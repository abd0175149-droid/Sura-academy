// WP-0.5: keep one headless participant with a fake camera in the isolation test room, so the
// owner sees a second video on the iPad (station step «فيديو»).
//
//   node spikes/flutter-web/tools/video-bot.mjs [--target cloud|local] [--minutes 20] [--variant js/iso]
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs } from 'node:util';

const here = dirname(fileURLToPath(import.meta.url));
const spike = join(here, '..');
const { chromium } = await import(join(spike, '../../scripts/web/node_modules/playwright/index.mjs'));
const { values } = parseArgs({
  options: {
    target: { type: 'string', default: 'cloud' },
    minutes: { type: 'string', default: '20' },
    variant: { type: 'string', default: 'js/iso' },
  },
});
const key = JSON.parse(readFileSync(join(spike, 'server/var/spike.json'), 'utf8')).run_key;
const browser = await chromium.launch({ args: ['--use-fake-device-for-media-stream', '--use-fake-ui-for-media-stream', '--enable-unsafe-swiftshader', '--use-angle=swiftshader'] });
const context = await browser.newContext({ locale: 'ar', permissions: ['camera', 'microphone'] });
const page = await context.newPage();
const q = new URLSearchParams({ k: key, d: 'headless-chrome', n: 'unthrottled', tab: 'video', video: values.target });
await page.goto(`http://127.0.0.1:27200/v/${values.variant}/?${q}`);
const until = Date.now() + Number(values.minutes) * 60_000;
let last = '';
while (Date.now() < until) {
  const state = await page.evaluate(() => window.suraProbe?.video ?? '').catch(() => '');
  if (state !== last) {
    console.log(new Date().toISOString().slice(11, 19), state);
    last = state;
  }
  await page.waitForTimeout(5000);
}
await browser.close();
