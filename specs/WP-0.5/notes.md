# Notes — WP-0.5

## Approval
- 2026-10-13, design and tasks approved via the approval page (https://claude.ai/artifact/1FXNBHetbZJhHTYnyHjobK). Answers: 1 yes · 2 (a) the owner borrows a Chromebook for one session · 3 (a) Claude runs an Android emulator for the screenshots · 4 (b) iPad video under the headers too, using the **existing WP-0.4 LiveKit Cloud key** (not revoked yet; the spike server reads it from `spikes/video/token-server/.env` at runtime, Claude never opens the file) · 5 tools and spike dependencies approved · 6 gate change approved.

## Decisions applied
- D-22 (Default, needs CR-003): measured against splash LCP ≤ 2.5 s, interactive ≤ 5 s first load / ≤ 2 s later loads; final numbers proposed to the owner.
- ADR-010 (Proposed): every initial setting exercised; status changes only with the owner's explicit approval.

## Sources
- Noto Naskh Arabic v2.021, unhinted TTF, https://github.com/notofonts/arabic/releases/download/NotoNaskhArabic-v2.021/NotoNaskhArabic-v2.021.zip (OFL). Regular `c34fdbd98af4dbc45ca192a23d2eeb77032add83086f5fe32957d23b3f36b221`, SemiBold `85c6a3c50b19179fe26f550d4e9a658cd8f0e74b25816e6da46b2ba8cdbc7673`. The unhinted build has no Latin letters (only digits and punctuation); content styles fall back to IBM Plex Sans Arabic via `fontFamilyFallback`.
- IBM Plex Sans Arabic 1.1.0, https://github.com/IBM/plex/releases/download/%40ibm/plex-sans-arabic%401.1.0/ibm-plex-sans-arabic.zip (OFL). Regular `8e0f1046c736bf939d4939ee3ae0116acf61cbcd6592deae7656761627080981`, Medium `eef162792cf2a6ba5af7943af9f86843b1286ab67aaf615c18c07fd8e97a4a90`, SemiBold `1d4ab8b6ebadbb9d85f2ecdcb7cb0bb672420a8ff3388ab4f69d7b98056562aa`, Bold `b74f809dead12442ed56e02a12c3bcc02076c9ad4e32f17d0a9ca6fc1aafc89e`.
- Spike dependencies: `livekit_client` 2.13.1, `web` 1.1.1, `http` 1.6.0; Docker `livekit/livekit-server:v1.13.7`. Flutter 3.47.7 / Dart 3.13.5. Headless Chromium 156 (Playwright 1.64.0 from `scripts/web`).

## Deviations / surprises
- **`<html dir="rtl">` breaks Flutter's semantics DOM.** Flutter web places its screen-reader nodes (`flt-semantics`) as absolutely positioned elements without an explicit inline start; under an RTL root they resolve from the right edge, so every node's box lands about twice its real offset (tabs measured at x≈2006–2446 in a 1280 px viewport; with `dir="ltr"` on the root they sit at 813–1280, where they are drawn). Fix in the spike: `<html lang="ar">` without `dir`, `dir="rtl"` only on the HTML splash. **`apps/family/web/index.html` has the same `dir="rtl"`** (and `apps/teacher` likely too): recorded for ADR-010 and WP-1.6, not changed here (outside this WP).
- Flutter web downloads **every family in FontManifest.json at start-up** (4 Plex weights, 2 Noto weights, Material Icons) and, with `--no-web-resources-cdn`, a bundled **Roboto** as its default fallback: 535 KB brotli of the ~2.5 MB cold load. Lever for WP-1.6: fewer weights.
- Missing glyphs (emoji, ☪) make Flutter request Noto Color Emoji / Noto Sans Symbols from `fontFallbackBaseUrl`. With it on our origin the requests stay local (404, the glyph shows as a box); with Flutter's default they would go to fonts.gstatic.com. The gate now fails on the default (T9).
- The flutter.js loader in 3.47 has `wasmAllowList` (default: Chromium only) and `forceSingleThreadedSkwasm`; without COOP/COEP skwasm runs single-threaded. Variant `wasmx` opens WebAssembly to Safari for an iPad experiment.
- CSP: the report-only candidate without `blob:` reports `worker-src blob:` (skwasm's multi-threaded worker), so the final policy needs `worker-src 'self' blob:`. Styles work with a per-load nonce (`config.nonce`), no `'unsafe-inline'`.
- Payment return bug caught by the checks: `Uri.replace(fragment: '')` leaves a trailing `#`, and the gateway's `&status=…` landed in the fragment. Fixed in the app (`removeFragment()`) and the server (query inserted before any fragment, tested).
- The diacritics `Switch` and its label merged with the neighbouring size control into one semantics node; fixed with `MergeSemantics` and a `container` for the size control (test `spike NFR-a11y diacritics switch is one labelled toggle`).
- Tap-to-first-input in headless Chromium sometimes needs a second tap under load (Flutter had not attached handlers yet); `measure.mjs` retries and records failures instead of aborting.

## Owner device sessions (2026-10-13)
- **Android phone (Chrome 152 and the spike APK):** APK installed from the start page; two screenshots (scale 1.0, 2.0) arrived. Auto-run completed `wasm/iso` and `wasm/plain` (5 cold + 5 warm each) and one `js/iso` cold load (41.8 s, screen most likely locked; run stopped there). A second Chromium 138 user agent posted loads without paint marks and with uncompressed transfer sizes (7.8 MB); excluded from the analysis until the owner identifies it (likely an in-app browser).
- **iPad (Safari 27, reports as Macintosh):** auto-run complete, 40 loads (every variant runs the JS build: WebAssembly is Chromium-only). First screenshot attempts failed with 422: dart2js prints `1.0` as `"1"` and the server accepted only `1.0`. Fixed on the server (normalized, test `spike CNT-08 shot scale is accepted as JavaScript prints it`) and in the app (`toStringAsFixed(1)`); retried shots arrived. Video under COOP/COEP with LiveKit Cloud worked both ways (iPad ↔ headless Chromium bot, `tools/video-bot.mjs`).
- **iPad checklist answers:** q1 yes (VoiceOver reads Arabic with an Arabic voice) · **q2 no (field and button names not read in Arabic)** · **q3 no (list items not read one by one)** · q4 yes (Arabic keyboard) · q5 yes (cursor) · q6 yes (diacritics, no overlap or clipping) · q7 yes (installed, opens without the Safari bar) · q8 yes (payment redirect returns to the installed app) · q9 yes (video with isolation headers). Detail of q2/q3 asked from the owner.
- **VoiceOver follow-up declined (owner, 2026-10-13):** «الفويس اوفر الي من الجهاز مزعج وما بشتغل بشكل مناسب وبقرا كل شي، بفضل يكون في زر عند الضغط عليه يقرا هذا الجزء تحديدا». q2/q3 stay unexplained; the screen-reader gap is recorded as a risk for WP-1.6 / WP-5.4 (spec NFR: WCAG 2.2 AA + manual Arabic screen-reader check, which a read-aloud button does not replace). The read-aloud button itself is a new feature (closest spec rows: CNT-02 recorded pronunciation audio, R1; CNT-14 machine Arabic TTS for content production, R3), so it needs a CR; offered to the owner as a candidate CR-005.
- **Read-aloud probe (owner's request after declining VoiceOver):** iPad offers one Arabic voice, «Majed» (`ar-001`, on device; listed twice). Checklist q10–q12 yes (clear, vocalized words pronounced, class time and link understandable), but the owner's verdict: «الصوت غير مناسب ابدا واضح انه صوت الي وغير مناسب لتعليم اللغة العربيه». No CR for device text-to-speech. A «استمع» control, if wanted, plays teacher-recorded audio, which the spec already has in R1 (CNT-02 «صوت نطق»): input for WP-2.1 / WP-4.5 design, no CR.
- **Diacritics:** iPad Safari (CanvasKit) and Android (native) render the vocalized samples the same; on the web the emoji and ☪ are boxes (fallback fonts not hosted), on Android they show (system fonts).
- **Emulator:** `/dev/kvm` is not usable by this user (not in group `kvm`; needs a one-time admin command). The real phone's screenshots cover the Android side; emulator shots skipped unless the owner wants them.

## Verification evidence
```
(paste verify --all summary)
```
| Acceptance criterion | Evidence |
| --- | --- |
| ADR-010 Accepted with final settings | |
| D-22 numbers proposed to the owner (CR-003 approve or amend) | |
| Written decision on the isolation headers | |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
1. …
