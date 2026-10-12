# Tasks — WP-0.3

Status legend: [ ] todo · [x] done. Each task ≤ ~2h. Verification command required.

- [x] T1 Self-test harness `scripts/test-traceability.php` (fixture tree in a temp dir, cases from design §2) + `--root` option in the checker — verify: `php scripts/test-traceability.php` runs; existing-behaviour cases pass, N1–N8 cases fail for the right reason
- [x] T2 Checker: N1 duplicates, N2 README totals, N3 traceability header totals, N4 README per-file counts and ID ranges — verify: those cases green; `php scripts/check-traceability.php` green on the repo
- [x] T3 Checker: N5 split vs source (sections, prose, rows, ID sets; D-xx hint in the message) — verify: N5 cases green; repo green
- [ ] T4 Checker: N6 trace row consistency, N7 WP definitions, N8 BR table; new summary line; drop dead code — verify: all self-test cases green; `php scripts/check-traceability.php`, `--quick`, `--wp WP-0.3` green on the repo
- [ ] T5 `scripts/verify.sh`: full mode on `--all`, `traceability:selftest` gate; update the checker's usage header — verify: `bash scripts/verify.sh --all --only traceability` green; `bash scripts/verify.sh --all` green
- [ ] T6 Evidence: full-mode output and the split-vs-source result (0 differences, so no D-xx) pasted in notes.md — verify: notes.md acceptance table filled
- [ ] T7 Close: status.md row + focus block (next WP), notes demo steps, traceability-checker review, PR — verify: `php scripts/check-traceability.php --wp WP-0.3`
