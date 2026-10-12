// WP-0.4 video spike: a headless Chromium participant with a fake camera and microphone, so every
// join-time trial has a remote video to wait for. Uses the Playwright already installed for
// scripts/web (no new dependency).
//
//   node spikes/video/tools/bot.mjs [--provider livekit] [--room spike-room] [--seat 1] [--minutes 120]
//                                   [--network limited-1.5mbit] [--stats] [--server https://…]
//
// --stats appends the app's `SPIKE_STATS` lines (quality, audio-only, received video) to
// token-server/var/stats-bot-<seat>.jsonl; tools/throttled.sh uses it inside a shaped container.
//
// Opens the local spike server (http://127.0.0.1:27100, a secure context for getUserMedia) with
// `?bot=1`, which joins as `bot-<seat>` and keeps rejoining if dropped. Ctrl+C or --minutes to stop.
import { appendFileSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs } from 'node:util';

const here = dirname(fileURLToPath(import.meta.url));
const repo = join(here, '..', '..', '..');
const { chromium } = await import(join(repo, 'scripts/web/node_modules/playwright/index.mjs'));

const { values } = parseArgs({
  options: {
    provider: { type: 'string', default: 'livekit' },
    room: { type: 'string', default: 'spike-room' },
    seat: { type: 'string', default: '1' },
    minutes: { type: 'string', default: '120' },
    server: { type: 'string', default: 'http://127.0.0.1:27100' },
    network: { type: 'string', default: 'normal' },
    stats: { type: 'boolean', default: false },
  },
});

const code = JSON.parse(readFileSync(join(here, '..', 'token-server/var/spike.json'), 'utf8')).access_code;
// Zoom has no Flutter web support, so a Zoom bot runs the JavaScript probe page instead.
const page0 = values.provider === 'zoom' ? '/zoom-web/' : '/';
const url = `${values.server}${page0}?bot=1&provider=${encodeURIComponent(values.provider)}&room=${encodeURIComponent(values.room)}&seat=${encodeURIComponent(values.seat)}&network=${encodeURIComponent(values.network)}${values.stats ? '&stats=1' : ''}&code=${encodeURIComponent(code)}`;
const statsFile = join(here, '..', `token-server/var/stats-bot-${values.seat}.jsonl`);

const browser = await chromium.launch({
  args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream', '--autoplay-policy=no-user-gesture-required'],
});
const context = await browser.newContext({ permissions: ['camera', 'microphone'], viewport: { width: 640, height: 480 } });
const page = await context.newPage();
page.on('console', (m) => {
  const text = m.text();
  if (text.startsWith('SPIKE_STATS ')) {
    appendFileSync(statsFile, `${text.slice('SPIKE_STATS '.length)}\n`);
  } else if (m.type() === 'error') {
    console.error(`[bot ${values.seat}] ${text}`);
  }
});
await page.goto(url);
console.log(`bot-${values.seat} joining ${values.provider}/${values.room} for ${values.minutes} min`);

const stop = async () => {
  await browser.close();
  process.exit(0);
};
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
setTimeout(stop, Number(values.minutes) * 60_000);
