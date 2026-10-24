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
Full reports in `specs/WP-0.7/reviews/`: pass 1 `ledger-auditor-1.md` (2 BLOCKER, 14 MAJOR), `privacy-reviewer-1.md` (0 BLOCKER, 13 MAJOR); pass 2 `ledger-auditor-2.md` (all pass-1 items resolved; 1 new BLOCKER, 3 MAJOR), `privacy-reviewer-2.md` (all resolved; 2 new MAJOR); pass 3 `ledger-auditor-3.md` (all pass-2 items resolved; 1 narrow BLOCKER, 1 MAJOR), `privacy-reviewer-3.md` (0 BLOCKER, 0 MAJOR, 2 MINOR).

| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |
| ledger-auditor | BLOCKER | A→B→A re-post as ADJUSTMENT_MANUAL escapes the key | fixed: reinstate by reversing the reversal; design §3.3, ledger.md, ADR-007 §6 note |
| ledger-auditor | BLOCKER | `compensation_lines` unique per teacher allows double pay | fixed: `UNIQUE (session_id, kind)` except adjustments |
| ledger-auditor | MAJOR | integer unit price rounds before D-28 conversion | fixed: `credit_lots.amount_paid_minor`, one rounding at the end |
| ledger-auditor | MAJOR | `credit_lots` mutable "derived" table | fixed: source table, update trigger, append-only `credit_lot_extensions` |
| ledger-auditor | MAJOR | lot not tied to its payment; renewals unguarded | fixed: `payment_allocation_id` unique, `cycle_no`, `subscriptions.pending_renewal_payment_id`, key formats |
| ledger-auditor | MAJOR | conventions and ADR-007 still state the old key | fixed: conventions §indexes, ADR-007 §6 note |
| ledger-auditor | MAJOR | two live attendance-type entries per session | fixed: `session_attendance.ledger_entry_id` under row lock + daily reconciliation |
| ledger-auditor | MAJOR | reversal integrity and pool mixing app-only | fixed: `validate_reversal()`, composite FK, pool/lot/sign CHECKs |
| ledger-auditor | MAJOR | second wallet conversion per lot possible | fixed: `UNIQUE (lot_id) WHERE type = 'CONVERSION_TO_WALLET'` |
| ledger-auditor | MAJOR | wallet has no per-credit tracking | fixed: `wallet_entries.credit_entry_id` |
| ledger-auditor | MAJOR | wallet without `rule_version_id` | fixed |
| ledger-auditor | MAJOR | no place for amount/currency mismatch | fixed: `payments.review_status` flag beside the state machine (no new state, so no D-xx) |
| ledger-auditor | MAJOR | invoices mutable, duplicate invoice per payment | fixed: issue trigger, `UNIQUE (payment_id) WHERE kind = 'invoice'`, refunds `UNIQUE (credit_note_invoice_id)` |
| ledger-auditor | MAJOR | `compensation_lines` without idempotency key; approved lines mutable | fixed: key + freeze triggers on lines and statements |
| ledger-auditor | MAJOR | no lock targets named | fixed: advisory locks per (student, pool) and (family, currency); refunds lock the payment |
| ledger-auditor | MAJOR | `end_processed_at` vs WP-2.3 `ledger_processed_at`; posting moment unclear | fixed: renamed; entries post once at lock |
| ledger-auditor | MINOR | reason/actor/sign CHECKs; remaining formula; snapshot rebuild; rule_versions timeline; trigger split; outcome/status CHECK; PayTabs event id; invoice counter, totals, refund provider ref; duplicate PaymentSucceeded; JOD CHECKs | fixed |
| ledger-auditor | MINOR | credit-ledger skill says the trial pool has its own lot | deferred to WP-2.3: the ERD keeps `CHECK (pool = 'paid')` on lots; align the skill text when the engine is built |
| ledger-auditor | MINOR | discount rate in rule and setting | fixed by note: the rule row copies the setting version |
| privacy-reviewer | MAJOR | retention cannot delete from append-only tables | fixed: two trigger functions, `sura_retention` purge only (conventions) |
| privacy-reviewer | MAJOR | free text in append-only tables | fixed: `reason_code` + erasable `record_notes` |
| privacy-reviewer | MAJOR | untagged free text | fixed: tagged and registered; checker rule E8 (free text needs PII or `no-PII:`), 57 self-test cases |
| privacy-reviewer | MAJOR | `teachers.status_reason` leaks safeguarding detail | fixed: `status_reason_code`; incident holds `subject_teacher_id` |
| privacy-reviewer | MAJOR | registry cannot locate rows; two config shapes | fixed: Locate by column (checked), single shape in pii-registry.md, design §3.4 aligned |
| privacy-reviewer | MAJOR | recordings erasure and consent evidence | fixed: erasure deletes attended sessions' recordings (D-14, D-11 literal); `recordings.consent_snapshot` |
| privacy-reviewer | MAJOR | retention destroys safeguarding evidence | D-38 Open; `legal_holds` modelled; first-name snapshot on the incident |
| privacy-reviewer | MAJOR | student IP in audit | fixed: never recorded for a student actor (WP-1.3 test) |
| privacy-reviewer | MAJOR | core child data retention undefined | fixed as proposal (`departed_at` rules) + legal list for WP-0.8 |
| privacy-reviewer | MAJOR | export file retention | fixed: signed link ≤ 10 min, logged, deleted after 30 days (proposed) |
| privacy-reviewer | MAJOR | chat editable | fixed: `session_chat_messages` append-only |
| privacy-reviewer | MAJOR | VPC gate not stated | fixed: invariant in `students`, WP-1.5 test; ops never create children |
| privacy-reviewer | MAJOR | fallback link to third parties | fixed: allow-list to our own video project, in-app route in child mode |
| privacy-reviewer | MINOR | marketing consent | D-39 Open |
| privacy-reviewer | MINOR | the other 15 (append-only audit side table, retention rows, partial PII columns, webhook subjects, recipients table, cookie consents append-only, PIN pepper, region CHECK + `copied_at`, admin access reason, TCH-03 projection, STU-10 note, immutable incident description, `child_age`, converted leads, `notifiable_id` text) | fixed |
| ledger-auditor (pass 2) | BLOCKER | reverse-the-reversal vs instant expiry of a credit returned to an expired lot charges twice | fixed: reversing such an entry first reverses its expiry entry (ledger.md) |
| ledger-auditor (pass 2) | MAJOR | `ledger_entry_id` named the chain root; a second round of edits fails | fixed: it is the chain tip; edits reverse the tip |
| ledger-auditor (pass 2) | MAJOR | paid COMPENSATION/ADJUSTMENT entries without a lot break lot remaining | fixed: `CHECK (pool <> 'paid' OR lot_id IS NOT NULL)` + lot choice rules + reconciliation |
| ledger-auditor (pass 2) | MAJOR | lots as source table conflict with ADR-007 §5 / D-05 «derived» | fixed: kind `derived` with its rebuild sources listed; ADR-007 §5 note; design D-05/D-28 rows |
| ledger-auditor (pass 2) | MINOR | stale `unit_price_minor` text, `expires_at` recompute, reconciliation wording, record_notes trigger + ADR-007 §2 note, review_status default/CHECK, wallet CHECK + composite FK, invoice line deferred check, statement INSERT freeze, allocation currency FK, JOD on rules | fixed |
| ledger-auditor (pass 2) | MINOR | conversion rounding is a business rule | D-40 Open (before WP-2.4) |
| ledger-auditor (pass 2) | MINOR | credit-ledger skill key formats (`att:`, `purchase:<payment_id>`, `expiry:<lot_id>`, `comp:`) and trial lot differ from the ERD | deferred to WP-2.3: align `.claude/skills/credit-ledger/SKILL.md` to `erd/ledger.md` when the engine is built |
| privacy-reviewer (pass 2) | MAJOR | `safeguarding_incident_actions` under the strict trigger cannot be purged after 7 years | fixed: moved to `forbid_mutation_except_retention` |
| privacy-reviewer (pass 2) | MAJOR | `record_notes` not locatable by subject; NOT NULL body with `erase` | fixed: `subject_student_id` / `subject_user_id` / `subject_family_id` FKs, action `delete_row`, retention row. A checker rule against polymorphic locate paths was not added: direct person morphs (`notifiable_id`, `sender_id`) are valid paths |
| privacy-reviewer (pass 2) | MINOR | record_notes/legal_holds triggers; holds logic pending D-38; free-text rule wording; retention trigger SQL (SECURITY INVOKER); recipient regions + AWS hosting; neutral `restricted` reason code; `title`/`exception`/`error` columns; E8 bare `no-PII`; TCH-03 learning notes column; VPC line in design §3.3 | fixed (58 self-test cases) |
| ledger-auditor (pass 3) | BLOCKER | post-lock type change on an expired lot: replacement takes another lot, session charged twice | fixed: replacement uses the reversed entry's lot; instant expiry judged on net lot remaining after the transaction; WP-2.3 tests every attendance mapping on an expired lot |
| ledger-auditor (pass 3) | MAJOR | manual −N larger than the FIFO lot; +N with no open lot | fixed: split FIFO one entry per lot (`manual:<uuid>:<lot_id>`), never overdraw; +N rejected without an open lot |
| ledger-auditor (pass 3) | MINOR | composite FK targets lacked UNIQUE; rebuild sources for `effective_at` and `cycle_no` | fixed |
| privacy-reviewer (pass 3) | MINOR | `record_notes` could have no subject; design wording of the free-text exception | fixed |

## خطوات العرض اليدوي
1. …
