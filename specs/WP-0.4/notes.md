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
- Zoom REST credentials (2026-10-13): two "API keys" created in Platform Studio (Credentials → API keys, 98-char tokens, scopes for recordings and sessions) were refused with HTTP 401 / code 124 "Invalid access token" on `/videosdk/sessions` and `/videosdk/recordings`, as `Authorization: Bearer` and as `x-api-key`. The "API credentials" Key + Secret on the same page work: the server signs a short JWT (`tools/zoom-recordings.php check` → OK). Another integration snag; the unused Platform Studio key `sura-spike-2` should be disabled at WP close.
- Arabic font: Flutter web shows Arabic as boxes without a bundled font, so the spike bundles IBM Plex Sans Arabic (OFL) under `spikes/video/app/assets/fonts/`.
- Room lock (CLS-04) found during T3: a lock that refused every token also locked out the teacher after a network drop, and with it the only person who can unlock. The lock now refuses non-host roles only (teacher/substitute can rejoin). Worth carrying into WP-3.1.
- Least privilege in the spike: client tokens carry no `roomAdmin`; moderation goes through the spike server, which checks a server-signed admin ticket (teacher/substitute only).

## Measurements so far

### PC baseline (2026-10-12, headless Chromium on the WSL machine, fake camera, not an owner device)
Both keys checked without reading the key file: LiveKit RoomService answered 404 `room does not exist` (auth accepted); a Zoom web bot joined a real session (connected in 6.3–6.6 s).

| Provider | Trials | OK | p50 (s) | p95 (s) | Max (s) | p95 < 10 s |
| --- | --- | --- | --- | --- | --- | --- |
| LiveKit (Flutter web) | 20 | 20 | 2.04 | 2.14 | 2.28 | yes |
| Zoom (Web SDK probe) | 20 | 20 | 5.62 | 7.27 | 7.46 | yes |

First LiveKit run was invalid (my bug, kept as `var/metrics-invalid-*.jsonl`): tiles were filtered on `pub.enabled`, which adaptive stream keeps false until a view is on screen, so no video was ever received. Fixed in 7b4bb00; the second run is the one above.

### 1.5 Mbit/s participant, LiveKit (2026-10-12, PC, preliminary)
Three bots: two on the normal line, one in the `tc`+ifb shaped container (1.5 Mbit/s each way). The shaped bot joined in 1.63 s. Received video went 360p → 180p (simulcast layer switch) and kept 13–20 fps; quality reached "poor" at 16:23:50, the policy switched to audio-only at 16:23:58 (≥ 5 s poor) and back at 16:24:08 (≥ 10 s good). Median received 360p @ 20 fps; downstream 0.56 Mbit/s for two videos. **Caveat:** an unshaped bot reported "poor" at the same moment, so host CPU load (three browsers + container on one machine) confounds this run; the owner-device session decides CLS-14.

### Owner devices, LiveKit session (2026-10-13, owner + helpers, adults, pseudonyms)
- Join time, automatic trials as student: PC Chrome 20/20, p50 1.04 s, p95 1.75 s, max 4.0 s · iPhone Safari 19/20, p50 2.85 s, p95 3.73 s. The one iPhone failure was a spike-app bug (`Concurrent modification during iteration` while awaiting stats inside a loop over live participant maps), fixed by iterating copies. First manual joins: PC 1.3 s, iPhone 3.5–7.0 s, Android app 7.9 s, iPad 9.0 s; one early PC attempt failed with an ICE timeout, then worked.
- Supervisor-role trials on the PC failed to receive video (7 of 9 timed out): the spike app asked to publish a camera the supervisor grant does not allow. Lesson for WP-3.4: the client must derive what it publishes from the role grants. Results are now grouped by role so this does not mix with student numbers.
- Teacher tools: mute all and lock/unlock confirmed by the owner ("تعمل تماما"); a non-host rejoin during lock was refused with «المعلمة أغلقت الغرفة».
- Recording: owner's recording `EG_tsvuJzTfQc36` EGRESS_COMPLETE, 23.4 s MP4 with video and audio, uploaded straight to our bucket (no provider copy). It first failed with S3 SignatureDoesNotMatch: Cloudflare rewrites `Accept-Encoding`, which the AWS SDK for Go (LiveKit egress) signs as `identity`; fixed with an nginx in front of MinIO (spike-only; production uses S3 directly).
- 1.5 Mbit/s participant (container, ~90 s, real room with the owner's devices): joined in 4.2 s; received 360p at ~19 fps from two publishers; quality excellent → good; no audio-only switch needed; 0.67 Mbit/s downstream.
- Disconnect/rejoin (iPhone): webhooks recorded leave 05:28:22 → join 05:28:27 and leave 05:33:23 → join 05:33:34; `AttendanceProbe` merges them (student-3: 19.4 min incl. trials). Finding for WP-3.2: LiveKit recorders join the room as participants (`EG_…` identities) and must be excluded from attendance.

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
