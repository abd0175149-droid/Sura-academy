# Flutter web spike (WP-0.5)

Throwaway code that measures Flutter on the web for **ADR-010** (build and delivery settings) and
**D-22 / CR-003** (load-time targets). Nothing here is imported by `apps/` or `packages/`, and none
of it is product code. Design: `specs/WP-0.5/design.md`. Results: `results/` and `specs/WP-0.5/notes.md`.

| Path | What it is |
| --- | --- |
| `app/` | Standalone Flutter app (web + Android): vocalized text in two bundled fonts, form/list/button with semantics, payment and video probes, start-up marks. Tests: `cd app && flutter analyze && flutter test` |
| `server/` | Plain PHP 8.4, no packages. App origin (27200): the builds under `/v/<wasm\|wasmx\|js>/<iso\|plain>/[r-<run>/]` with or without COOP/COEP, CSP with a per-load nonce, precompressed files; run-key protected `/metrics`, `/shots`, `/feedback`, `/livekit-token`. Payment origin (27201): fake hosted payment page (redirect, popup, iframe). Self-test: `php server/tests/run.php` |
| `tools/` | `build.sh` (both builds, brotli/gzip, `results/sizes.csv`, `--apk`), `serve.sh` / `stop.sh` (servers, `--tunnel` for devices, `--livekit` for a local LiveKit dev server), `measure.mjs` (headless Chromium: cold/warm loads under DevTools network and CPU profiles; `--checks` for isolation, payment, video, accessibility), `summarize.php` (`results/summary.md`) |
| `results/` | Committed, name-free results: `sizes.csv`, `load-times.csv`, `pc-interaction.csv`, `summary.md`, `isolation.md`, `a11y.md`, `shots/` |

## Run a session

```bash
bash tools/build.sh                 # once per code change (≈ 2 min)
bash tools/serve.sh --livekit       # this PC only; add --tunnel for the iPad and the Chromebook
node tools/measure.mjs --runs 10    # PC baseline (≈ 40 min); --checks for the isolation matrix
php tools/summarize.php             # results/summary.md and load-times.csv
bash tools/stop.sh
```

`serve.sh` prints the start links with the run key and a device label (`d=ipad-safari`, …). The
start page has «تشغيل القياس تلقائياً», which walks the four variants by itself (5 cold + 5 warm
each) and posts the timings to `server/var/metrics.jsonl`.

## Variants

| Path | Build | Headers |
| --- | --- | --- |
| `/v/wasm/iso/` | `--wasm` (WebAssembly on Chromium, JS elsewhere) | COOP `same-origin` + COEP `require-corp` |
| `/v/wasm/plain/` | same | none |
| `/v/js/iso/` | JavaScript only | COOP + COEP |
| `/v/js/plain/` | same | none |
| `/v/wasmx/iso/` | `--wasm` with `wasmAllowList` opened to Safari and Firefox (experiment) | COOP + COEP |

## Privacy

Adults only, no names: metrics carry a fixed device label, the browser user agent and timings. The
LiveKit Cloud check reads WP-0.4's key file at runtime (owner's decision, 2026-10-13); scripts never
print it, and the key is revoked when the WP closes. Runtime data stays in `server/var/` (git-ignored).
