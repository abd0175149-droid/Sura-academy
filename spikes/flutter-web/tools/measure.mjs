// WP-0.5: PC baseline in headless Chromium (Playwright from scripts/web, no new dependency).
//
//   node spikes/flutter-web/tools/measure.mjs [--runs 10] [--profiles none,slow4g,fast4g]
//                                              [--variants wasm/iso,wasm/plain,js/iso,js/plain]
//   node spikes/flutter-web/tools/measure.mjs --checks     isolation flows, requests, accessibility
//
// Load mode: per profile × variant, N cold loads (fresh browser context and fresh r-<run> URL) then
// N warm loads (same context, service worker active). The app posts its own timings to the spike
// server (server/var/metrics.jsonl); this script adds the time to the first real interaction
// (tap the «النموذج» tab until its text field is on screen) in results/pc-interaction.csv.
//
// Profiles (Chrome DevTools presets, applied throttling; CPU slowed like a mid-range phone):
//   none    no throttling, CPU ×1
//   slow4g  DevTools «Slow 4G»: 562.5 ms latency, 1.44 Mbit/s down, 675 kbit/s up, CPU ×4
//   fast4g  DevTools «Fast 4G»: 165 ms latency, 8.1 Mbit/s down, 1.35 Mbit/s up, CPU ×4
import { appendFileSync, existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs } from 'node:util';
import { randomBytes } from 'node:crypto';

const here = dirname(fileURLToPath(import.meta.url));
const spike = join(here, '..');
const repo = join(spike, '..', '..');
const { chromium } = await import(join(repo, 'scripts/web/node_modules/playwright/index.mjs'));

const { values } = parseArgs({
  options: {
    runs: { type: 'string', default: '10' },
    profiles: { type: 'string', default: 'none,slow4g,fast4g' },
    variants: { type: 'string', default: 'wasm/iso,wasm/plain,js/iso,js/plain' },
    base: { type: 'string', default: 'http://127.0.0.1:27200' },
    checks: { type: 'boolean', default: false },
    video: { type: 'string', default: 'local' },
  },
});

const key = JSON.parse(readFileSync(join(spike, 'server/var/spike.json'), 'utf8')).run_key;
const base = values.base;
const PROFILES = {
  none: { label: 'unthrottled', cpu: 1, net: null },
  slow4g: { label: 'throttled-slow-4g', cpu: 4, net: { latency: 562.5, downloadThroughput: (1.6 * 1e6 * 0.9) / 8, uploadThroughput: (750 * 1e3 * 0.9) / 8 } },
  fast4g: { label: 'throttled-fast-4g', cpu: 4, net: { latency: 165, downloadThroughput: (9 * 1e6 * 0.9) / 8, uploadThroughput: (1.5 * 1e6 * 0.9) / 8 } },
};
// WebGL through SwiftShader so headless Chromium can run skwasm (it needs WebGL), plus a fake camera.
const LAUNCH = {
  args: ['--enable-unsafe-swiftshader', '--use-angle=swiftshader', '--use-fake-device-for-media-stream', '--use-fake-ui-for-media-stream'],
};

const run = () => `r-${randomBytes(8).toString('hex')}`;
const url = (variant, runId, extra) => `${base}/v/${variant}/${runId ? `${runId}/` : ''}?${new URLSearchParams({ k: key, d: 'headless-chrome', ...extra })}`;

async function throttle(page, profile) {
  const cdp = await page.context().newCDPSession(page);
  await cdp.send('Emulation.setCPUThrottlingRate', { rate: profile.cpu });
  if (profile.net) await cdp.send('Network.emulateNetworkConditions', { offline: false, ...profile.net });
  return cdp;
}

/** Loads the app, waits for "interactive" (posted by the app), then times the first real tap. */
async function load(page, target, timeoutMs) {
  const t0 = Date.now();
  await page.goto(target, { waitUntil: 'commit', timeout: timeoutMs });
  await page.waitForFunction(() => window.suraProbe?.startup, null, { timeout: timeoutMs, polling: 50 });
  const startup = JSON.parse(await page.evaluate(() => window.suraProbe.startup));
  // A tap can land before Flutter attached its handlers under heavy CPU throttling: retry it.
  const field = page.getByRole('textbox', { name: 'اسم تجريبي' });
  const deadline = Date.now() + timeoutMs;
  for (;;) {
    await page.getByRole('tab', { name: 'النموذج' }).click({ timeout: Math.max(1000, deadline - Date.now()) });
    if (await field.waitFor({ timeout: 3000 }).then(() => true, () => false)) break;
    if (Date.now() > deadline) throw new Error('form tab never opened');
  }
  const firstInput = await page.evaluate(() => performance.now());
  return { startup, firstInput: Math.round(firstInput), wallMs: Date.now() - t0 };
}

async function loads() {
  const out = join(spike, 'results/pc-interaction.csv');
  if (!existsSync(out)) writeFileSync(out, 'profile,variant,phase,wasm,threads,isolated,fcp,interactive,first_input,transfer_kb,status\n');
  const browser = await chromium.launch(LAUNCH);
  const runs = Number(values.runs);
  for (const name of values.profiles.split(',')) {
    const profile = PROFILES[name];
    for (const variant of values.variants.split(',')) {
      const timeout = name === 'slow4g' ? 180_000 : 90_000;
      let warmContext = null;
      let warmUrl = '';
      for (let i = 0; i < runs; i++) {
        const context = await browser.newContext({ locale: 'ar' });
        const page = await context.newPage();
        await throttle(page, profile);
        const runId = run();
        const target = url(variant, runId, { n: profile.label, phase: 'cold' });
        const r = await load(page, target, timeout).catch((e) => ({ error: e.message.split('\n')[0] }));
        record(out, name, variant, 'cold', r);
        if (i === runs - 1) {
          // Keep the last cold context for the warm loads, once the service worker cache is filled.
          await page.evaluate(() => window.suraSpike?.swReady).catch(() => null);
          warmContext = context;
          warmUrl = url(variant, runId, { n: profile.label, phase: 'warm' });
          await page.close();
        } else {
          await context.close();
        }
      }
      for (let i = 0; i < runs; i++) {
        const page = await warmContext.newPage();
        await throttle(page, profile);
        record(out, name, variant, 'warm', await load(page, warmUrl, timeout).catch((e) => ({ error: e.message.split('\n')[0] })));
        await page.close();
      }
      await warmContext.close();
      console.log(`done ${name} ${variant}`);
    }
  }
  await browser.close();
}

function record(file, profile, variant, phase, { startup, firstInput, error }) {
  if (error) {
    console.error(`load failed (${profile} ${variant} ${phase}): ${error}`);
    appendFileSync(file, [profile, variant, phase, '', '', '', '', '', '', '', 'error'].join(',') + '\n');
    return;
  }
  const m = startup.marks ?? {};
  appendFileSync(file, [profile, variant, phase, startup.wasm, startup.threads, startup.isolated, m.fcp ?? '', m.interactive ?? '', firstInput, startup.transfer_kb, 'ok'].join(',') + '\n');
}

// --- checks: isolation flows, requests leaving the origin, accessibility tree ----------------------

async function checks() {
  const browser = await chromium.launch(LAUNCH);
  const lines = ['| Variant | Runs as | Redirect and return | Popup | Iframe (gateway sends nothing) | Iframe (gateway sends CORP + COEP) | Video (2 participants) |', '| --- | --- | --- | --- | --- | --- | --- |'];
  const offOrigin = new Set();
  for (const variant of ['wasm/iso', 'wasm/plain', 'js/iso', 'js/plain']) {
    const context = await browser.newContext({ locale: 'ar' });
    context.on('request', (r) => {
      const u = new URL(r.url());
      if (u.origin !== base && u.origin !== 'http://127.0.0.1:27201' && !u.protocol.startsWith('blob') && !u.protocol.startsWith('data')) offOrigin.add(r.url());
    });
    const page = await context.newPage();
    const { startup } = await load(page, url(variant, run(), { n: 'unthrottled', tab: 'payment' }), 90_000);
    const runsAs = startup.wasm ? `WASM, ${startup.threads === 'multi' ? 'multi-thread' : 'single thread'}` : 'JS';
    const probe = async () => JSON.parse((await page.evaluate(() => window.suraProbe?.payment)) ?? '{}');
    const tab = (name) => page.getByRole('tab', { name }).click();

    // 1. Full-page redirect to the gateway and back.
    await tab('الدفع');
    await page.getByRole('button', { name: 'انتقال كامل إلى صفحة الدفع' }).click();
    await page.waitForURL(/127\.0\.0\.1:27201\/pay/, { timeout: 30_000 });
    await page.getByRole('link', { name: 'ادفع' }).click();
    await page.waitForFunction(() => window.suraProbe?.payment, null, { timeout: 90_000 });
    const redirect = (await probe()).status === 'paid' ? 'works' : 'FAILS';

    // 2. Popup posting back to window.opener.
    let popupResult = 'FAILS';
    try {
      const [popup] = await Promise.all([page.waitForEvent('popup', { timeout: 15_000 }), page.getByRole('button', { name: 'صفحة الدفع في نافذة منبثقة' }).click()]);
      await popup.waitForLoadState();
      await popup.getByRole('button', { name: 'ادفع' }).click();
      const said = await popup.locator('#pay-state').textContent({ timeout: 3000 }).catch(() => '');
      await page.waitForFunction(() => JSON.parse(window.suraProbe?.payment ?? '{}').via === 'popup', null, { timeout: 5000 }).then(() => { popupResult = 'works'; }, () => { popupResult = said ? `FAILS (popup has no opener: «${said.split('.')[0]}»)` : 'FAILS (no message)'; });
    } catch (e) {
      popupResult = `FAILS (${e.message.split('\n')[0]})`;
    }

    // 3–4. Iframe without and with the embedding headers.
    const frame = async (button, mode) => {
      await page.getByRole('button', { name: button }).click();
      const deadline = Date.now() + 15_000;
      let f = null;
      while (!f && Date.now() < deadline) {
        f = page.frames().find((x) => x.url().includes(`mode=${mode}`) && x.url().includes('/frame'));
        await page.waitForTimeout(200);
      }
      if (!f) return 'BLOCKED (the browser did not load the frame)';
      const pay = f.getByRole('button', { name: 'ادفع' });
      const visible = await pay.isVisible({ timeout: 5000 }).catch(() => false);
      if (!visible) return 'BLOCKED by the browser';
      await pay.click();
      return page.waitForFunction((m) => JSON.parse(window.suraProbe?.payment ?? '{}').via === m, mode, { timeout: 5000 }).then(() => 'works', () => 'FAILS (no message)');
    };
    const frameNone = await frame('صفحة الدفع داخل إطار', 'frame');
    await page.getByRole('button', { name: 'إغلاق الإطار' }).click().catch(() => {});
    const frameCorp = await frame('داخل إطار يسمح بالتضمين', 'frame-corp');

    // 5. Video: two participants in the local LiveKit dev server (or Cloud), each must see the other.
    const video = await videoCheck(browser, variant);
    lines.push(`| \`${variant}\` | ${runsAs} | ${redirect} | ${popupResult} | ${frameNone} | ${frameCorp} | ${video} |`);
    await context.close();
    console.log(`checked ${variant}`);
  }

  // Accessibility tree of the form tab (Chrome's view of Flutter's semantics DOM).
  const context = await browser.newContext({ locale: 'ar' });
  const page = await context.newPage();
  await load(page, url('js/plain', run(), { n: 'unthrottled' }), 90_000);
  const html = await page.evaluate(() => ({ lang: document.documentElement.lang, dir: document.documentElement.dir }));
  const tree = await page.locator('flt-semantics-host').ariaSnapshot();
  await context.close();
  await browser.close();

  writeFileSync(join(spike, 'results/isolation.md'), [
    '# Isolation headers: what breaks (headless Chromium, PC)', '',
    `Generated by \`tools/measure.mjs --checks\` on ${new Date().toISOString().slice(0, 10)}. Gateway = the spike's fake payment origin on another port.`, '',
    ...lines, '',
    `Requests to other origins during these loads: ${offOrigin.size === 0 ? 'none' : [...offOrigin].join(', ')}`, '',
  ].join('\n'));
  writeFileSync(join(spike, 'results/a11y.md'), [
    '# Accessibility tree of the form tab (Chromium, JS build)', '',
    `\`<html lang="${html.lang}" dir="${html.dir}">\`. Semantics on at start (ADR-010 §4). Snapshot from Playwright \`ariaSnapshot()\`:`, '',
    '```yaml', tree, '```', '',
  ].join('\n'));
  console.log('wrote results/isolation.md and results/a11y.md');
}

async function videoCheck(browser, variant) {
  const pages = [];
  try {
    for (let i = 0; i < 2; i++) {
      const context = await browser.newContext({ locale: 'ar', permissions: ['camera', 'microphone'] });
      const page = await context.newPage();
      await page.goto(url(variant, run(), { n: 'unthrottled', tab: 'video', video: values.video }), { waitUntil: 'commit' });
      pages.push(page);
    }
    const seen = async (page) => page.waitForFunction(() => {
      const v = JSON.parse(window.suraProbe?.video ?? '{}');
      return v.phase === 'failed' || v.remote_videos >= 1;
    }, null, { timeout: 60_000, polling: 250 }).then(async () => JSON.parse(await page.evaluate(() => window.suraProbe.video)));
    const results = await Promise.all(pages.map(seen));
    if (results.every((r) => r.remote_videos >= 1)) return 'works';
    return `FAILS (${results.map((r) => r.error || r.phase).join('; ')})`;
  } catch (e) {
    return `FAILS (${e.message.split('\n')[0]})`;
  } finally {
    for (const p of pages) await p.context().close();
  }
}

await (values.checks ? checks() : loads());
