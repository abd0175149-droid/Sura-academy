# Video provider spike (WP-0.4)

Throwaway code that measures **LiveKit Cloud** against the **Zoom Video SDK** for ADR-005 / D-20.
Nothing here is imported by `apps/` or `packages/`, and none of it is product code.
Design: `specs/WP-0.4/design.md`. Results: `spikes/video/results/` and `specs/WP-0.4/notes.md`.

| Path | What it is |
| --- | --- |
| `token-server/` | Plain PHP 8.4, no packages. Join tokens per role and pseudonym, verified webhooks, host-only moderation, LiveKit egress, join-time metrics, static files. Self-test: `php token-server/tests/run.php` |
| `app/` | Standalone Flutter app (web, Android, iOS). LiveKit on all three platforms; Zoom on Android and iOS only. 20 automatic join trials. Tests: `cd app && flutter analyze && flutter test` |
| `zoom-web/` | A plain HTML+JS probe for Zoom on the web (Zoom has no Flutter web SDK). The SDK goes in `zoom-web/vendor/` (git-ignored) via `tools/fetch-zoom-web.sh` |
| `tools/` | `serve.sh` / `stop.sh` (server + Cloudflare quick tunnel), `bot.mjs` (headless camera participant), `throttled.sh` (a bot on a 1.5 Mbit/s link), `minio-egress.sh` (throwaway bucket for LiveKit recordings), `zoom-recordings.php` (fetch, then delete at Zoom) |

## Run a session

```bash
# once
cp token-server/.env.example token-server/.env   # the owner fills the provider keys; never commit it
bash tools/fetch-zoom-web.sh
(cd app && flutter build web --release --no-web-resources-cdn)

# each session
bash tools/serve.sh                # prints the https link, the access code and the webhook URLs
bash tools/minio-egress.sh         # only for LiveKit recording tests
node tools/bot.mjs --provider livekit --seat 1 --stats &   # keeps a video in the room; logs downstream bitrate
bash tools/throttled.sh 20 2       # optional: 1.5 Mbit/s participant, stats in token-server/var/
bash tools/stop.sh                 # afterwards
```

The quick-tunnel link changes on every `serve.sh`, so the webhook URLs in the LiveKit and Zoom
dashboards must be updated after each restart.

## Privacy

Adults only, fixed pseudonyms (`teacher-1`, `student-2`), no real names in tokens, events, metrics or
recordings. Runtime data stays in `token-server/var/` (git-ignored). At WP close, recordings and the
MinIO volume are purged (`tools/minio-egress.sh purge`) and the provider keys are revoked.
