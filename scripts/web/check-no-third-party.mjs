#!/usr/bin/env node
// Runtime check for a Flutter web build (ADR-010, constitution Art. 8-9):
// load the app in headless Chromium and fail on any request that leaves our origin.
//
// Usage: node scripts/web/check-no-third-party.mjs <path/to/build/web>
//
// Runs twice: the WebAssembly build (Chromium default) and the JavaScript fallback
// (what Safari/Firefox get), forced by hiding WasmGC support from Flutter's loader.
import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { extname, join, normalize, resolve, sep } from 'node:path';
import { chromium } from 'playwright';

const root = resolve(process.argv[2] ?? '');
if (!process.argv[2] || !(await stat(root).catch(() => null))?.isDirectory()) {
  console.error('usage: check-no-third-party.mjs <build/web directory>');
  process.exit(2);
}

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript',
  '.mjs': 'text/javascript',
  '.json': 'application/json',
  '.wasm': 'application/wasm',
  '.png': 'image/png',
  '.ico': 'image/x-icon',
  '.otf': 'font/otf',
  '.ttf': 'font/ttf',
  '.woff2': 'font/woff2',
  '.bin': 'application/octet-stream',
  '.frag': 'application/octet-stream',
};

const server = createServer(async (req, res) => {
  const path = decodeURIComponent(new URL(req.url, 'http://x').pathname);
  const file = normalize(join(root, path.endsWith('/') ? `${path}index.html` : path));
  if (file !== root && !file.startsWith(root + sep)) {
    res.writeHead(403).end();
    return;
  }
  try {
    const body = await readFile(file);
    res.writeHead(200, { 'content-type': MIME[extname(file)] ?? 'application/octet-stream' });
    res.end(body);
  } catch {
    res.writeHead(404).end();
  }
});
await new Promise((ok) => server.listen(0, '127.0.0.1', ok));
const origin = `http://127.0.0.1:${server.address().port}`;

// Flutter's loader probes WasmGC with this exact module; answering "invalid" selects the JS build.
const hideWasmGc = () => {
  const validate = WebAssembly.validate.bind(WebAssembly);
  const probe = [0, 97, 115, 109, 1, 0, 0, 0, 1, 5, 1, 95, 1, 120, 0];
  WebAssembly.validate = (bytes) => {
    const view = bytes instanceof Uint8Array ? bytes : new Uint8Array(bytes);
    const isProbe = view.length === probe.length && probe.every((b, i) => view[i] === b);
    return isProbe ? false : validate(bytes);
  };
};

const modes = [
  { name: 'wasm', entry: '/main.dart.wasm', init: null },
  { name: 'js', entry: '/main.dart.js', init: hideWasmGc },
];

const browser = await chromium.launch();
let failed = false;

for (const mode of modes) {
  const context = await browser.newContext({ serviceWorkers: 'block' });
  if (mode.init) await context.addInitScript(mode.init);

  // External requests are recorded (as violations) but aborted, so nothing leaves the machine.
  await context.route((url) => url.origin !== origin, (route) => route.abort());
  const urls = [];
  context.on('request', (r) => urls.push(r.url()));
  const page = await context.newPage();
  page.on('websocket', (ws) => urls.push(ws.url()));

  await page.goto(`${origin}/`, { waitUntil: 'load' });
  const booted = await page
    .waitForSelector('flutter-view', { state: 'attached', timeout: 30_000 })
    .then(() => true, () => false);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2_000); // late font-fallback fetches happen after first frame

  const external = urls.filter((u) => !u.startsWith(`${origin}/`) && !/^(data|blob):/.test(u));
  const loadedEntry = urls.some((u) => new URL(u, origin).pathname === mode.entry);

  const problems = [];
  if (!booted) problems.push('app did not render a <flutter-view>');
  if (!loadedEntry) problems.push(`expected ${mode.entry} to load (wrong build path exercised)`);
  for (const u of external) problems.push(`third-party request: ${u}`);

  if (problems.length > 0) {
    failed = true;
    console.error(`✗ ${mode.name}: ${problems.length} problem(s)`);
    for (const p of problems) console.error(`  - ${p}`);
  } else {
    console.log(`✓ ${mode.name}: ${urls.length} requests, all to ${origin}`);
    if (process.env.VERBOSE) for (const u of urls) console.log(`  ${u.replace(origin, '')}`);
  }
  await context.close();
}

await browser.close();
server.close();
process.exit(failed ? 1 : 0);
