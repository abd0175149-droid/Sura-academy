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
