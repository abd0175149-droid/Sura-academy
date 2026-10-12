# Notes — WP-0.4

## Decisions applied
- D-20 (Open): this WP produces the evidence that closes it. ADR-005 tie rule: LiveKit Cloud. Status changes to ADR-005/D-20 only after the owner's explicit approval of the recommendation.
- D-16 (Default): recording copied to private EU storage within 1 h and deleted from the provider within 24 h. Spike proves the mechanics with local MinIO standing in for S3 `eu-central-1`.

## Owner approvals
- 2026-10-12, design and tasks approved via the approval page (https://claude.ai/artifact/TPZyiDjkotwuBEUXn66Xmv). Answers: 1 yes · 2 (a) has a Mac usable for a week, **not available yet** · 3 devices: Android phone, iPhone, iPad (plus the Windows PC with Chrome) · 4 three adult helpers available · 5 tools outside the repo approved (`cloudflared`, JDK + Android SDK in `~/tools`, Docker images) · 6 spike-only dependencies approved (`livekit_client`, `flutter_zoom_videosdk`, `http`, `@zoom/videosdk`) · 7 Zoom web probe approved.
- Consequence of answer 2/3: iOS native builds (LiveKit and Zoom) wait for the Mac window; everything else proceeds. iPhone/iPad are measured in Safari first.

## Deviations / surprises
- Provider facts checked 2026-10-12 (sources in design.md §8): Zoom has no Flutter web plan; Zoom JWT `exp` must be ≥ 30 min after `iat`, which weakens "short-lived token" (CLS-03); LiveKit Cloud stores no recordings (egress needs our bucket); LiveKit EU media pinning needs the Scale plan; Zoom free tier is 20 credits (≈ 5,700 participant-minutes), not the 10,000 min/month quoted in spec/16.
- Account facts checked 2026-10-12 (second research pass): LiveKit's project "data region" covers analytics/logs only, not media; EU media routing = region pinning on Scale (support request). LiveKit Build plan needs no card and hard-caps at 5,000 participant-min and 60 recording (transcode) min. Zoom Build Platform: new account required; trial = 20 credits (checkout says one-time, 12-month expiry; another Zoom page says monthly); SDK key/secret, API key/secret and webhook Secret Token live in the legacy Build portal; recording storage location can be Germany; `DELETE …/recordings?action=delete` is permanent (`trash` keeps 30 days).
- T4: `livekit_client` 2.13.1 compiles for Flutter web both as JS and as WebAssembly (`flutter build web --wasm --no-web-resources-cdn`), and the built app makes no request outside its own origin (Playwright network log, 2026-10-12). Evidence for WP-0.5 / ADR-010.
- Zoom on Android (T6/T9, 2026-10-12), integration cost visible before any call:
  1. `flutter_zoom_videosdk` 2.6.0 declares the Zoom SDK as `implementation`, so the app's generated plugin registrant does not compile (`cannot access ZoomVideoSDKDelegate`); fix: declare `us.zoom.videosdk:zoomvideosdk-core:2.6.0` in the app too.
  2. R8 (release shrinking) fails on classes the Zoom SDK and Tink reference but do not ship (`javax.annotation.*`, `javax.lang.model.*`); a product build needs `-dontwarn`/keep rules, the spike disables shrinking.
  3. Size: the arm64 APK is 219 MB unshrunk; of ~166 MB native libraries, roughly 130 MB are Zoom's (`libprotoc` 24 MB, `libzVideoApp` 19 MB, `libviper_neon`, `libssl_sb`, `libcrypto_sb`, `libzWebService`, `libssb_sdk`, …) versus ~12 MB for LiveKit's WebRTC (`libjingle_peerconnection`).
  3b. Same app without `flutter_zoom_videosdk` (LiveKit only, same build settings, arm64, unshrunk): **37.4 MB** vs 219 MB, and only the permissions the app declares plus `BLUETOOTH` (maxSdk 30) from WebRTC.
  4. Permissions merged in by the Zoom SDK that the spike never asked for: `READ_PHONE_STATE`, `SYSTEM_ALERT_WINDOW`, `BLUETOOTH_SCAN`, `ACCESS_WIFI_STATE`, `BROADCAST_STICKY`, `WAKE_LOCK`. `READ_PHONE_STATE` and `SYSTEM_ALERT_WINDOW` are sensitive for a children's app (ADR-009, child-privacy) and would need `tools:node="remove"` plus a test that Zoom still works.
- Arabic font: Flutter web shows Arabic as boxes without a bundled font, so the spike bundles IBM Plex Sans Arabic (OFL) under `spikes/video/app/assets/fonts/`.
- Room lock (CLS-04) found during T3: a lock that refused every token also locked out the teacher after a network drop, and with it the only person who can unlock. The lock now refuses non-host roles only (teacher/substitute can rejoin). Worth carrying into WP-3.1.
- Least privilege in the spike: client tokens carry no `roomAdmin`; moderation goes through the spike server, which checks a server-signed admin ticket (teacher/substitute only).

## Verification evidence
```
(paste verify --all summary)
```
| Acceptance criterion | Evidence |
| --- | --- |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
1. …
