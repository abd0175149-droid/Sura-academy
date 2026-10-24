# Tasks — WP-0.7

Status legend: [ ] todo · [x] done. Each task ≤ ~2h. Verification command required.
No contract, no production code: order is tooling (tests first) → module docs → registry/coverage → docs/close.

- [x] T1 Checker self-test first: `scripts/test-erd.php` with the fixture tree and one failing case per check E0–E7 (design §2) — verify: `php scripts/test-erd.php` fails because `scripts/check-erd.php` does not exist yet
- [x] T2 Checker, part 1: parsing (spec/14 domain table, traceability R1 IDs, conventions append-only list, module files), E1 mapping, E2 migration order, E5 coverage, E6 registry — verify: `php scripts/test-erd.php` (those cases green)
- [x] T3 Checker, part 2: E3 convention lint, E4 append-only, E7 Mermaid generation and `--write`; `verify.sh` gates `erd` and `erd:selftest` — verify: `php scripts/test-erd.php` all green; `bash scripts/verify.sh --changed`
- [x] T4 `erd/README.md`: format rules, module index, full spec/14 mapping table (every spec name with a disposition) — verify: `php scripts/check-erd.php` (E1 fails only for module files not written yet)
- [x] T5 `platform.md` + `identity.md` (incl. package and framework tables) — verify: `php scripts/check-erd.php --write && php scripts/check-erd.php`
- [x] T6 `families.md` (consents, legal documents, data requests, leads, safeguarding) — verify: same
- [x] T7 `curriculum.md` (hierarchy, content, outcomes per D-26, placement, assessments, trials) — verify: same
- [x] T8 `teachers.md` core + compensation (D-02, D-03, D-04) — verify: same
- [x] T9 `scheduling.md` (time model per ADR-008, D-08, D-12, D-24, D-30; `student_availability` per D-37 answer) — verify: same
- [x] T10 `live.md` (rooms, tokens, participant events, recordings per D-11/D-16, chat) — verify: same
- [x] T11 `ledger.md` (ADR-007, D-05, D-06, D-28; unique key per design §3.3) — verify: same
- [x] T12 `billing.md` (D-01, D-07, D-28, D-30; payment gateway fields behind ADR-006/D-36) — verify: same
- [x] T13 `learning.md` + `messaging.md` (D-10) — verify: same
- [x] T14 Migration order section in README (all tables, FK-safe) — verify: `php scripts/check-erd.php` (E2 green)
- [x] T15 `pii-registry.md` + `config/privacy.php` shape (D-14, D-15, F17 retention table) — verify: `php scripts/check-erd.php` (E6 green)
- [x] T16 `r1-coverage.md`: 210 R1 IDs + OUT-01/02, built from `specs/WP-0.7/inventory/` — verify: `php scripts/check-erd.php` (E5 green, `R1 covered: 210`)
- [x] T17 Sync docs: `data-model-conventions.md` (added tables, append-only list + `join_token_log`, `recording_access_log`), `glossary.md` (new models) — verify: `php scripts/check-erd.php`; `bash scripts/verify.sh --changed`
- [ ] T18 Owner review page (Arabic, module by module, diagrams) + roadmap station; record owner approval in `status.md` «اعتمادات صاحب المشروع» — verify: page published; owner's answer quoted in notes.md
- [x] T19 Verify + reviews: `bash scripts/verify.sh --all`; ledger-auditor, privacy-reviewer, traceability-checker; fix BLOCKER/MAJOR — verify: summary in notes.md
- [ ] T20 Close: status.md, notes.md demo steps, roadmap republish, PR — verify: `php scripts/check-traceability.php --wp WP-0.7`; `php scripts/build-roadmap.php --check`
