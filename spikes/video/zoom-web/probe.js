// WP-0.4 Zoom web probe: join a Zoom Video SDK session in the browser and measure tap → first
// remote video, the same way the Flutter app measures LiveKit. `?bot=1&seat=…&room=…&code=…`
// joins straight away as a camera bot (headless Chromium with fake devices, tools/bot.mjs).
/* global WebVideoSDK, SPIKE_STRINGS */
'use strict';

// The UMD build exposes `WebVideoSDK`; its default export is the ZoomVideo entry point.
const ZoomVideo = WebVideoSDK.default;
const { VideoQuality } = WebVideoSDK;

const T = SPIKE_STRINGS.ar;
const $ = (id) => document.getElementById(id);
const q = new URLSearchParams(location.search);
const isBot = q.get('bot') === '1';
const TRIALS = 20;

document.querySelectorAll('[data-t]').forEach((el) => { el.textContent = T[el.dataset.t]; });
for (let i = 1; i <= 9; i++) $('seat').add(new Option(String(i), String(i)));
$('code').value = q.get('code') || '';
if (q.get('room')) $('room').value = q.get('room');
if (q.get('seat')) $('seat').value = q.get('seat');

let client = null;
let stream = null;
let grant = null;
let recording = false;

function form() {
  return {
    role: isBot ? 'bot' : $('role').value,
    seat: $('seat').value,
    room: $('room').value.trim(),
    device: isBot ? 'bot' : $('device').value.trim(),
    code: $('code').value.trim(),
    network: q.get('network') || 'normal',
  };
}

async function post(path, body, headers = {}) {
  const res = await fetch(path, { method: 'POST', headers: { 'content-type': 'application/json', ...headers }, body: JSON.stringify(body) });
  const text = await res.text();
  const json = text ? JSON.parse(text) : {};
  if (!res.ok) throw new Error(json.message_ar || json.code || `HTTP ${res.status}`);
  return json;
}

/** Resolves when a remote user's video has been attached and is rendering (attachVideo resolved). */
function firstRemoteVideo(me) {
  return new Promise((resolve) => {
    const attached = new Set();
    const attach = async (userId) => {
      if (userId === me || attached.has(userId)) return;
      attached.add(userId);
      const player = await stream.attachVideo(userId, VideoQuality.Video_360P);
      if (player && !(player instanceof Error) && player.nodeType === 1) {
        $('videos').appendChild(player);
        resolve();
      }
    };
    client.getAllUser().filter((u) => u.bVideoOn).forEach((u) => attach(u.userId));
    client.on('peer-video-state-change', ({ action, userId }) => { if (action === 'Start') attach(userId); });
  });
}

async function join() {
  const f = form();
  const clock = performance.now();
  const ms = () => Math.round(performance.now() - clock);
  const result = { tToken: null, tConnected: null, tVideo: null, error: null };
  try {
    grant = await post('/token', { provider: 'zoom', role: f.role, identity: `${f.role}-${f.seat}`, room: f.room, access_code: f.code });
    result.tToken = ms();
    client = ZoomVideo.createClient();
    await client.init('en-US', `${location.origin}/zoom-web/vendor/lib`, { patchJsMedia: true, enforceMultipleVideos: true });
    await client.join(grant.session_name, grant.token, grant.display_name);
    stream = client.getMediaStream();
    await stream.startAudio();
    await stream.startVideo();
    const self = await stream.attachVideo(client.getCurrentUserInfo().userId, VideoQuality.Video_360P);
    if (self && self.nodeType === 1) $('videos').appendChild(self);
    result.tConnected = ms();
    $('status').textContent = T.waiting;
    const timeout = new Promise((_, reject) => setTimeout(() => reject(new Error('no remote video in 30s')), isBot ? 3_600_000 : 30_000));
    await Promise.race([firstRemoteVideo(client.getCurrentUserInfo().userId), timeout]);
    result.tVideo = ms();
    $('status').textContent = T.joined((result.tVideo / 1000).toFixed(2));
  } catch (e) {
    result.error = String(e && (e.reason || e.message || e.type || e));
    $('status').textContent = T.error(result.error);
  }
  $('host').hidden = !grant || !grant.admin_ticket;
  $('leave').hidden = false;
  return result;
}

async function leave() {
  if (client) {
    try { await client.leave(); } catch { /* already gone */ }
    ZoomVideo.destroyClient();
  }
  client = null; stream = null;
  $('videos').replaceChildren();
  $('host').hidden = true;
  $('leave').hidden = true;
}

function metric(trial, r, mode) {
  const f = form();
  if (isBot) return Promise.resolve();
  return post('/metrics', {
    provider: 'zoom-web', platform: 'web', device: f.device, network: f.network, role: f.role, room: f.room, mode, trial,
    t_token_ms: r.tToken, t_connected_ms: r.tConnected, t_first_video_ms: r.tVideo, ok: r.tVideo !== null && !r.error,
    ...(r.error ? { error: r.error } : {}),
  }, { 'x-spike-code': f.code }).catch(() => {});
}

function percentile(values, p) {
  const sorted = [...values].sort((a, b) => (a === null) - (b === null) || a - b);
  const v = sorted[Math.max(1, Math.ceil((p / 100) * sorted.length)) - 1];
  return v === null || v === undefined ? '—' : (v / 1000).toFixed(2);
}

$('join').addEventListener('click', async () => {
  if (!$('form').reportValidity()) return;
  $('status').textContent = T.joining;
  metric(0, await join(), 'manual');
});

$('trials20').addEventListener('click', async () => {
  if (!$('form').reportValidity()) return;
  const times = [];
  $('trials').replaceChildren();
  for (let i = 1; i <= TRIALS; i++) {
    $('status').textContent = T.trial(i, TRIALS);
    const r = await join();
    await leave();
    await metric(i, r, 'auto');
    times.push(r.error ? null : r.tVideo);
    const li = document.createElement('li');
    li.textContent = r.error ? r.error : T.trialLine((r.tVideo / 1000).toFixed(2), r.tToken, r.tConnected);
    $('trials').appendChild(li);
    await new Promise((res) => setTimeout(res, 2000));
  }
  $('status').textContent = T.done(times.filter((t) => t !== null).length, TRIALS, percentile(times, 50), percentile(times, 95));
});

$('leave').addEventListener('click', leave);
$('muteAll').addEventListener('click', () => stream && stream.muteAllAudio());
$('record').addEventListener('click', async () => {
  const rec = client.getRecordingClient();
  recording = !recording;
  await (recording ? rec.startCloudRecording() : rec.stopCloudRecording());
  $('record').textContent = recording ? T.recordStop : T.recordStart;
});

if (isBot) {
  (async function botLoop() {
    const r = await join();
    if (r.error && r.tConnected === null) { await leave(); setTimeout(botLoop, 3000); }
  })();
}
