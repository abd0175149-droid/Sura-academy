# Design — WP-0.4 Technical spike: video provider

## 1. Scope
- Goal (phase-0-foundation.md, WP-0.4): a numbers-based choice between **LiveKit Cloud** and **Zoom Video SDK** for one Flutter app on web, iOS and Android. Result goes into ADR-005 «القرار» and closes D-20.
- In scope (IDs): **feasibility only**, nothing implemented for production. CLS-01..05, CLS-09, CLS-10, CLS-13, CLS-14 and the NFR «الدخول للحصة < 10 ث عند 95%». These IDs stay assigned to WP-3.x in `traceability.md`; their status column is **not** changed by this WP.
- Deliverables (all spike code under `spikes/video/`, never imported by `apps/` or `packages/`):
  1. A small PHP token endpoint issuing join tokens per provider and role (teacher = host, student, substitute, supervisor).
  2. A Flutter spike app joining a 4-participant room: LiveKit on web, Android, iOS; Zoom on Android and iOS via `flutter_zoom_videosdk`, plus an effort estimate for Zoom on web via JavaScript.
  3. Join/leave webhooks captured; one cloud recording started, downloaded, and deleted from the provider.
  4. A measurement table: join time to first video (20 trials per device), behaviour at 1.5 Mbit/s and audio-only fallback, mute and remove participant, data region.
  5. Monthly cost: 100 concurrent classes × 4 participants × 75 min average × 8 classes per student per month.
- Explicitly out of scope: production code; the `VideoProvider`/`VideoClient` interfaces themselves (WP-3.1); attendance rules and ledger effects (WP-3.2/2.3); recording consent logic and retention jobs (WP-3.3); whiteboard (CLS-16, R2); chat (CLS-07); any real child, family or teacher data.
- Decisions applied:
  - **D-20 (Open, closed by this WP):** the WP is the mechanism that resolves it. Tie rule from ADR-005: LiveKit Cloud. The final status change of D-20 and ADR-005 needs the owner's explicit approval (Claude never changes an existing decision's status on its own, `.claude/rules/docs-and-plan.md`).
  - **D-16 (Default):** recordings are copied to private EU storage within 1 h and deleted from the provider within 24 h. The spike proves the mechanics with local MinIO as the stand-in for S3 `eu-central-1` (no AWS account in a spike).

## 2. Traceability (feasibility evidence, not production tests)
Spike tests live in `spikes/video/**`, which `check-traceability.php` does not scan, so they cannot be mistaken for WP-3.x coverage. Names start with `spike` and cite the ID.

| Req ID | What the spike must show | Code location (planned) | Evidence (planned) |
| --- | --- | --- | --- |
| CLS-01 | Video+audio in the browser (Chrome, Safari on iPad) and in native apps, no install for web | `spikes/video/app/` | Station run on each device; measurement table row per device × provider |
| CLS-02 | Server refuses a token outside a join window (window is a parameter here; real window comes from settings in WP-3.1) | `token-server/src/TokenIssuer.php` | `spike CLS-02 token refused outside join window` |
| CLS-03 | Short-lived token per user, room and role; no static link. Note: Zoom requires `exp` ≥ 30 min after `iat` (provider minimum), LiveKit has no minimum | `token-server/src/TokenIssuer.php`, `LiveKitToken.php`, `ZoomToken.php` | `spike CLS-03 token expires within ttl`, `spike CLS-03 zoom token uses the provider minimum ttl`, `spike CLS-03 token bound to one room and identity` |
| CLS-04 | Teacher = host: mute all, remove participant, lock room (LiveKit: RoomService `MutePublishedTrack`/`RemoveParticipant`, which also revokes the token on Cloud; Zoom: host calls `muteAllAudio`/`removeUser` in the SDK, `role_type` 1). Lock = the server stops issuing tokens for that room (neither provider has a room lock). Student: no screen share by default (LiveKit `canPublishSources`; Zoom `setSharePrivilege(Locked)`) | `src/Roles.php`, `src/LiveKitAdmin.php`, app teacher panel | `spike CLS-04 student cannot publish screen share`, `spike CLS-04 teacher grant is room admin`; manual: mute/remove on each provider |
| CLS-05 | No one joins without a server-issued token; wrong access code → 403 | `public/index.php` | `spike CLS-05 token request without access code is rejected` |
| CLS-09 | Join/leave webhooks arrive and attendance minutes can be computed, including a reconnect | `src/Webhooks/*.php`, `src/AttendanceProbe.php` | `spike CLS-09 livekit webhook signature verified`, `… tampered body rejected`, `spike CLS-09 zoom signature and url validation`, `spike CLS-09 minutes from join leave pairs with reconnect`; real event log excerpt (pseudonyms) in notes.md |
| CLS-10 | Cloud recording starts, file lands in our storage (or is downloaded), then deleted at the provider (D-16) | `src/LiveKitAdmin.php` (egress → MinIO), `src/ZoomRecordings.php` (list, download, delete) | Manual run, file size and checksum in notes.md |
| CLS-13 | Substitute and supervisor get a token for that room only; supervisor visible with the name «مشرفة», never hidden | `src/Roles.php` | `spike CLS-13 supervisor is visible and named`, `spike CLS-13 substitute token only for its room` |
| CLS-14 | Under 1.5 Mbit/s the call stays usable and drops to audio-only when quality is poor | app `connection_policy.dart`; throttled participant container | Manual observation + client stats (resolution, fps, loss) in the measurement table |
| NFR join < 10 s @ p95 | Tap → first remote video frame, 20 trials per device × provider | app `join_timer.dart`, `POST /metrics`, `tools/summarize.php` | `results/join-times.csv` (no names) + p50/p95 per device |

## 3. Data
No database tables. Runtime files under `spikes/video/token-server/var/` (git-ignored): `events.jsonl` (webhooks), `metrics.jsonl` (join timings), `recordings/`. Identities are fixed pseudonyms (`teacher-1`, `student-1..3`, `substitute-1`, `supervisor-1`). Only aggregated, name-free results are committed (`spikes/video/results/*.csv`).

## 4. API contract changes
None to `contracts/openapi/sura-api.v1.yaml`. The spike's own endpoints are not part of the product API:

| Method | Path | Request | Response | Errors |
| --- | --- | --- | --- | --- |
| POST | `/token` | `{provider, role, room, identity, access_code}` | `{token, server_url, expires_at}` | 403 bad access code · 422 unknown role/provider · 409 outside window |
| POST | `/webhooks/livekit` | LiveKit event, `Authorization` JWT | 200 | 401 bad signature |
| POST | `/webhooks/zoom` | Zoom event, `x-zm-signature`, `x-zm-request-timestamp` | 200 (or url-validation answer) | 401 bad signature |
| POST | `/admin/{mute,remove,lock,record/start,record/stop}` | `{provider, room, identity?}` + access code | 200 | 403 · 502 provider error |
| POST | `/metrics` | `{provider, platform, device, network, t_token_ms, t_connected_ms, t_first_video_ms}` | 204 | 422 |
| GET | `/` , `/zoom-web/`, `/downloads/*.apk` | static: Flutter web build, Zoom web probe, Android APK | files | 404 |

## 5. Domain logic
Spike only, no business rules.
- **PHP token server:** plain PHP 8.4, no Composer packages (HS256 JWT, HMAC and Twirp/REST calls are a few lines each). Runs with `php -S` (`PHP_CLI_SERVER_WORKERS=4`) on `127.0.0.1:27100`. Keys come from `token-server/.env`, created by the owner from `.env.example`; Claude never reads it. A random spike access code protects every endpoint except webhooks (the tunnel URL is public).
- **Roles → grants:** teacher: publish camera/mic/screen, room admin. Student: camera and mic only. Substitute: same as teacher, one room. Supervisor: visible, name «مشرفة», can subscribe and publish mic, not hidden. LiveKit TTL 10 min (spike value; production TTL comes from settings). Zoom TTL 30 min, the provider minimum (`exp` ≥ `iat` + 1800 s); `role_type` 1 for teacher/substitute, 0 otherwise; `geo_regions` `DE,NL`.
- **Webhooks:** verified, appended to `events.jsonl`. `AttendanceProbe` sums minutes per identity from joined/left pairs (proves CLS-09 is computable; real rules belong to WP-3.2).
- **Recording:** LiveKit Cloud keeps no recordings; room-composite egress uploads straight to our S3-compatible bucket (MinIO exposed through the tunnel, `force_path_style`). Zoom: cloud recording in Zoom's cloud → download via REST → `DELETE /v2/videosdk/sessions/{id}/recordings` (account setting «host can delete cloud recordings»; check whether deletion is permanent or a 30-day trash, which matters for D-16's 24 h).
- **Join-time trials:** an «auto trials» button joins, waits for the first remote video frame, records timings, leaves, and repeats 20 times. A **bot participant** (headless Chromium in Docker with a fake camera, running the same web app with `?bot=teacher`) keeps a video stream in the room so every trial has a remote video to wait for.
- **1.5 Mbit/s:** a second Docker container joins as a participant with its link shaped by `tc` (1.5 Mbit/s up, policed 1.5 Mbit/s down; no host `sudo` needed). It logs WebRTC stats every 2 s; the owner watches the same participant on the devices. The app's connection policy turns off incoming video when quality is "poor" for 5 s (LiveKit), or relies on Zoom's built-in adaptation; the result says which is better.

## 6. UI (spike app)
- Flutter project `spikes/video/app` (standalone, not in the pub workspace, own `analysis_options.yaml`), platforms web, android, ios.
- Screen 1 «انضمام»: provider (Zoom disabled on web with a reason), role, pseudonym, device label, network label, access code; «دخول» and «20 محاولة تلقائية».
- Screen 2 «الغرفة»: up to 4 video tiles, mic/camera toggles, connection-quality badge, live join-time readout; teacher panel: mute all, remove, lock, start/stop recording.
- Strings in ARB (`ar` first, `en`), RTL by default, directional APIs only. No goldens (spike, thrown away after the decision). No analytics or third-party hosts except the video provider.
- **Zoom on web:** Zoom states it has no plan for Flutter web, so the product would need a Flutter-web ↔ JS interop layer over the Web SDK. `spikes/video/zoom-web/` is a plain HTML+JS page using `@zoom/videosdk` 2.5.0 served locally with its worker/WASM `dependentAssets` (vendored, no CDN), COOP/COEP headers from our server, same trial loop. Its numbers ground the effort estimate for that interop layer.

## 7. Security & privacy
- Public repo: no keys in git (`spikes/**/.env` is already ignored); `.env.example` lists names only. Keys are rotated or deleted after the spike.
- Test sessions use **adults only** (owner and helpers), pseudonyms, and test content. No child takes part; no real names in tokens, events, recordings or results.
- Recordings stay on the owner's machine (MinIO / `var/recordings`) and are deleted when the WP closes; provider-side copies are deleted through the API and the deletion is evidenced.
- The tunnel URL is shared only with testers; every non-webhook endpoint requires the access code; webhooks require valid provider signatures.
- `privacy-reviewer` runs at the end (WP review row).

## 8. Risks & open questions (owner)
1. **iOS native needs a Mac** (Xcode). This machine is Windows + WSL. Without a Mac: LiveKit on iPhone/iPad is measured in Safari (web), and Zoom on iOS cannot be measured natively. Options: owner's Mac; or GitHub Actions macOS + paid Apple Developer account + TestFlight; or skip iOS native and record it as unmeasured.
2. **Accounts (owner):** LiveKit Cloud project (free tier) and Zoom Video SDK account on the Build Platform (pay-as-you-go, 20 free credits ≈ 5,700 participant-minutes, enough for the spike; may ask for a card). The LiveKit project must be created in the EU (Frankfurt) data region. Owner pastes keys into `spikes/video/token-server/.env` in VS Code; webhook URLs are pasted into each dashboard once per test day (quick-tunnel URL changes each run).
3. **Tools to install (outside the repo, no `sudo`):** `cloudflared` (Cloudflare quick tunnel, no account) for an HTTPS link that phones and iPad can open; Temurin JDK 17 + Android SDK command-line tools in `~/tools` to build the APK; Docker images for the bot and the throttled participant.
4. **New dependencies (spike only, not in ADR-002/003):** Flutter `livekit_client`, `flutter_zoom_videosdk`, `http`; web `@zoom/videosdk` (vendored copy). No PHP packages.
5. **Devices and people:** at least Chrome on a PC, Safari on iPad, one Android phone, one iPhone (if iOS native is possible); 3 adult helpers for the 4-participant sessions (the bot can fill one seat).
6. **Data region:** LiveKit fixes the project data region at creation, so the owner must pick «European Union (Frankfurt)»; media pinning to EU needs the Scale plan (from $500/month). Zoom uses `geo_regions` `DE,NL` plus an account data-centre setting (a routing bug was fixed 2026-09-15); the spike checks where sessions actually route.
7. **Device minimums:** Zoom's Flutter SDK needs iOS 15+ and Android 9+ (minSdk 28); LiveKit needs iOS 13+ and Android 5+.
8. **Cost drivers (preliminary, checked 2026-10-12):** participant minutes alone are small for LiveKit (Ship $50 incl. 150k min, then $0.0005/min) and ~$840/month for Zoom ($0.0035/min × 240k). Recording (LiveKit $0.02/min transcode on Ship; Zoom $0.01/min + $0.25/GB) and LiveKit downstream bandwidth ($0.12/GB over 250 GB on Ship) may dominate, so the cost model includes recording share and bandwidth as parameters.
9. **Closing D-20:** after the owner approves the recommendation, Claude sets ADR-005 to Accepted and D-20 to Closed **only with that explicit approval** recorded in notes.md.
