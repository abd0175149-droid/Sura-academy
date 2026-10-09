---
paths:
  - "docs/**"
  - "specs/**"
---

# Docs and plan rules

- `docs/01-product/**` and `docs/00-governance/constitution.md` are human-only (hook-enforced).
- `docs/03-plan/status.md`: update only the row of the current WP and the focus block (≤ 7 lines). Keep the table format.
- `docs/03-plan/traceability.md`: change only the status column of IDs in the current WP. Do not reorder columns.
- `docs/00-governance/decisions.md`: you may append a new `D-xx` row with status Open and a detail section; never change the status of an existing decision.
- New architecture decisions go in a new `docs/02-architecture/adr/ADR-0xx-<slug>.md` with status Proposed until the user accepts.
- `specs/WP-x.y/` holds `design.md`, `tasks.md`, `notes.md` for the WP; keep them current.
- Arabic docs use plain Arabic with English technical terms in backticks.
