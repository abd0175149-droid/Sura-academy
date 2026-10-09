---
name: sura-wp
description: Execute one Sura Academy work package end-to-end — preflight, design for approval, test-first implementation, verification, reviews and close-out. Invoke as /sura-wp WP-2.3 (or HF-001 for a hotfix). Re-invoking on a started WP resumes from its tasks.md.
argument-hint: "[WP-id e.g. WP-2.3 | HF-001]"
disable-model-invocation: true
---

# Execute work package $ARGUMENTS

Follow the steps in order. Talk to the user in Arabic; write code and technical files in English. If anything below conflicts with CLAUDE.md or the constitution, those win — stop and ask.

## Step 0 — Resume check
If `specs/$ARGUMENTS/tasks.md` exists, read it and `specs/$ARGUMENTS/notes.md`, report done/remaining tasks in ≤ 6 lines, and continue at the first unchecked task (Step 3). If design is not yet approved (status `Design` in status.md), go back to the end of Step 2 and ask for approval.

## Step 1 — Preflight (read-only)
1. Read `docs/03-plan/status.md`. Every WP in "يعتمد على" for $ARGUMENTS must be `Done`, unless the user explicitly allows starting early (record that in notes.md). Gates named as dependencies (ب2 …) must be recorded as passed. Otherwise stop and report.
2. Locate the WP section: `grep -n "### $ARGUMENTS" docs/03-plan/phase-*.md docs/03-plan/hotfixes.md`. Read only that section.
3. Read every file in its "اقرأ فقط" row, nothing else under `docs/`. For long spec files or many IDs, delegate to the `spec-reader` agent with the WP id and use its brief.
4. Decisions: for each D-xx in the WP row, read its detail in `docs/00-governance/decisions.md`. `Approved`/`Default` → apply literally and note it. `Open` → stop and ask the user. A rule you need but cannot find → append a new `D-xx` (Open) and stop.
5. `git status` must be clean. Create `wp/$ARGUMENTS-<short-slug>` from an up-to-date `main`.
6. Update the WP row in status.md to `Design`. Tell the user in ≤ 10 lines: scope, decisions applied, risks, anything unclear.

## Step 2 — Design (no production code)
1. Create `specs/$ARGUMENTS/design.md` from [templates/design.md](templates/design.md) and `specs/$ARGUMENTS/tasks.md` from [templates/tasks.md](templates/tasks.md), plus an empty `notes.md` from [templates/notes.md](templates/notes.md).
2. Every requirement ID in scope appears in the design's traceability table with planned code location and planned test names.
3. API changes are listed as concrete contract diffs (paths, schemas, error codes) before any implementation task.
4. Tasks: ≤ ~2 hours each, ordered contract → tests → implementation → UI → docs, each with its verification command.
5. STOP. Ask: «هل توافق على التصميم والمهام؟ يمكنك طلب تعديلات قبل البدء.» Do not write production code before explicit approval. Apply requested edits, then mark status `In progress`.

## Step 3 — Implement, one task at a time
1. Write the failing test(s) first, named `<REQ-ID> <behavior>`. Run them and see them fail for the right reason.
2. Implement the minimum to pass, following `.claude/rules/` and the domain skills.
3. `bash scripts/verify.sh --changed` until green. Never weaken, skip or delete tests; never lower thresholds.
4. Tick the task in tasks.md; append to notes.md any decision/default applied (D-xx) and anything surprising.
5. Commit: `<type>(<module>): <summary> [$ARGUMENTS][<IDs>]`.
Stop and ask when: an Open decision is needed; sources conflict; a change is needed outside the WP's modules beyond the integration points in design.md; a dependency not listed in ADR-002/003 is needed; anything would touch real money, real accounts or real personal data; a test unrelated to this WP fails.

## Step 4 — Verify
1. `bash scripts/verify.sh --all` → paste the summary block into notes.md.
2. Contract changed → `bash scripts/gen-api-client.sh`, contract tests green, regenerated client committed.
3. UI changed → goldens updated only for intended changes (list each in notes.md).
4. For each acceptance criterion of the WP, record evidence in notes.md (test name, command output, measurement, or screenshot path).

## Step 5 — Review
Run, in parallel, the agents listed in the WP's "المراجعات" row plus `traceability-checker`. Fix every BLOCKER and MAJOR; record MINOR items in notes.md with a decision (fixed / deferred with reason). Re-run the agents that reported BLOCKERs.

## Step 6 — Close
1. `php scripts/check-traceability.php --wp $ARGUMENTS` passes.
2. Update status.md (row → `Review`, date, branch) and the focus block (next WP from `docs/03-plan/master-plan.md` order); set this WP's IDs to `Implemented` in traceability.md.
3. Write Arabic manual demo steps in notes.md (numbered, runnable by the user).
4. Commit; ask permission to push; open the PR with [templates/pr.md](templates/pr.md) via `gh pr create`.
5. Report to the user in Arabic: what was built, evidence, what to check manually, the next WP. Suggest `/clear` before the next WP.
