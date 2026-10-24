# Notes — WP-0.7

## Decisions applied
- D-01, D-02, D-03, D-04, D-05, D-06, D-07, D-08, D-09, D-10, D-11, D-12, D-14, D-26, D-28, D-30 (Default): applied as in design.md §1.
- Also consulted (Default): D-15, D-16, D-18, D-24, D-27, D-29. D-13 (Open) only needs a nullable `consents.verification_method`, as D-13 itself prescribes.
- D-37 (Approved 2026-10-24, option a): `student_availability` stores weekday + local time + family IANA zone.
- Design and tasks approved by the owner on 2026-10-24 («اوافق على المقترحات», approval page https://claude.ai/artifact/9NpNhMNxBqzodnAg14jF3z).

## Deviations / surprises
- Reads beyond "اقرأ فقط": `architecture.md` §3 (module → table ownership, referenced by ADR-001) and constitution Art. 5 §2 (exact ledger unique-key wording). Needed to define "per module" and the ledger index.
- spec/14 names ≈ 110 tables while its intro says "نحو 90".
- The literal unique key `(student_id, session_id, type)` on every ledger type would block a second REVERSAL in one session. The constitution limits it to "قيود الحصص"; design §3.3 applies it to the four session-linked types only.

- T1–T3: `scripts/test-erd.php` 52/52; a copy of the checker with the `_bps` rule disabled fails exactly that case (51/52), so the suite catches a broken check. The `erd` gate stays red until T16 (ERD documents are written in T4–T16).

- While writing (design §3 corrected to match):
  - `credit_lots` has no `source_entry_id`: with ADR-007's `credit_ledger_entries.lot_id` both FKs would form a cycle. The lot is inserted first; the purchase entry points to it (unique per lot).
  - spec `mfa_factors` merged into `users`: Fortify (ADR-002) keeps the TOTP secret, recovery codes and confirmation on `users`; R1 needs TOTP only (SEC-02).
  - `cache`, `cache_locks`, `jobs` are not created (Redis for cache and queues per `.env.example`); `job_batches` and `failed_jobs` stay.
  - `students.birth_month`, `birth_year`, `first_name_normalized` are nullable so erasure can empty them; required at creation (ONB-03).
  - Diagrams omit `created_by`/`updated_by` (every human-edited table points them at `users`); test case added (53 cases).
  - `audit_logs.request_id` renamed `request_ref` (outside identifiers end in `_ref`).
  - Three logs joined the append-only list: `join_token_log`, `recording_access_log`, `safeguarding_incident_actions`.
- Spec ambiguities left to the owning WP (no column change): dunning day origin vs grace (BIL-08, WP-2.4); DST notice 14 days (spec/21) vs `scheduling.dst_alert_days` 21 (WP-2.2); PAY-04 urgent-cover amount vs `teacher.substitute_per_session_jod` (WP-2.6, see D-02); whether unsampled reports skip «مُرسل» (RPT-05, WP-4.6); numbers stated without settings keys (support SLA 12 h / 1 h, waitlist hold 48 h, HELD_NO_SHOW 15 min wait, teacher late 5 min, staff idle 30 min); lead stages, safeguarding incident status, support thread status/categories (`TBD(WP-x.y)` in the ERD); retention periods not stated (applicants, teacher bank data after leaving, CRM notes, in-app notifications, cookie consents) — for the legal pack in WP-0.8.

## Verification evidence
```
(paste verify --all summary)
```
| Acceptance criterion | Evidence |
| --- | --- |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
1. …
