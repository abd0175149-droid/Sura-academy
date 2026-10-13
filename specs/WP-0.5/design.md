# Design — WP-0.5 Technical spike: Flutter on the web

## 1. Scope
- Goal (phase-0-foundation.md, WP-0.5): real numbers that settle the Flutter web build settings (ADR-010) and the load-time definition (D-22 / CR-003), plus a written decision on the cross-origin isolation headers (COOP/COEP).
- In scope (IDs): **feasibility only**, nothing implemented for production. CNT-08 (Arabic typography: bundled fonts with diacritics, enlarge text, show/hide diacritics), PRV-10 (no third-party requests on child-facing pages), and the NFR rows of `spec/18`: performance (LCP / interactive), accessibility (screen reader in Arabic), Arabic typography, devices (Safari on iPad, Chromebook). CNT-08 and PRV-10 stay assigned to WP-1.6 / WP-4.5 / WP-5.2 in `traceability.md`; their status column is **not** changed by this WP.
- Deliverables (all spike code under `spikes/flutter-web/`, never imported by `apps/` or `packages/`):
  1. A reading page with fully vocalized text in Noto Naskh Arabic and IBM Plex Sans Arabic at 16, 24 and 32 px, a show/hide diacritics switch and a text-size control (1.0 / 1.3 / 2.0).
  2. An Arabic form (text fields), a list and an interactive button, with the semantics tree enabled at start-up.
  3. Two builds (`--wasm` with its automatic JS fallback, and JS only), each served twice: with COOP/COEP and without → four variants on one server.
  4. Measurements: HTML splash paint (FCP, and LCP where the browser reports it), time to interactive (cold and warm), bundle size (raw / gzip / brotli).
  5. Redirect to an external (fake) payment page and back, in both header modes; popup and iframe modes as extra probes.
  6. Install as an app (PWA) with a hand-written service worker.
  7. Screenshots comparing diacritics rendering on the web (Chrome WASM, Chrome JS, Safari iPad) and Android.
- Explicitly out of scope: production code in `apps/` or `packages/` (design system and fonts land in WP-1.6); `go_router`, Riverpod and the generated API client; real PayTabs (WP-0.6); real LiveKit rooms or keys; WCAG audit of product screens (WP-5.4); load testing (WP-5.3); any real child, family or teacher data.
- Decisions applied:
  - **D-22 (Default, needs CR-003):** the spike measures against the default targets — HTML splash LCP ≤ 2.5 s; interactive ≤ 5 s first load and ≤ 2 s later loads, mid-range device on 4G — and proposes final numbers plus an exact definition of "mid-range device" and "4G". The owner then approves CR-003 as is or amended. Claude does not change D-22's status or the spec.
  - **ADR-010 (Proposed):** every initial setting (1–7) is exercised; the ADR moves to Accepted with final values **only after the owner's explicit approval**, recorded in notes.md.
  - Carry-over from WP-0.4 notes (T4, privacy review): `fontFallbackBaseUrl` must point to our origin and the `web:no-third-party-requests` gate must catch a fallback-font request. This WP handles both (section 5, T9).

## 2. Traceability (feasibility evidence, not production tests)
Spike tests live in `spikes/flutter-web/**`, which `check-traceability.php` does not scan, so they cannot be mistaken for WP-1.6 coverage. Names start with `spike` and cite the ID or NFR row.

| Req | What the spike must show | Code location (planned) | Evidence (planned) |
| --- | --- | --- | --- |
| CNT-08 | Bundled fonts render full tashkeel without clipping at 16/24/32 and text scale up to 2.0; diacritics hide/show is display-only; enlarge text works | `app/lib/reading/`, `app/lib/arabic/diacritics.dart`, `app/assets/fonts/` | `spike CNT-08 hiding diacritics removes only marks and keeps letters`, `spike CNT-08 reading page shows both fonts at 16 24 32 without overflow at text scale 2.0`, `spike CNT-08 diacritics switch updates every sample`; screenshots web vs Android in `results/shots/` |
| PRV-10 | Zero requests outside our origin in every variant, including a missing-glyph case and the service worker; strict CSP | `app/web/flutter_bootstrap.js`, `server/src/Headers.php`, `scripts/web/check-no-third-party.mjs` | `spike PRV-10 every response carries a CSP limited to our origins`, `spike PRV-10 metrics payload has only allow-listed fields`; network log of `tools/measure.mjs` in `results/requests.md`; gate run on the spike build |
| NFR performance (D-22) | Splash FCP/LCP and time to interactive, cold and warm, per variant and device; bundle sizes | `app/web/index.html` (marks), `app/lib/measure/`, `tools/measure.mjs`, `tools/summarize.php` | `spike NFR-perf startup marks are reported once with renderer info`; `results/load-times.csv`, `results/sizes.csv`, p50/p95 in `results/summary.md` |
| NFR isolation (ADR-010 §3) | Which variant actually runs where (WASM multi-thread / WASM single-thread / JS), and whether COOP/COEP break payment redirect, popup, iframe or video | `server/src/Router.php`, `app/lib/payment/`, `app/lib/video/` | `spike NFR-isolation iso variant sends COOP and COEP`, `spike NFR-isolation plain variant sends no isolation headers`, `spike payment return only goes back to the app origin`; flow results in `results/isolation.md` |
| NFR accessibility | Semantics tree on at start; form, list, button and headings exposed with Arabic labels and `lang`/`dir`; VoiceOver reads Arabic on iPad | `app/lib/main.dart`, `app/lib/form/` | `spike NFR-a11y form list and button expose Arabic semantics`; Chrome accessibility snapshot in `results/a11y.md`; owner's VoiceOver checklist answers |
| NFR install (ADR-010 §6) | App installs on iPad and Chromebook; warm loads come from our service worker; the payment return works inside the installed app | `app/web/sw.js`, `app/web/manifest.json` | owner checklist; `measure.mjs` warm runs show `sw: controlled` |

## 3. Data
No database. Runtime files under `spikes/flutter-web/server/var/` (git-ignored): `metrics.jsonl` (timings), `shots/` (PNG screenshots), `feedback.jsonl` (owner checklist answers), `csp.jsonl` (CSP reports). Device labels come from a fixed list (`ipad-safari`, `chromebook-chrome`, `pc-chrome`, `android-app`, …); no names, accounts or free text in metrics. Only aggregated results and screenshots of test text are committed (`spikes/flutter-web/results/`).

## 4. API contract changes
None to `contracts/openapi/sura-api.v1.yaml`. The spike server's own endpoints are not part of the product API:

| Method | Path | Request | Response | Errors |
| --- | --- | --- | --- | --- |
| GET | `/v/{wasm\|js}/{iso\|plain}/[r-<run>/]…` | — | the build's files; `index.html` with the variant's `<base href>`; COOP/COEP only for `iso`; CSP always; precompressed `br`/`gzip` when accepted | 404 |
| POST | `/metrics` | `{variant, run, device, network, marks{…}, renderer, threads, isolated, sw}` + run key | 204 | 403 bad key · 413 too big · 422 unknown field |
| POST | `/shots` | PNG body, `?device=&variant=&font=&size=` + run key | 204 | 403 · 413 (> 5 MB) · 415 not PNG |
| POST | `/feedback` | `{device, answers{q1..qn: yes\|no}}` + run key | 204 | 403 · 422 |
| POST | `/csp-report` | CSP report JSON | 204 | 413 |
| POST | `/livekit-token` | `{room, identity}` + run key → token for the **local** LiveKit dev server only | `{token, url}` | 403 · 422 |
| GET | `/downloads/flutter-web-spike.apk` | — | Android APK for the screenshot comparison | 404 |
| GET | (payment origin, second port) `/pay`, `/frame`, `/popup` | `?return=<app url>&mode=` | fake hosted payment page with «ادفع» / «إلغاء» | 400 return URL not on the allow-list |

Errors use the product's shape `{code, message_ar, message_en, details, request_id}`; messages from `server/lang/{ar,en}.php`.

## 5. Domain logic
Spike only, no business rules.
- **Builds (`tools/build.sh`):** `flutter build web --wasm --no-web-resources-cdn --release` → `build/web-wasm`; `flutter build web --no-web-resources-cdn --release` → `build/web-js`. Then: write the service-worker asset list (versioned cache name per build), precompress every text/wasm/font file to `.br` and `.gz` (Node `zlib`, no new tool), and write `results/sizes.csv` (per file and total; raw, gzip, brotli). `flutter build apk --release` for Android (JDK and Android SDK already in `~/tools` from WP-0.4).
- **Bootstrap (`web/flutter_bootstrap.js`):** `performance.mark()` at script start, entrypoint loaded, engine initialised, app running; config `fontFallbackBaseUrl: '<our origin>/fonts/fallback/'`, `canvasKitBaseUrl` relative; `forceSingleThreadedSkwasm` left automatic. The app marks its first frame and "interactive" (first frame + one more frame with the semantics tree built) and reads the renderer (WASM vs JS via `dart.tool.dart2wasm`; skwasm vs CanvasKit; `crossOriginIsolated`; service-worker state).
- **HTML splash (`web/index.html`):** `lang="ar" dir="rtl"`, inline CSS and an inline SVG logo, system font only (no download before first paint), text «أكاديمية سُرى — جارٍ التحميل» and a stage line updated from the bootstrap; removed on the first Flutter frame.
- **Service worker (`web/sw.js`, hand-written):** on install, precache the build's file list; cache-first for build files, network-first for `index.html` and navigations; never touches cross-origin requests or the payment return query; old caches deleted on activate. `manifest.json`: `lang: ar`, `dir: rtl`, `display: standalone`, icons.
- **Server (`server/`, plain PHP 8.4, no Composer packages, `php -S` with `PHP_CLI_SERVER_WORKERS=4`):** port 27200 (app) and 27201 (fake payment origin, a different origin). Variant from the path; optional `r-<run>` segment so each cold run uses fresh URLs (empty HTTP and code caches) while serving the same files. Headers: `iso` → `Cross-Origin-Opener-Policy: same-origin`, `Cross-Origin-Embedder-Policy: require-corp`; all → `Cross-Origin-Resource-Policy: same-origin`, a strict CSP (`default-src 'self'`; `script-src 'self' 'wasm-unsafe-eval'`; `frame-src`/`form-action` = payment origin; `connect-src 'self'` + local LiveKit; `report-uri /csp-report`) and `Cache-Control: no-cache` + `ETag` (Flutter's file names are not content-hashed, so caching for warm loads is the service worker's job). A random run key is generated at start and put in the link; every POST needs it; body caps; server-chosen file names; PNG magic-byte check; path traversal blocked.
- **Fake payment origin:** `/pay` behaves like a hosted payment page (full-page redirect back to `return` with `?status=paid|cancelled&ref=…`); `/popup` posts the result to `window.opener` and closes; `/frame` is iframe content that posts to its parent, served once with and once without `Cross-Origin-Resource-Policy: cross-origin` to show what a real gateway would need under COEP. `return` must start with the app origin (no open redirect).
- **Video under the headers:** a «فيديو» tab joins a room on a **local LiveKit dev server** (Docker `livekit/livekit-server --dev`, no account, no keys from WP-0.4) with `livekit_client` (already shown to compile to WASM in WP-0.4). `measure.mjs` opens two headless Chrome pages with a fake camera per variant; pass = each sees the other's video. Media from the local server cannot cross the quick tunnel, so iPad video under the headers is only checked if the owner chooses option (b) in question 3.
- **Measurement (`tools/measure.mjs`, Playwright already in `scripts/web`):** per variant, 10 cold runs (fresh browser context) and 10 warm runs (same context, service worker active), under three profiles: none; "slow 4G" = Lighthouse mobile (150 ms RTT, 1.6 Mbit/s down, 750 kbit/s up, CPU × 4); "fast 4G" = Chrome DevTools preset of that name (values written into the results). Also: time from first frame to the first successful tap on the button; every request URL (third-party check); Chrome accessibility snapshot; payment redirect/popup/iframe and video under each variant. `tools/summarize.php` → p50/p95 per device × variant × cold/warm in `results/summary.md`.
- **On devices:** the link opens a «تشغيل القياس تلقائياً» page that walks through the four variants by itself (5 cold + 5 warm each, the page reloads itself) and posts the numbers; the owner only keeps the screen on. Device numbers go through the owner's network and a Cloudflare quick tunnel, so they are reported next to, not mixed with, the controlled PC profiles.

## 6. UI (spike app)
- Flutter project `spikes/flutter-web/app` (standalone, not in the pub workspace, own `analysis_options.yaml`), platforms web and Android.
- Tabs: «النص المشكول» (two fonts × three sizes, diacritics switch, size control, «التقاط صورة» uploads a PNG of the text area via `RepaintBoundary`), «النموذج» (name and notes fields, a 30-item list, a counter button), «الدفع» (redirect / popup / iframe, result shown on return), «فيديو» (local LiveKit probe), «القياس» (renderer, isolation, service worker, timings of this load, auto-run, clear caches), «ملاحظاتك» (yes/no checklist for VoiceOver, keyboard, install; posted to `/feedback`).
- Text samples: a fully vocalized paragraph; stress lines (shadda with tanween, superscript alef, hamza forms, lam-alef, alef wasla); a short vocalized line in Quranic orthography to see the fonts' coverage of small high marks; a mixed Arabic/Latin/number line with bidi isolation; one emoji and one rare symbol to trigger font fallback.
- Strings in ARB (`ar` first, `en`), RTL by default, directional APIs only, semantics labels on every control, touch targets ≥ 44 px. No goldens (spike, thrown away after the decision). No analytics.

## 7. Security & privacy
- Public repo: no keys in git. The LiveKit dev server uses its built-in development key, which only works against a local server; nothing from WP-0.4's key file is read or reused.
- Testers are **adults only** (owner); no child takes part. Screenshots contain only the test text; metrics carry a fixed device label, browser user agent and timings, nothing else.
- The quick-tunnel links are shared only with the owner and change every session; POST endpoints need the run key; the payment origin refuses return URLs outside the app origin.
- `rtl-reviewer` runs at the end (WP review row), plus `traceability-checker`.

## 8. Risks & open questions (owner)
1. **No Chromebook among the owner's devices (WP-0.4 list: Windows PC with Chrome, iPad, iPhone, Android phone).** Options: (a) borrow a Chromebook for one session; (b) substitute: Chrome on the Windows PC with CPU slowed × 4 (same WASM path, estimated CPU) plus Chrome on the Android phone as a real mid-range device; (c) both.
2. **Android screenshots:** (a) Claude runs an Android emulator on this machine (`/dev/kvm` is present; one-time system image ≈ 1.5 GB in `~/tools/android-sdk`), no owner effort; (b) the owner installs the APK on their Android phone and presses «التقاط صورة»; (c) both.
3. **Video under the isolation headers on iPad:** (a) local LiveKit dev server in Chrome on this machine only (automatic, no keys); (b) additionally a one-session LiveKit Cloud key that the owner creates and revokes afterwards, to test Safari on iPad.
4. **New tools and dependencies (spike only):** Docker image `livekit/livekit-server`; Flutter packages `livekit_client` (approved for spikes in WP-0.4), `web` (Dart's own browser API package), `http`; OFL fonts Noto Naskh Arabic and IBM Plex Sans Arabic (four weights) downloaded once from their official GitHub releases into `app/assets/fonts/` with the licence; Android system image only if 2(a).
5. **Small change outside `spikes/`:** `scripts/web/check-no-third-party.mjs` gains a check that fails when the built `flutter_bootstrap.js` keeps Google's default `fontFallbackBaseUrl` (carry-over from WP-0.4). No product code changes.
6. **Known facts that shape the outcome (Flutter docs, checked 2026-10-13):** the WASM build runs only on Chromium browsers; Safari (including every browser on iPad) and Firefox get the JS build. Multi-threaded WASM rendering needs COOP/COEP; without them it runs single-threaded. So the headers can only help Chromebook/Chrome users, while any breakage would hit payment or video for everyone — the spike quantifies both sides.
7. **"4G" is ambiguous in D-22:** at 1.6 Mbit/s a first load of ~3–4 MB compressed cannot be interactive in 5 s, at 9 Mbit/s it might. The proposal to the owner will name the exact network and device profile behind each number.
8. **Device time:** about 15 minutes per device for the automatic run, plus ~20 minutes for VoiceOver, install and payment checks on the iPad.
9. **Closing:** ADR-010 → Accepted and the CR-003 numbers are set only with the owner's explicit approval, recorded in notes.md; the owner's approvals table in status.md is updated in the same step.
