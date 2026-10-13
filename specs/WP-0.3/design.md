# Design — WP-0.3 Spec integrity and traceability baseline

## 1. Scope
- In scope (IDs): none implemented. The WP verifies all 253 requirement IDs (R1 210 · R2 38 · R3 5) mechanically.
- Deliverables (phase-0-foundation.md, WP-0.3):
  1. `php scripts/check-traceability.php` (full mode) passes: 253 IDs (210 / 38 / 5), each assigned, every referenced WP present in `status.md`.
  2. A traceability step in CI.
  3. Any difference between the split spec files and the source is recorded as an Open decision, never hand-fixed.
- Explicitly out of scope:
  - Editing `docs/01-product/spec/**` or `docs/01-product/source/**` (human-only).
  - Cross-checking each WP's "المتطلبات" row in `phase-*.md` against `traceability.md` (the phase files are not in this WP's read list; can be a later hotfix WP if wanted).
  - CR-002 proposed IDs (`22-learning-experience-proposal.md`, OUT/SLF/…): stay outside the 253 and outside the source comparison.
- Decisions applied: none listed for this WP.

### Baseline found during preflight (read-only probes, before any change)
| Check | Result today |
| --- | --- |
| Current script, full and `--quick` | ✔ passes (`requirements: 253 · mapped: 253 · work packages: 50`) |
| IDs in split files vs `source/spec-rev24.md` | 253 = 253, same IDs, identical row text, same priorities |
| Totals by priority | R1 210 · R2 38 · R3 5 (matches README and traceability.md headers) |
| README index table, per-file R1/R2/R3 counts | all match |
| traceability.md rows: priority / spec file / at least one WP | all consistent |
| Split files 00–21 vs source sections, line by line | identical except the auto-copy `<!-- … -->` header in every file and one package note `> **تنبيه الحزمة:** …` at the end of file 13 (Vue/Inertia row replaced by ADR-003; documented in README "ملاحظات النقل"). The three figure captions `> **شكل (منقول نصاً …)**` are in the source too. (Corrected during T3: the preflight probe compared line sets and misread which line was extra.) |
| WPs in status.md with a definition in phase files | 42 as `### WP-x.y` headings, 8 (WP-6.x, WP-7.1) as table rows |

So the data is already consistent. The gap is that the script checks only part of this: it does not check totals, priority split, README counts, assignment (a row with `—` / `—` passes), the priority/file columns, or the split-vs-source match, and CI runs only `--quick`. This WP turns the probes into permanent checks.

## 2. Traceability
No requirement IDs are in scope, so no test names start with an ID. The checker gets its own fixture-based self-test, in the same style as `scripts/test-guards.php` (WP-0.2).

| Check (new = N) | Behavior (one line) | Code location (planned) | Test case(s) in `scripts/test-traceability.php` |
| --- | --- | --- | --- |
| C0 | `--root <dir>` runs the checks against another tree (fixtures) | `check-traceability.php` args | all cases |
| N1 | Duplicate requirement ID across or within spec files fails | spec parse | `duplicate id in spec files` |
| N2 | Totals and R1/R2/R3 split match the `**المجموع:** N متطلباً — R1: a · R2: b · R3: c` line in `spec/README.md` | spec totals | `readme total mismatch`, `readme priority split mismatch` |
| N3 | Same totals line in `traceability.md` matches | trace totals | `traceability header total mismatch` |
| N4 | README index table: per-file R1/R2/R3 counts and ID ranges (`ONB-01…14 · PUB-01…12`) equal the IDs actually in that file | README index | `readme per-file count mismatch`, `readme id range mismatch` |
| N5 | Split vs source: the source file named in README (`../source/<file>.md`) has one `## ` section per split file 00–21; each section equals the split file line by line, ignoring `<!-- … -->` lines and package notes `> **تنبيه الحزمة:** …` (counted in the summary line); figure captions are compared like any line; same requirement ID set. Failure message tells you to record an Open `D-xx` and not hand-fix | split/source | `requirement row differs from source`, `id only in split`, `id only in source`, `prose differs from source`, `package note and header comment are ignored`, `package notes are counted in the summary`, `figure caption is compared like any line`, section/README/source-file cases |
| N6 | Each traceability row: priority column starts with the spec's R-level; spec-file column equals the file holding the ID; at least one of backend/experience is a WP | trace rows | `trace priority differs`, `trace spec file differs`, `row not assigned` |
| N7 | Every WP in status.md is defined in `phase-*.md` as a `### WP-x.y` heading or a `\| WP-x.y \|` table row (today: any substring match) | WP definitions | `wp mentioned only in prose` |
| N8 | Every WP in the BR table exists in status.md; BR IDs allowed in test names come from that table (replaces the hard-coded `BR-001…018` regex) | BR table | `br table unknown wp`, `test names unknown br id` |
| existing | Unmapped spec ID, WP missing from status.md, unknown ID in test names, missing tests for Review/Done WPs (full) and `--wp` | unchanged | `baseline fixture passes`, `unmapped id`, `trace wp missing from status`, `unknown id in test name`, `review wp without tests (full fails, quick passes)` |

Added after the traceability-checker review (notes.md, "Review findings"): business-rule rows must be readable and cover the `BR-001…BR-0xx` range stated in `spec/README.md` exactly; status values must come from status.md's vocabulary, with no duplicate rows, and «يعتمد على» WPs must exist; `--wp` needs a value and cannot be combined with `--quick`; a `<!--` comment is ignored only above the first heading; a package note must be named in README «ملاحظات النقل»; ID-shaped traceability rows with an unexpected cell count fail.

Summary line gains the split and source: `requirements: 253 (R1 210 · R2 38 · R3 5) · source: spec-rev24.md (22 sections · 1 package note) · mapped: 253 · work packages: 50 · …`. The unused `$counts` line is removed.

## 3. Data
None.

## 4. API contract changes
None.

## 5. Domain logic
Tooling only.

`scripts/verify.sh`, traceability area:
- `--all` runs **full mode** `php scripts/check-traceability.php` (today `--quick`). `--changed` keeps `--quick`.
- New gate `traceability:selftest` = `php scripts/test-traceability.php`, run on `--all` or when `scripts/(check|test)-traceability.php` changed.

CI: the existing `traceability` job runs `bash scripts/verify.sh --all --only traceability`, so it picks up full mode and the self-test with no edit to `ci.yml`.

Effect on later WPs: once a WP's row is `Review` (PR open) or `Done`, CI fails unless each ID assigned to that WP (backend or experience column) has a test whose name starts with the ID. This is the rule CLAUDE.md already states. CI now enforces it.

## 6. UI (if any)
None.

## 7. Security & privacy
No child data and no secrets. The self-test writes fixtures only under `sys_get_temp_dir()` and deletes them on exit.

## 8. Risks & open questions
- Q1 (owner): enforcing full mode in CI as above. Recommended: yes.
- Risk: the source comparison is strict. A future re-export (`split-spec.php`) that changes wording fails CI until the human moves the files. That is the intended behavior (deliverable 3).
- Risk: README format drift (e.g. a different ellipsis or separator) breaks parsing. Mitigation: parse failures are reported as explicit errors, never silently skipped.
