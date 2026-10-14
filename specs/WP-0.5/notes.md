# Notes — WP-0.5

## Approval
- 2026-10-13, design and tasks approved via the approval page (https://claude.ai/artifact/1FXNBHetbZJhHTYnyHjobK). Answers: 1 yes · 2 (a) the owner borrows a Chromebook for one session · 3 (a) Claude runs an Android emulator for the screenshots · 4 (b) iPad video under the headers too, using the **existing WP-0.4 LiveKit Cloud key** (not revoked yet; the spike server reads it from `spikes/video/token-server/.env` at runtime, Claude never opens the file) · 5 tools and spike dependencies approved · 6 gate change approved.

- 2026-10-13, decisions approved via the decisions page (https://claude.ai/artifact/9YoafxShs3Xebpw6pogyUD): «1 نعم · 2 أ · 3 أ · 4 أ · 5 أ» → 1 ADR-010 settings accepted · 2 isolation headers stay off · 3 CR-003 numbers approved as proposed · 4 host Noto Color Emoji / Noto Sans Symbols on our origin (WP-1.6) · 5 close without the Chromebook; it is measured in WP-5.3. Applied: ADR-010 → Accepted, CR-003 → Approved, D-22 → Approved (detail added), status.md approvals row.

## Decisions applied
- D-22: measured against its Default (splash ≤ 2.5 s, interactive ≤ 5 s first / ≤ 2 s later); numbers proposed through CR-003 and **Approved** by the owner (2026-10-13, «3 أ»).
- ADR-010: every initial setting exercised; **Accepted** with final settings (owner «1 نعم», 2026-10-13).
- D-34 (Approved): the runtime third-party gate; extended here with a static check of the built bootstrap (T9).
- D-32 (Approved): the dev machine is for development only and holds no real family or child data; the spike ran there with adults, pseudonymous device labels and test text only.

## Sources
- Noto Naskh Arabic v2.021, unhinted TTF, https://github.com/notofonts/arabic/releases/download/NotoNaskhArabic-v2.021/NotoNaskhArabic-v2.021.zip (OFL). Regular `c34fdbd98af4dbc45ca192a23d2eeb77032add83086f5fe32957d23b3f36b221`, SemiBold `85c6a3c50b19179fe26f550d4e9a658cd8f0e74b25816e6da46b2ba8cdbc7673`. The unhinted build has no Latin letters (only digits and punctuation); content styles fall back to IBM Plex Sans Arabic via `fontFamilyFallback`.
- IBM Plex Sans Arabic 1.1.0, https://github.com/IBM/plex/releases/download/%40ibm/plex-sans-arabic%401.1.0/ibm-plex-sans-arabic.zip (OFL). Regular `8e0f1046c736bf939d4939ee3ae0116acf61cbcd6592deae7656761627080981`, Medium `eef162792cf2a6ba5af7943af9f86843b1286ab67aaf615c18c07fd8e97a4a90`, SemiBold `1d4ab8b6ebadbb9d85f2ecdcb7cb0bb672420a8ff3388ab4f69d7b98056562aa`, Bold `b74f809dead12442ed56e02a12c3bcc02076c9ad4e32f17d0a9ca6fc1aafc89e`.
- Spike dependencies: `livekit_client` 2.13.1, `web` 1.1.1, `http` 1.6.0; Docker `livekit/livekit-server:v1.13.7`. Flutter 3.47.7 / Dart 3.13.5. Headless Chromium 156 (Playwright 1.64.0 from `scripts/web`).

## Deviations / surprises
- **`<html dir="rtl">` breaks Flutter's semantics DOM.** Flutter web places its screen-reader nodes (`flt-semantics`) as absolutely positioned elements without an explicit inline start; under an RTL root they resolve from the right edge, so every node's box lands about twice its real offset (tabs measured at x≈2006–2446 in a 1280 px viewport; with `dir="ltr"` on the root they sit at 813–1280, where they are drawn). Fix in the spike: `<html lang="ar">` without `dir`, `dir="rtl"` only on the HTML splash. **`apps/family/web/index.html` and `apps/teacher/web/index.html` have the same `dir="rtl"`** (both confirmed): recorded for ADR-010 and WP-1.6, not changed here (outside this WP).
- Flutter web downloads **every family in FontManifest.json at start-up** (4 Plex weights, 2 Noto weights, Material Icons) and, with `--no-web-resources-cdn`, a bundled **Roboto** as its default fallback: 535 KB brotli of the ~2.5 MB cold load. Lever for WP-1.6: fewer weights.
- Missing glyphs (emoji, ☪) make Flutter request Noto Color Emoji / Noto Sans Symbols from `fontFallbackBaseUrl`. With it on our origin the requests stay local (404, the glyph shows as a box); with Flutter's default they would go to fonts.gstatic.com. The gate now fails on the default (T9).
- The flutter.js loader in 3.47 has `wasmAllowList` (default: Chromium only) and `forceSingleThreadedSkwasm`; without COOP/COEP skwasm runs single-threaded. Variant `wasmx` (WebAssembly opened to Safari) exists in the server and start page but **was not run** on the iPad; ADR-010 §1 keeps Flutter's default (Chromium only).
- CSP: the report-only candidate without `blob:` reports `worker-src blob:` (skwasm's multi-threaded worker), so the final policy needs `worker-src 'self' blob:`. Styles work with a per-load nonce (`config.nonce`), no `'unsafe-inline'`.
- Payment return bug caught by the checks: `Uri.replace(fragment: '')` leaves a trailing `#`, and the gateway's `&status=…` landed in the fragment. Fixed in the app (`removeFragment()`) and the server (query inserted before any fragment, tested).
- The diacritics `Switch` and its label merged with the neighbouring size control into one semantics node; fixed with `MergeSemantics` and a `container` for the size control (test `spike NFR-a11y diacritics switch is one labelled toggle`).
- Tap-to-first-input in headless Chromium sometimes needs a second tap under load (Flutter had not attached handlers yet); `measure.mjs` retries and records failures instead of aborting.

## Owner device sessions (2026-10-13)
- **Android phone (Chrome 152 and the spike APK):** APK installed from the start page; two screenshots (scale 1.0, 2.0) arrived. Auto-run completed `wasm/iso` and `wasm/plain` (5 cold + 5 warm each; device rows have n = 4–5 after leaving out loads that never painted) and one `js/iso` cold load (41.8 s, screen most likely locked; run stopped there). A second Chromium 138 user agent posted loads without paint marks and with uncompressed transfer sizes (7.8 MB); excluded from the analysis until the owner identifies it (likely an in-app browser).
- **iPad (Safari 27, reports as Macintosh):** auto-run complete, 40 loads (every variant runs the JS build: WebAssembly is Chromium-only). First screenshot attempts failed with 422: dart2js prints `1.0` as `"1"` and the server accepted only `1.0`. Fixed on the server (normalized, test `spike CNT-08 shot scale is accepted as JavaScript prints it`) and in the app (`toStringAsFixed(1)`); retried shots arrived. Video under COOP/COEP with LiveKit Cloud worked both ways (iPad ↔ headless Chromium bot, `tools/video-bot.mjs`).
- **iPad checklist answers:** q1 yes (VoiceOver reads Arabic with an Arabic voice) · **q2 no (field and button names not read in Arabic)** · **q3 no (list items not read one by one)** · q4 yes (Arabic keyboard) · q5 yes (cursor) · q6 yes (diacritics, no overlap or clipping) · q7 yes (installed, opens without the Safari bar) · q8 yes (payment redirect returns to the installed app) · q9 yes (video with isolation headers). Detail of q2/q3 asked from the owner.
- **VoiceOver follow-up declined (owner, 2026-10-13):** «الفويس اوفر الي من الجهاز مزعج وما بشتغل بشكل مناسب وبقرا كل شي، بفضل يكون في زر عند الضغط عليه يقرا هذا الجزء تحديدا». q2/q3 stay unexplained; the screen-reader gap is recorded as a risk for WP-1.6 / WP-5.4 (spec NFR: WCAG 2.2 AA + manual Arabic screen-reader check, which a read-aloud button does not replace). The read-aloud button itself is a new feature (closest spec rows: CNT-02 recorded pronunciation audio, R1; CNT-14 machine Arabic TTS for content production, R3), so it needs a CR; offered to the owner as a candidate CR-005.
- **Read-aloud probe (owner's request after declining VoiceOver):** iPad offers one Arabic voice, «Majed» (`ar-001`, on device; listed twice). Checklist q10–q12 yes (clear, vocalized words pronounced, class time and link understandable), but the owner's verdict: «الصوت غير مناسب ابدا واضح انه صوت الي وغير مناسب لتعليم اللغة العربيه». No CR for device text-to-speech. A «استمع» control, if wanted, plays teacher-recorded audio, which the spec already has in R1 (CNT-02 «صوت نطق»): input for WP-2.1 / WP-4.5 design, no CR.
- **Diacritics:** iPad Safari (CanvasKit) and Android (native) render the vocalized samples the same; on the web the emoji and ☪ are boxes (fallback fonts not hosted), on Android they show (system fonts).
- **Emulator:** `/dev/kvm` is not usable by this user (not in group `kvm`; needs a one-time admin command). The real phone's screenshots cover the Android side; emulator shots skipped unless the owner wants them.

## Spec text for the owner (`spec/18`, row «الأداء»), after CR-003
Human-only file; the owner pastes this in place of the current performance row:

```
| الأداء | الموقع العام ولوحة الإدارة: تحميل الصفحة الرئيسية أقل من 2.5 ثانية (LCP) على جهاز متوسط واتصال 4G. تطبيق الأسرة وبوابة المعلمة (Flutter على الويب، CR-003): الشاشة الافتتاحية أقل من 2.5 ثانية (LCP)، والتطبيق جاهز للتفاعل خلال 5 ثوانٍ في التحميل الأول و2 ثانية في التحميل اللاحق على الأجهزة المرجعية (iPad، هاتف Android متوسط، كروم بوك) وفي المختبر لمسار WebAssembly، وخلال 6 ثوانٍ و3.5 ثانية لمسار JavaScript في المختبر؛ القيم عند 75% من 10 تحميلات على الأقل، والمختبر Chrome بملف «Fast 4G» ومعالج أبطأ 4 مرات؛ زمن استجابة API عند 95% من الطلبات أقل من 800 ملّي ثانية دون التكاملات | اختبار حمل ومراقبة مستمرة؛ قياس المختبر والأجهزة المرجعية |
```

## Hand-offs
- **WP-1.6 (from the RTL review):** per-block `Directionality`/`Text.locale`/`localeForSubtree` for Arabic content; `VisualDensity.standard` + `MaterialTapTargetSize.padded`; `<html lang>` follows the locale, and the splash, `<title>` and manifest get English variants; Arabic text styles at line height ≥ 1.6; explicit font-weight mapping (Material 3 asks for w500/w700).
- **WP-1.6:** remove `dir="rtl"` from `apps/family/web/index.html` and `apps/teacher/web/index.html`, keep `lang="ar"`; host Noto Color Emoji and Noto Sans Symbols under the app's `fontFallbackBaseUrl` (owner «4 أ»); ship IBM Plex Sans Arabic 400/600 and Noto Naskh Arabic 400 only; adopt the spike's bootstrap marks, HTML splash, service worker and CSP (ADR-010 §5–7); investigate the VoiceOver gaps (control names, list navigation) on iPad.
- **WP-1.8 (hosting):** no COOP/COEP; serve `index.html` templated per request (CSP nonce in the header and in `<meta name="…nonce">` for `config.nonce`); `Cache-Control: no-cache` + `ETag` for build files (names are not content-hashed); brotli. Its deliverable «COOP/COEP حسب ADR-010» now reads: none.
- **WP-5.4:** VoiceOver on iPad read Arabic text but not control names, and did not step through list items (q2/q3 no); audit with a specialist.
- **WP-0.6 (PayTabs):** a full-page redirect to the hosted page and back works, including from the installed iPad app; with isolation headers off, popups and iframes work too.
- **WP-5.3:** measure the Chromebook (owner «5 أ») and re-run CR-003's two setups; move `tools/measure.mjs` to `scripts/web`.
- **WP-2.1 / WP-4.5:** a «استمع» control, if wanted, plays teacher-recorded audio (CNT-02), not device text-to-speech (owner's verdict).
- **Owner:** revoke the WP-0.4 LiveKit Cloud key now that WP-0.5 no longer needs it (still on the WP-0.4 to-do list).

## Verification evidence
`bash scripts/verify.sh --all` (2026-10-13, branch `wp/WP-0.5-flutter-web-spike`):
```
──────── verify summary (--all) ────────
✅ api:pint
✅ api:phpstan
✅ api:tests+coverage(80)
✅ dart:format
✅ dart:analyze
✅ flutter:test:packages/sura_core
✅ flutter:test:apps/family
✅ flutter:test:apps/teacher
✅ web:build:apps/family
✅ web:no-third-party-requests:apps/family
✅ web:build:apps/teacher
✅ web:no-third-party-requests:apps/teacher
✅ guards
✅ traceability
✅ traceability:selftest
✅ roadmap
```
Spike gates (outside `verify.sh`): `dart format --set-exit-if-changed` ok · `flutter analyze` no issues · `flutter test` 11 passed · `php server/tests/run.php` 20 passed · `check-no-third-party.mjs` on the spike WASM build: bootstrap ok, wasm 20 and js 20 requests, all to our origin.

| Acceptance criterion | Evidence |
| --- | --- |
| ADR-010 Accepted with final settings | `docs/02-architecture/adr/ADR-010-flutter-web-delivery.md`: status Accepted (owner «1 نعم», 2026-10-13); measured table and decisions §1–8 |
| D-22 numbers proposed to the owner (CR-003 approve or amend) | `results/summary.md`, `load-times.csv`, `pc-interaction*.csv`; decisions page https://claude.ai/artifact/9YoafxShs3Xebpw6pogyUD; owner «3 أ» → CR-003 Approved, D-22 Approved; spec text for `spec/18` above |
| Written decision on the isolation headers | ADR-010 §3 (off) with `results/isolation.md` (popup and cross-origin iframe break under COOP/COEP; redirect and LiveKit work either way) and the p75 comparison (no start-up gain); owner «2 أ» |
| Deliverable 1: vocalized page, two fonts, 16/24/32, diacritics switch | `app/lib/src/reading/`; tests `spike CNT-08 …` (4); iPad q6 yes |
| Deliverable 2: Arabic form, list, button, semantics on | `app/lib/src/form/`; `results/a11y.md`; test `spike NFR-a11y …` (2); iPad q4/q5 yes, q2/q3 no (risk recorded) |
| Deliverable 3: WASM and JS builds, with and without COOP/COEP | `tools/build.sh`, `server/src/BuildFiles.php`; tests `spike NFR-isolation …` (2) |
| Deliverable 4: splash, interactive cold/warm, bundle size | `results/summary.md` (240 PC loads + 40 iPad + 19 Android), `results/sizes.csv` |
| Deliverable 5: payment redirect and return, both header modes | `results/isolation.md`; iPad q8 yes (from the installed app) |
| Deliverable 6: install with a custom service worker | `app/web/sw.js`, `manifest.json`; warm loads 0 KB downloaded; iPad q7 yes |
| Deliverable 7: diacritics screenshots, web vs Android | `results/shots/ipad-safari-js-scale-1.0.png`, `results/shots/android-app-scale-1.0.png` (same shaping; web shows emoji as boxes) |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |
| traceability-checker | BLOCKER | `rtl-reviewer` not run (its first run stopped on a usage limit) | Re-run 2026-10-14; findings below |
| traceability-checker | MAJOR | CR-003 said the first real tap succeeds 0.2–0.5 s after `sura-interactive`; true only unthrottled (lab p75: 1.1 s WASM, 1.7 s JS, tab switch and tooling included) | Fixed: wording correction in CR-003 (numbers unchanged), row added to ADR-010's table, first-tap measurement handed to WP-5.3; owner told |
| traceability-checker | MINOR | Summary showed p50/p95 while CR-003 judges p75; device n below 10 | Fixed: `summarize.php` prints p50/p75/p95 and a «Meets CR-003 at p75» column; CR-003 states device n = 4–10 |
| traceability-checker | MINOR | Descriptive figures in CR-003 drifted from p75 (slow 4G warm JS, iPad cold, Android splash) | Fixed in CR-003 and ADR-010 |
| traceability-checker | MINOR | notes «Decisions applied» stale; D-34 and D-32 not cited | Fixed |
| traceability-checker | MINOR | T12 unticked | Fixed |
| traceability-checker | MINOR | `wasmx` never measured | Recorded as not run (above); ADR keeps Flutter's default |
| traceability-checker | MINOR | ADR-010 claimed iPad video in both header modes | Fixed: iPad with isolation here, without it in WP-0.4 |
| traceability-checker | MINOR | CSP «isolation builds only» not visible in committed results | Fixed: CSP reports summarized per variant (`wasm/iso` ×90 only) |
| traceability-checker | MINOR | Hand-offs missing WP-1.8 and WP-5.4; `apps/teacher` `dir` unconfirmed | Fixed |
| traceability-checker | MINOR | Risk R-05 trigger met | Fixed: R-05 row updated in `docs/03-plan/risks.md` |
| traceability-checker | MINOR | «استمع» probe not in design.md | Fixed: addendum in design.md §6 |
| traceability-checker | MINOR | D-22 status changed (rule says never change an existing decision's status) | Kept: changed only after the owner's explicit «3 أ», as WP-0.4 did for D-20 |
| traceability-checker | MINOR | Scale 2.0 screenshots not committed | Fixed: `results/shots/*-scale-2.0.png` |
| traceability-checker | MINOR | Decisions page is outside the repo | Accepted: replies quoted in notes |
| rtl-reviewer | MAJOR | Arabic content blocks used the ambient direction: under `?lang=en` the ornate brackets and full stop moved | Fixed: `Directionality(rtl)` + `Text.locale` + `Semantics.localeForSubtree` per content block; test `spike CNT-08 Arabic samples stay right-to-left under the English UI`; rule in ADR-010 §4 |
| rtl-reviewer | MAJOR | Desktop platforms (a Chromebook reports linux) get compact density: buttons 32 px | Fixed: `VisualDensity.standard` + `MaterialTapTargetSize.padded`; test `spike NFR-a11y touch targets stay at least 44 px on desktop platforms` (failed at 32 px before the fix); rule in ADR-010 §4 |
| rtl-reviewer | MAJOR | Static `<html lang="ar">` while the UI can be English; splash, title and manifest Arabic-only | Fixed in the spike: `setDocumentLang` on locale (verified `lang=en`); ADR-010 §4 rule; splash/title/manifest per locale handed to WP-1.6 |
| rtl-reviewer | MAJOR | ADR said VoiceOver «did not read names»; q2 asked «in Arabic», cause unknown, one device | Fixed: ADR-010 §4 reworded (answers, unknown cause, one device, follow-up declined, tree verified in Chromium) |
| rtl-reviewer | MAJOR (verify) | Bundled fonts lack FSI/PDI: would every isolate fetch a fallback font? | Verified 2026-10-14: with `?fallback=0` (isolates, no emoji) zero fallback requests on WASM and JS; the `notosanssymbols` request comes from ☪. Recorded in ADR-010 §2 |
| rtl-reviewer | MINOR | Name field forces RTL | Deferred: spike; WP-1.6 text fields follow the input language (arabic-rtl-ui skill) |
| rtl-reviewer | MINOR | Line heights below 1.6 on some text styles | Deferred: WP-1.6 theme sets ≥ 1.6 on every Arabic style |
| rtl-reviewer | MINOR | Voice label concatenated, not isolated; dropdown unlabeled | Deferred: probe rejected by the owner; not carried forward |
| rtl-reviewer | MINOR | Measure values forced LTR although some are Arabic words | Deferred: spike-only debug screen |
| rtl-reviewer | MINOR | Feedback numbering concatenated; yes/no groups unlabeled | Deferred: spike-only checklist; WP-1.6 uses ARB messages and group labels |
| rtl-reviewer | MINOR | Dynamic Latin values (status, errors, font ids) not isolated | Deferred: WP-1.6 isolate helper; friendly font names were not needed in a spike |
| rtl-reviewer | MINOR | `Europe/Berlin` wraps after «/» at 32 px | Handed to WP-1.6/WP-2.2: show the friendly zone label (PAR-04) as one no-wrap run |
| rtl-reviewer | MINOR | Hide-diacritics keeps alef wasla; stripping U+0670 misspells ٱلْعَٰلَمِينَ | Handed to WP-2.1/WP-4.5: Quranic content is excluded from «hide diacritics» (decision there) |
| rtl-reviewer | MINOR | Overflow test asserts exceptions only; no goldens | Accepted for a spike; scale 2.0 screenshots committed; WP-1.6 uses `pumpGolden` |
| rtl-reviewer | MINOR | iframe title, video tile and spinner labels missing | Deferred: spike-only probes |
| rtl-reviewer | MINOR | Server pages Arabic-only, small cancel link, `WP-0.5` not isolated, no plural | Deferred: spike-only pages |
| rtl-reviewer | MINOR | Two-weight font setup never rendered; Noto content needs a Plex fallback | Fixed in ADR-010 §2 and §8 (weights mapped explicitly in WP-1.6, skill table update) |
| rtl-reviewer | MINOR | Hyphenated IDs (CR-003) may show reversed in RTL paragraphs | No change: a Latin letter run followed by «-» and digits resolves left-to-right (bidi W7/N1), and repo docs use the same style |

## خطوات العرض اليدوي
1. اطلب من Claude: «جهّز محطة Flutter على الآيباد». يشغّل `bash spikes/flutter-web/tools/serve.sh --tunnel --livekit` ويعطيك روابط البداية (الآيباد، الهاتف، الحاسوب).
2. افتح رابط الآيباد في Safari واضغط «تشغيل القياس تلقائياً». المتوقع بعد نحو 15 دقيقة: «انتهى القياس»، والأرقام في `spikes/flutter-web/server/var/metrics.jsonl`.
3. افتح «النسخة العادية دون ترويستي العزل»: نص مشكول بخطين وثلاثة أحجام، وزر «إظهار التشكيل» يخفي الحركات ويعيدها، و«الأكبر» يكبّر النص دون قص.
4. تبويب «النموذج»: اكتب اسماً من خيالك بلوحة المفاتيح العربية، واضغط «اضغط هنا». المتوقع: «ضغطت مرة واحدة».
5. ثبّت التطبيق من زر المشاركة، ثم من التطبيق المثبّت: «الدفع» ← «انتقال كامل إلى صفحة الدفع» ← «ادفع». المتوقع: «نتيجة الدفع: تم الدفع».
6. على الحاسوب: `node spikes/flutter-web/tools/measure.mjs --checks` يكتب `results/isolation.md`. المتوقع: مع ترويستي العزل تفشل النافذة المنبثقة والإطار دون ترويسات، وتعمل البقية.
7. `php spikes/flutter-web/tools/summarize.php` يكتب `results/summary.md` بعمود p75 وحكم CR-003 لكل حالة.
8. أوقف كل شيء: `bash spikes/flutter-web/tools/stop.sh`.
