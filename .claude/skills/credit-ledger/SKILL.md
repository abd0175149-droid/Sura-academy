---
name: credit-ledger
description: Rules for Sura Academy's credit ledger, attendance-to-credit effects, credit lots and expiry, reversals and idempotency. Use whenever code touches attendance, absence requests, credits, balances, session-end processing, subscriptions consuming credits, wallet conversion, or ledger migrations — even for "small" changes, because ledger mistakes become double charges.
paths:
  - "apps/api/app/Modules/Ledger/**"
  - "apps/api/app/Modules/Billing/**"
  - "apps/api/app/Modules/Live/**/EndSession*"
  - "apps/api/database/migrations/*ledger*"
---

# Credit ledger

Sources: `spec/09-attendance-ledger-rules.md`, ADR-007, decisions D-05, D-06, D-08, D-28, CR-004.

## Invariants (never break)
1. `credit_ledger_entries` is append-only (DB trigger + revoked privileges). No update/delete/save-after-change on an entry, ever.
2. Balance(student, pool) = SUM(qty). Snapshots are cache; decisions read the ledger (or a fresh snapshot rebuilt inside the transaction).
3. Paid pool and trial pool never mix (BR-018). `pool` ∈ {paid, trial}.
4. One entry per `(student_id, session_id, type)` for session-linked types (unique constraint). Every write has an `idempotency_key`.
5. Every entry stores `rule_version_id` (the settings snapshot in force at the event time), `actor_id` (null for system), `reason` (required for manual types).
6. Corrections: post `REVERSAL` (qty = −original.qty, `reverses_entry_id` = original) then the correct entry. Never "fix" in place.
7. The paid pool never goes negative except through `ADJUSTMENT_MANUAL` with a written reason.

## Entry types
| Type | qty | Created when | Source |
| --- | --- | --- | --- |
| CREDIT_PURCHASE | +sessions_per_cycle | payment succeeded (subscription S2/S7/S9) | BIL-06/07 |
| CREDIT_TRIAL | +1 (trial pool) | trial approved/booked; 2nd only by ops (D-29) | ONB-08/09, BR-018 |
| CONSUMPTION_ATTENDANCE | −1 | session HELD and student PRESENT/LATE/PARTIAL | BR-007 |
| ABSENCE_UNEXCUSED | −1 | ABSENT_UNEXCUSED | BR-006 |
| EXTENSION_ABSENCE_EXCUSED | 0 | ABSENT_EXCUSED (tracking only) | ATT-03 |
| COMPENSATION_ACADEMY | +1 | a session was charged, then an academy fault was established (spec ledger types table); cancellations by teacher/holiday are not charged at all and get a make-up session instead | spec/09 |
| ADJUSTMENT_MANUAL | ±N | authorized staff with reason | ATT-05 |
| CREDIT_EXPIRY | −remaining | lot expired (D-05) | credits.expiry_days_after_cycle |
| REVERSAL | −original | correction | ATT-05 |
| CONVERSION_TO_WALLET | −remaining paid | partial-use cancellation (behind CR-004 flag) | D-28 |

## Attendance status → entry (session outcome HELD)
| Status | Rule | Entry |
| --- | --- | --- |
| PRESENT | joined ≤ `attendance.late_after_min` after start and present ≥ `attendance.partial_below_pct` | CONSUMPTION_ATTENDANCE −1 |
| LATE | joined after `late_after_min` | CONSUMPTION_ATTENDANCE −1 |
| PARTIAL | present < `partial_below_pct` | CONSUMPTION_ATTENDANCE −1 |
| ABSENT_EXCUSED | request ≥ `attendance.excuse_cutoff_hours` before start (UTC) AND within cap (D-06) | EXTENSION_ABSENCE_EXCUSED 0 |
| ABSENT_UNEXCUSED | no request, late request, or over cap | ABSENCE_UNEXCUSED −1 |

## Session outcome → effect
| Outcome | Students | Teacher pay |
| --- | --- | --- |
| HELD | per status table | executed |
| HELD_NO_SHOW | nobody joined after 15 min wait: everyone charged (BR-010) | executed |
| CANCELLED_TEACHER | no charge; make-up session scheduled (BR-008) | not paid |
| CANCELLED_HOLIDAY | no charge; make-up scheduled (BR-008) | paid when make-up held |
| POSTPONED_GROUP | > 50% of the class excused: nobody charged; make-up (BR-009) | paid when make-up held |
| TECH_FAILURE | no charge if < half the session held; ops decides | ops decision |

## Credit lots (D-05)
- Each CREDIT_PURCHASE creates a lot: `effective_at`, `cycle_end_at = effective_at + subscription.cycle_weeks`, `expires_at = cycle_end_at + credits.expiry_days_after_cycle`, `remaining` derived from entries linked by `lot_id`.
- Consumption picks the open lot with the earliest `expires_at` (FIFO by expiry). Trial pool has its own lot.
- Freeze extends `expires_at` of all open lots by the actual frozen duration (set on resume).
- Daily expiry job: for each lot with `expires_at < now` and remaining > 0 → CREDIT_EXPIRY −remaining (idempotency key `expiry:<lot_id>`).

## Excused cap (D-06)
Count ABSENT_EXCUSED among the student's last 8 scheduled sessions in that class (including the requested one). If adding this one exceeds `attendance.max_excused_per_8` (2) → treat as unexcused and tell the parent before submit (PAR-05 explains effect).

## Session-end processing (ATT-08)
- Triggered by SessionEnded (or ops close). Job key = session_id. Inside a transaction: `SELECT … FOR UPDATE` the session; if `ledger_processed_at` is set → exit; post all entries; set `ledger_processed_at`; write outbox events. Retries are safe.
- Teacher confirms within `attendance.teacher_confirm_hours` (12); after that the suggestion is auto-approved; then the session is locked. Edits after lock = ATT-05 (permission + reason + REVERSAL).

## Idempotency keys
`att:<session_id>:<student_id>:<type>` · `purchase:<payment_id>` · `trial:<trial_id>` · `expiry:<lot_id>` · `comp:<session_id>:<student_id>` · `manual:<uuid from request Idempotency-Key>`.

## Tests that must exist
- Property test: random sequences (seeded) of purchases, attendance, reversals, expiries → balance = SUM, no paid pool < 0 without manual entry.
- Every row of both tables above; boundary: request exactly 24h before start (allowed) vs 23h59m (late).
- Double processing (two jobs, same session) → one set of entries.
- Reversal then correct entry; freeze then expiry; trial isolation; DB rejects UPDATE/DELETE.
