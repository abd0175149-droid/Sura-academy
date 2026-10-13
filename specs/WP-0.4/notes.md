# Notes — WP-0.4

## Decisions applied
- D-20 (Open): this WP produces the evidence that closes it. ADR-005 tie rule: LiveKit Cloud. Status changes to ADR-005/D-20 only after the owner's explicit approval of the recommendation.
- D-16 (Default): recording copied to private EU storage within 1 h and deleted from the provider within 24 h. Spike proves the mechanics with local MinIO standing in for S3 `eu-central-1`.

- D-32 (Approved, lists WP-0.4; noticed 2026-10-13, not in the WP row): the development machine is for development and preview only, no real family or child data. Applied: the spike ran there with adult testers under pseudonyms, test content only; recordings stay local and are purged at WP close.
- LiveKit remove participant verified server-side (2026-10-13): bot-7 joined 06:39:15, `/admin/remove` → left 06:39:17 and did not return (token revoked on Cloud).

- CI (PR #6, 2026-10-13): the `dart` job failed because `dart:analyze` ran on `.` and reached `spikes/video/app`, whose packages CI never fetches (it passed locally only because the spike's `.dart_tool` existed). `scripts/verify.sh` now analyzes `apps packages`, the same scope as `dart:format`; verified with the spike's `.dart_tool` moved aside. Product checks are unchanged; the spike keeps its own gates.

## Owner approvals
- 2026-10-12, design and tasks approved via the approval page (https://claude.ai/artifact/TPZyiDjkotwuBEUXn66Xmv). Answers: 1 yes · 2 (a) has a Mac usable for a week, **not available yet** · 3 devices: Android phone, iPhone, iPad (plus the Windows PC with Chrome) · 4 three adult helpers available · 5 tools outside the repo approved (`cloudflared`, JDK + Android SDK in `~/tools`, Docker images) · 6 spike-only dependencies approved (`livekit_client`, `flutter_zoom_videosdk`, `http`, `@zoom/videosdk`) · 7 Zoom web probe approved.
- 2026-10-13, decision page https://claude.ai/artifact/3PMLXBqacfeFS62dVDqseR. Owner: «نعم خلص اعتمد استخدام livekit … اغلق الحزمة ما في داعي لقياس تطبيق الايفون». Applied: ADR-005 → Accepted with the measured numbers; D-20 → Closed (status changed by Claude on this explicit approval, as announced on the approval page); iOS native not measured, waived by the owner. Question 3 (new D-35) was not answered; D-35 was appended as Open per the rules (Claude may add Open rows), for the owner and the legal adviser before W12.
- Per-student cost requested by the owner (8 × 60 min, 4–5 students + teacher + supervisor ~5 min): LiveKit Ship ~0.63–0.75 $/student/month without recording, 2.5–3.1 $ with every class recorded; Zoom 2.04–2.14 $ and 3.00–3.33 $.
- Consequence of answer 2/3: iOS native builds (LiveKit and Zoom) wait for the Mac window; everything else proceeds. iPhone/iPad are measured in Safari first.

## Deviations / surprises
- Provider facts checked 2026-10-12 (sources in design.md §8): Zoom has no Flutter web plan; Zoom JWT `exp` must be ≥ 30 min after `iat`, which weakens "short-lived token" (CLS-03); LiveKit Cloud stores no recordings (egress needs our bucket); LiveKit EU media pinning needs the Scale plan; Zoom free tier is 20 credits (≈ 5,700 participant-minutes), not the 10,000 min/month quoted in spec/16.
- Account facts checked 2026-10-12 (second research pass): LiveKit's project "data region" covers analytics/logs only, not media; EU media routing = region pinning on Scale (support request). LiveKit Build plan needs no card and hard-caps at 5,000 participant-min and 60 recording (transcode) min. Zoom Build Platform: new account required; trial = 20 credits (checkout says one-time, 12-month expiry; another Zoom page says monthly); SDK key/secret, API key/secret and webhook Secret Token live in the legacy Build portal; recording storage location can be Germany; `DELETE …/recordings?action=delete` is permanent (`trash` keeps 30 days).
- T4: `livekit_client` 2.13.1 compiles for Flutter web both as JS and as WebAssembly (`flutter build web --wasm --no-web-resources-cdn`). Loading the join screen made no request outside its own origin (Playwright network log, 2026-10-12). Caveat from the privacy review: the build keeps Flutter's default `fontFallbackBaseUrl` (`https://fonts.gstatic.com/s/`), so a glyph missing from IBM Plex Sans Arabic (emoji, symbols in a name or chat) would fetch from Google Fonts. Latent here (UI strings are covered by the font). For WP-0.5 / ADR-010: point `fontFallbackBaseUrl` at our origin and add a missing-glyph case to the `web:no-third-party-requests` gate.
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
- Android app and iPad (2026-10-13): iPad Safari 19/20 as student, p50 2.13 s, p95 5.43 s. Android first run 17/20 (p50 2.66 s, max 5.96 s): the three failures fell inside one minute (05:55) together with an iPad ICE timeout, i.e. a local network break; one of them was a spike-app error (`getStats` on a closed peer connection). Android second run 20/20, p50 1.84 s, p95 2.24 s, max 3.15 s. Both runs stay in `metrics.jsonl`.
- Supervisor-role trials on the PC failed to receive video (7 of 9 timed out): the spike app asked to publish a camera the supervisor grant does not allow. Lesson for WP-3.4: the client must derive what it publishes from the role grants. Results are now grouped by role so this does not mix with student numbers.
- Teacher tools: mute all and lock/unlock confirmed by the owner ("تعمل تماما"); a non-host rejoin during lock was refused with «المعلمة أغلقت الغرفة».
- Recording: owner's recording `EG_tsvuJzTfQc36` EGRESS_COMPLETE, 23.4 s MP4 (2,132,741 bytes; no checksum taken before the purge) with video and audio, uploaded straight to our bucket (no provider copy). It first failed with S3 SignatureDoesNotMatch: Cloudflare rewrites `Accept-Encoding`, which the AWS SDK for Go (LiveKit egress) signs as `identity`; fixed with an nginx in front of MinIO (spike-only; production uses S3 directly).
- 1.5 Mbit/s participant (container, ~90 s, real room with the owner's devices): joined in 4.2 s; received 360p at ~19 fps from two publishers; quality excellent → good; no audio-only switch needed; 0.67 Mbit/s downstream.
- Disconnect/rejoin (iPhone): webhooks recorded leave 05:28:22 → join 05:28:27 and leave 05:33:23 → join 05:33:34; `AttendanceProbe` merges them (student-3: 19.4 min incl. trials). Finding for WP-3.2: LiveKit recorders join the room as participants (`EG_…` identities) and must be excluded from attendance.

### Per-student cost derivation (owner question, 2026-10-13)
8 classes × 60 min per student per month; class of 4–5 students + teacher; supervisor ~5 min per class; downstream 1.0 Mbit/s per participant (0.67 measured with 2–3 videos; 1.5 as a heavy case); prices of 2026-10-12. Per student: 584 participant-min and 96 recorded min (5 students) or 610 and 120 (4 students).

| Students on the platform | Plan | No recording ($/student/month) | Every class recorded |
| --- | --- | --- | --- |
| 100 | Ship | 0.73 (5/class) · 0.75 (4/class) | 2.53 · 3.03 |
| 300 | Ship | 0.63 · 0.67 | 2.51 · 3.03 |
| 500 | Ship | 0.71 · 0.74 | 2.60 · 3.12 |
| 500 | Scale | 1.00 · 1.00 | 2.20 · 2.56 |
| 1000 | Scale | 0.64 · 0.66 | 1.96 · 2.34 |
| any | Zoom | 2.04 · 2.14 | 3.00 · 3.33 |

At 1.5 Mbit/s add about 0.27 $ per student on Ship. Formula: base fee + participant-minutes over the included amount × minute price + downstream GB over the included amount × GB price + recorded room-minutes over the included amount × transcode price, divided by students.

### Spike-only — do not copy into product
- Cloudflare quick tunnel: tokens, recordings and the access code pass through Cloudflare; the link changes on restart. Product: WP-1.8 environments.
- Shared spike access code (also in `?code=` URLs, logged by `php -S`). Product: real authentication and authorization (WP-1.2/1.3).
- Release APK signed with the debug key, R8 shrinking off; Zoom SDK merged permissions (`READ_PHONE_STATE`, `SYSTEM_ALERT_WINDOW`, …).
- Recordings on local disk / spike MinIO behind an nginx header fix. Product: S3 `eu-central-1` directly (D-16).
- `canPublishData=true` for every role, free-text `error` in metrics, Zoom `leave_reason` stored verbatim (see lessons in ADR-005).
- Shared access/host codes typed by testers, MinIO credentials on command lines, unpinned nginx/Playwright images, webhooks without deduplication.

## Verification evidence
```
──────── verify summary (--all) ──────── 2026-10-13, commit 521b52d
✅ api:pint · ✅ api:phpstan · ✅ api:tests+coverage(80)
✅ dart:format · ✅ dart:analyze
✅ flutter:test:packages/sura_core · ✅ flutter:test:apps/family · ✅ flutter:test:apps/teacher
✅ web:build:apps/family · ✅ web:no-third-party-requests:apps/family
✅ web:build:apps/teacher · ✅ web:no-third-party-requests:apps/teacher
✅ guards · ✅ traceability · ✅ traceability:selftest · ✅ roadmap
Spike gates (outside verify.sh areas): php spikes/video/token-server/tests/run.php → 32 passed, 0 failed ·
spikes/video/app: flutter analyze → No issues found · flutter test → 9 passed
php scripts/check-traceability.php --wp WP-0.4 → traceability OK (no IDs owned by WP-0.4)
```
Cleanup 2026-10-13: LiveKit test recordings and the spike MinIO volume purged (`tools/minio-egress.sh purge`); spike server, tunnels and bots stopped (`tools/stop.sh`, no listener left on 27100/29100/29102); `spikes/video/token-server/var/` deleted entirely (event logs, metrics, stats, server/tunnel logs with `?code=` URLs, access code). Zoom's cloud recording (session `0nkBe1ej…`) could be neither fetched nor deleted: Zoom answers 3301 "still being processed" for both (the session had to end first); the owner turns on automatic deletion of cloud recordings in the Zoom account and deletes the Zoom Video SDK app. Owner revokes the spike LiveKit key.
| Acceptance criterion | Evidence |
| --- | --- |
| ADR-005 has «القرار» with the numbers, and D-20 is Closed | `docs/02-architecture/adr/ADR-005-video-provider.md` (Accepted 2026-10-13, results table); `docs/00-governance/decisions.md` D-20 Closed with the owner's approval quoted |
| Every criterion in the ADR-005 table has a measured result | LiveKit: all eight measured (iOS native waived by the owner; iOS covered through Safari on iPhone and iPad; audio-only switch shown only in the preliminary PC run, the owner-device 1.5 Mbit/s run never needed it). Zoom: not measured — 1.5 Mbit/s, recording download (Zoom still processing), live Android join (phone unavailable), live remove, iOS native. The owner closed the WP with these gaps known («اغلق الحزمة», 2026-10-13, after the decision page listed them); Zoom was not chosen. Tables: `spikes/video/results/summary.md`, `join-times.csv` (Android combines both runs: 37/40; second run alone 20/20, p95 2.24 s) |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |
| traceability-checker | MAJOR | ADR quoted Android p95 2.24 s (second run) while results combine both runs (37/40, p95 empty) | Fixed: ADR row and acceptance evidence state both runs |
| traceability-checker | MINOR | Acceptance row vs ADR listed different Zoom gaps; owner waiver covered only iOS | Fixed: gaps reconciled; owner closed with gaps listed on the decision page |
| traceability-checker | MINOR | "تفوّق في كل معيار" overstated (data region, cost with full recording) | Fixed: ADR wording |
| traceability-checker | MINOR | Audio-only shown only in the preliminary PC run; no MP4 checksum | Fixed: stated in ADR and notes; checksum not taken (file purged) |
| traceability-checker | MINOR | D-35 per-student figures not derived | Fixed: derivation table above |
| traceability-checker | MINOR | `architecture.md:25` still "LiveKit أو Zoom"; demo step 1 wording | Fixed |
| traceability-checker | MINOR | Endpoint + webhooks: security-reviewer per DoD; UI without goldens/rtl-reviewer | security-reviewer run (row below); goldens and rtl-reviewer waived for spike UI (design §6), RTL checked by screenshot and widget test |
| traceability-checker | MINOR | D-20/ADR status changed by Claude, an exception to docs-and-plan rule | Accepted: explicit owner approval quoted, announced beforehand on the approval page |
| privacy-reviewer | MAJOR | `canPublishData=true` for students would be a private adult↔child channel if copied | Fixed: lesson in ADR-005 and "do not copy" list; WP-3.1 tests `canPublishData=false` |
| privacy-reviewer | MAJOR | "no request outside origin" overstated: default `fontFallbackBaseUrl` points at Google Fonts | Fixed: notes corrected; action for WP-0.5 recorded |
| privacy-reviewer | MINOR | `/metrics` `room` unvalidated, `error` free text | Fixed: `room` validated with the token pattern; free text noted as spike-only |
| privacy-reviewer | MINOR | Zoom `leave_reason` carries the display name | Noted: lesson in ADR-005 (store a reason code) |
| privacy-reviewer | MINOR | Close plan gaps: Platform Studio key, `token-server/var`, Zoom recording delete without fetch, provider session reports | Fixed: T14 and cleanup plan updated; owner asked to revoke keys and delete the Zoom app |
| privacy-reviewer | MINOR | No "do not copy" list | Fixed: list above |
| privacy-reviewer | MINOR | LiveKit becomes a processor of child data; not on the PRV-08 register | Fixed: lesson in ADR-005 and D-35 detail |
| security-reviewer | MAJOR | Any holder of the shared code could claim teacher/substitute and get the admin ticket (and Zoom host) | Fixed: separate host code required for host roles (test `spike CLS-04 host roles need the separate host code`); WP-3.1 lesson: role and identity come from the server-side assignment |
| security-reviewer | MAJOR | 24-bit code, no attempt limit, reused across restarts | Fixed: two 10-character codes (~49 bits each) regenerated at every `serve.sh` start; 20 wrong codes per client in 10 min → 429 (test `spike CLS-05 repeated wrong codes … throttled`). Codes still appear in bot/probe URLs (spike-only list); `php -S` logs addresses only, not paths |
| security-reviewer | MINOR | Webhook 401 told the caller which check failed | Fixed: reason logged only |
| security-reviewer | MINOR | Provider error bodies returned to the client | Fixed: logged; response keeps provider and status |
| security-reviewer | MINOR | `record/stop` accepted an egress id of another room | Fixed: id must be in ListEgress for the ticket's room |
| security-reviewer | MINOR | JWT without `exp` accepted | Fixed: `exp` required |
| security-reviewer | MINOR | Empty ticket secret would pass; chmod after write | Fixed: strict validation, private atomic write |
| security-reviewer | MINOR | `/metrics` `trial` unbounded | Fixed: 0–1000 (room fixed earlier) |
| security-reviewer | MINOR | No `Referrer-Policy` / framing protection on pages | Fixed: `no-referrer`, `frame-ancestors 'none'` |
| security-reviewer | MINOR | Zoom API token sent to any `download_url` host | Fixed: only `zoom.us` hosts (tested) |
| security-reviewer | MINOR | Webhooks not deduplicated; raw bodies not stored | Deferred to WP-3.2 (lesson: dedupe on provider event id, pair by participant sid, keep raw body) |
| security-reviewer | MINOR | MinIO passwords on command lines; nginx/Playwright images not pinned by digest; `var/` mounted read-write in the throttled container | Accepted for the spike (services removed, `var/` deleted); listed under "do not copy" |
| security-reviewer | MINOR | `canPublishData` for every role | Same as privacy MAJOR: lesson for WP-3.1 |

## خطوات العرض اليدوي
1. اكتب لـ Claude في VS Code: «جهّز محطة مكالمة الفيديو». يشغّل الخادم والرابط الآمن ومشاركاً آلياً بكاميرا، ويعطيك صفحة فيها الرابط ورمز التجربة.
2. افتح الرابط في Chrome على الحاسوب. المتوقع: شاشة «الدخول إلى غرفة تجريبية» بالعربية ومن اليمين لليسار.
3. اختر LiveKit، والدور «طالب»، والمقعد 2، واكتب اسم الجهاز ورمز التجربة، ثم اضغط «20 محاولة تلقائية». المتوقع بعد نحو دقيقة ونصف: «انتهت المحاولات»، والوسيط نحو ثانية أو ثانيتين.
4. ارجع واضغط «دخول». المتوقع: ترى «مشارك آلي 1» بكاميرا ملونة.
5. افتح الرابط على جهاز ثانٍ بدور «معلمة» والمقعد 1. المتوقع: أزرار «كتم الجميع» و«قفل الغرفة» و«بدء التسجيل» تحت الفيديو.
6. اضغط «بدء التسجيل»، وانتظر دقيقة، ثم «إيقاف التسجيل». يؤكد لك Claude أن الملف وصل إلى جهازه بصورة وصوت.
