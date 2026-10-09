---
paths:
  - "apps/api/database/**/*.php"
---

# Migration rules

- Follow `docs/02-architecture/data-model-conventions.md` exactly: UUIDv7 `id`, `timestampTz`, `*_minor` + `currency`, CHECK constraints for enums, `archived_at`, scope columns, FKs with `restrictOnDelete()`.
- File name starts with the module: `YYYY_MM_DD_HHMMSS_<module>_<description>.php`.
- Never modify a migration that is already on `main`; add a new one. Destructive changes are expand → migrate data → contract across releases.
- Append-only tables (ledger, audit, wallet, consents, versions, events) call the `appendOnly()` helper to install the `forbid_mutation` trigger and revoke UPDATE/DELETE from `sura_app`. They have no `updated_at`.
- Unique constraints are part of correctness, not optimization: `(student_id, session_id, type)` on ledger entries, `idempotency_key`, `provider_ref`.
- Table names follow `docs/00-governance/glossary.md` (`class_sessions`, `classes`→`ClassGroup`, `curriculum_units`).
- Every migration runs on an empty database in the test suite; `down()` exists unless the migration is explicitly irreversible (document why).
