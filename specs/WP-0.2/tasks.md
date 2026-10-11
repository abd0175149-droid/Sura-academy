# Tasks — WP-0.2

Status legend: [ ] todo · [x] done. Each task ≤ ~2h. Verification command required.

- [ ] T1 Inventory: plugins, MCP servers (CLI + headless init event + laravel-boost handshake), CLAUDE.md, 12 skills, 6 agents → notes.md — verify: `claude plugin list`; `claude mcp list`; headless `claude -p … --output-format stream-json --verbose` init event saved
- [ ] T2 Guard attempts G1–G5 (required) and G6 (extra), each with its exact tool result → notes.md — verify: each result matches design §5, or is reported as a finding
- [ ] T3 Probe cleanup: delete `apps/family/lib/guard_probe.dart` and any probe that got written — verify: `git status --short` shows no probe files
- [ ] T4 `scripts/test-guards.php` + `verify.sh` gate `guards`: replay recorded tool calls against the hooks, assert block/allow — verify: `php scripts/test-guards.php` green; `bash scripts/verify.sh --all` green
- [ ] T5 Audit: `claude doctor`; audit settings/hooks vs constitution → `specs/WP-0.2/proposals.md` (exact diffs for the owner); fix START_HERE.md §3–§4 for D-33 — verify: proposals reviewed in PR
- [ ] T6 Close: status.md row + focus block (next WP-0.3), notes (evidence, demo steps), traceability-checker, PR; owner types `/sura-status` — verify: `php scripts/check-traceability.php --wp WP-0.2`; `/sura-status` names WP-0.3
