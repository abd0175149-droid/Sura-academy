# Flutter web spike app (WP-0.5)

Standalone Flutter app (web + Android), outside the pub workspace. Not product code.
Design: `specs/WP-0.5/design.md`. Overview and run steps: `../README.md`.

- `lib/src/reading/` vocalized text in Noto Naskh Arabic and IBM Plex Sans Arabic (16/24/32),
  show/hide diacritics, text size, capture to PNG (CNT-08).
- `lib/src/form/` Arabic text fields, a list and a button with semantics (screen readers).
- `lib/src/payment/`, `lib/src/video/` probes for the isolation headers (COOP/COEP).
- `lib/src/measure/` start-up marks posted to the spike server (D-22).
- `web/` Arabic HTML splash, bootstrap with timing marks, hand-written service worker, manifest.
  The manifest has no `start_url` on purpose: the installed app opens at the URL it was installed
  from, which carries the spike's run key.

Gates: `dart format --set-exit-if-changed lib test && flutter analyze && flutter test`.
