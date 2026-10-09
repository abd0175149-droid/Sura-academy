---
name: sura-status
description: Show Sura Academy progress — current focus, work package statuses, open and due decisions, recent commits — and name the next work package to run.
disable-model-invocation: true
---

## Data
!`sed -n '1,90p' docs/03-plan/status.md 2>/dev/null || true`
!`grep -E '^\| D-[0-9]+' docs/00-governance/decisions.md 2>/dev/null | grep -E '\| (Open|Default)' || true`
!`git log --oneline -8 2>/dev/null || true`
!`git status --short 2>/dev/null | head -20 || true`

## Instructions
Report in Arabic, ≤ 25 lines:
1. Current phase and next gate with its date (from the focus block).
2. WPs In progress / Design / Blocked / Review, with blocker reasons.
3. The next WP: the first WP in the order table of `docs/03-plan/master-plan.md` whose status is `Not started` and whose dependencies are all `Done`. Say the exact command `/sura-wp <id>`.
4. Decisions with status Open, and Default decisions whose deadline week is ≤ current week + 2.
5. Uncommitted changes, if any, and whether they belong to a WP branch.
Do not modify any file.
