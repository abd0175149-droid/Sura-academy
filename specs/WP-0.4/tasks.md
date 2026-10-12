# Tasks — WP-0.4

Status legend: [ ] todo · [x] done. Each task ≤ ~2h. Verification command required. Tasks marked (owner) need the owner's accounts, devices or time; Claude prepares everything and gives one link.

Spike gates (spikes are outside `verify.sh` areas): `php spikes/video/token-server/tests/run.php` · `cd spikes/video/app && flutter analyze && flutter test`.

- [x] T1 Token server skeleton + self-test harness: HS256 JWT, role → grants, LiveKit and Zoom token payloads, TTL, join window, access code (tests first: CLS-02/03/04/05/13 cases fail, then pass) — verify: `php spikes/video/token-server/tests/run.php`
- [x] T2 Webhooks: LiveKit (JWT + body SHA-256) and Zoom (HMAC `v0:ts:body`, `endpoint.url_validation`) receivers, `events.jsonl`, `AttendanceProbe` minutes with a reconnect (tests first: CLS-09 cases) — verify: `php spikes/video/token-server/tests/run.php`
- [x] T3 Provider admin calls: LiveKit RoomService (list, mute, remove) and room-composite egress to MinIO; Zoom recordings list/download/delete; lock = stop issuing tokens for the room — verify: self-test for request shapes (done, 26/26); live check in T10/T11 once keys exist
- [ ] T4 Flutter spike app scaffold (standalone, web/android/ios, ARB ar+en, RTL), join screen, `SpikeVideoClient` interface, token fetch, metrics POST — verify: `cd spikes/video/app && flutter analyze && flutter test && flutter build web --release`
- [ ] T5 LiveKit client: room grid (4 tiles), mic/camera, teacher panel via server admin, connection policy (audio-only on poor quality), join timer + 20 auto trials, `?bot=` mode — verify: analyze/test green; join a local room with the bot (Playwright)
- [ ] T6 Zoom client (Android/iOS) via `flutter_zoom_videosdk`: join, tiles, host mute/remove, cloud recording start/stop, join timer + trials — verify: `flutter build apk --debug` green
- [ ] T7 Zoom web probe `spikes/video/zoom-web/` (vendored `@zoom/videosdk`, COOP/COEP headers from the server), same trial loop; write the Flutter-web interop effort estimate — verify: page joins a Zoom session in Chromium with fake media
- [ ] T8 Tooling: server serves web build, probe and APK; `tools/tunnel.sh` (cloudflared quick tunnel); MinIO bucket for egress; `tools/bot.sh` (headless Chromium, fake camera) and `tools/throttled.sh` (`tc` 1.5 Mbit/s container, stats log) — verify: tunnel URL opens the app from a phone; bot visible in the room
- [ ] T9 Android toolchain in `~/tools` (JDK 17, SDK cmdline-tools) and APK download; iOS path per owner answer — verify: `flutter build apk --release` and APK downloads over the tunnel
- [ ] T10 (owner) Session A — LiveKit: 20 trials on each device, 4-participant call, mute/remove/lock, 1.5 Mbit/s participant, webhook log, egress recording to MinIO — verify: `results/` filled; notes.md evidence
- [ ] T11 (owner) Session B — Zoom: same on Android/iOS and the web probe, cloud recording download + deletion via API — verify: `results/` filled; notes.md evidence
- [ ] T12 `tools/summarize.php`: p50/p95 per device × provider from `metrics.jsonl` → `results/join-times.csv`; cost model with sourced prices (date + URL) → `results/cost.md` — verify: `php spikes/video/tools/summarize.php` output pasted in notes.md
- [ ] T13 ADR-005 «القرار» filled with the numbers (status stays Proposed); Arabic decision page for the owner; after explicit approval: ADR-005 Accepted, D-20 Closed (approval quoted in notes.md) — verify: every row of the ADR-005 criteria table has a measured result
- [ ] T14 Close: delete test recordings and rotate/remove provider keys; status.md row + focus, station «مكالمة فيديو تجريبية على أجهزتك», roadmap page, privacy-reviewer + traceability-checker, `bash scripts/verify.sh --all`, PR — verify: `php scripts/check-traceability.php --wp WP-0.4`
