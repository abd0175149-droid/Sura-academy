<!-- ledger-auditor-2 report, WP-0.7 second pass (2026-10-24) -->

## Ledger audit, second pass: WP-0.7 (docs only, fix commit 75434bd)

I edited no files. Both checkers pass: `php scripts/check-erd.php` prints `tables 130 (modules 11) · spec/14 mapped 110/110 · migration order 130 · R1 covered 210/210 · pii columns 79 · erd OK`, and `php scripts/test-erd.php` prints `57/57 as expected`.

Note on the count: notes.md says the first pass had 15 MAJORs, but the report table at specs/WP-0.7/reviews/ledger-auditor-1.md lists 14. All 14 are checked below.

**Summary:** every first-pass BLOCKER and MAJOR is resolved in the ERD. The fixes did add one new narrow BLOCKER and three new MAJORs, so the ledger design is not ready to sign off yet.

### First-pass BLOCKER and MAJOR status

| # | First-pass finding | Status | Evidence |
|---|---|---|---|
| B1 | A→B→A re-post as ADJUSTMENT_MANUAL escapes the key | RESOLVED (but see new BLOCKER N1 and MAJOR N2) | docs/02-architecture/erd/ledger.md:6, :199, :200 (`reversal:<reverses_entry_id>`); ADR-007-credit-ledger.md:12; design.md §3.3 |
| B2 | compensation_lines unique per teacher allows double pay | RESOLVED | teachers.md:370 `UNIQUE (session_id, kind) WHERE session_id IS NOT NULL AND kind <> 'adjustment'` |
| M1 | integer unit price forces intermediate rounding | RESOLVED (stale references remain, see N4 and MINOR 1) | ledger.md:141, :157 |
| M2 | credit_lots mutable and called "derived" | RESOLVED in the ERD (but conflicts with ADR-007 and D-05, see N4) | ledger.md:8, :146, :154; credit_lot_extensions at ledger.md:159-173 with `UNIQUE (lot_id, subscription_event_id)` and an exact interval |
| M3 | lot not tied to its payment; renewals unguarded | RESOLVED | ledger.md:137-138, :152 (`UNIQUE (payment_allocation_id)`, `UNIQUE (subscription_id, cycle_no)`), :200 `purchase:<payment_allocation_id>`; billing.md:278, :288 |
| M4 | conventions and ADR-007 §6 still state the old key | RESOLVED | data-model-conventions.md:77; ADR-007-credit-ledger.md:12 |
| M5 | two live attendance-type entries per session | RESOLVED (but see N2 and MINOR 3) | ledger.md:232, :239, :211 |
| M6 | reversal integrity and pool mixing are app-only | RESOLVED | ledger.md:202 (composite FK; target `UNIQUE (id, student_id, pool)` at :152), :203-206 |
| M7 | a second wallet conversion per lot is possible | RESOLVED | ledger.md:201, :200 `conversion:<lot_id>` |
| M8 | wallet has no per-credit tracking | RESOLVED (see MINOR 7) | billing.md:489, :501 |
| M9 | wallet_entries has no rule_version_id | RESOLVED | billing.md:490 |
| M10 | no place to park an amount or currency mismatch | RESOLVED (see MINOR 6) | billing.md:309-310 |
| M11 | invoices mutable; duplicate invoice per payment | RESOLVED (see MINOR 8) | billing.md:403, :404 (totals CHECK), :405, :472 |
| M12 | compensation_lines has no idempotency key; approved history mutable | RESOLVED (see MINOR 9) | teachers.md:362, :371, :372, :340 |
| M13 | no lock targets named | RESOLVED | ledger.md:9 (advisory lock on student and pool); billing.md:501 (family and currency), :473 (refunds lock the `payments` row and check the refund total) |
| M14 | `end_processed_at` vs `ledger_processed_at`; posting moment unclear | RESOLVED | scheduling.md:291; ledger.md:7; matches phase-2-domain-engines.md:160 |

### New findings

| Severity | file:line | Finding | Required fix |
|---|---|---|---|
| BLOCKER (narrow path) | docs/02-architecture/erd/ledger.md:199-200 | The new "reverse the reversal" chain clashes with the new rule "a credit returned to an already expired lot expires at once". Example on an expired lot: A = CONSUMPTION −1. A post-lock edit posts R1 = +1, so the expiry job posts E = `expiry:<lot>:<R1>` −1. Editing back posts R2 = REVERSAL of R1, −1. The net is A + R1 + E + R2 = −2 for one session; it should be −1. The same happens when a COMPENSATION_ACADEMY +1 is posted to an expired lot, expires at once, and is later reversed (net −1 instead of 0). The result is a wrong balance, and the expired lot's remaining goes to −1. | Add a rule (and later a test): reversing an entry whose credit was expired at once (key `expiry:<lot>:<that entry>`) must first reverse that expiry entry, in the same transaction and under the same advisory lock. In the example, R2 is then matched by a REVERSAL of E, giving a net of −1. Document this in ledger.md:199 and design §3.3. |
| MAJOR | ledger.md:232, :239 | `session_attendance.ledger_entry_id` is described as the "current effective **root** entry", and :239 says a post-lock change "reverses `ledger_entry_id` first". After a reinstatement (A, R1, R2) the root A is already reversed by R1, so a third edit tries to reverse A again and fails on `UNIQUE (reverses_entry_id)`. The intended correction flow therefore breaks on the second round of edits (A→B→A→B). It fails safely, but it pushes implementers toward workarounds. | Make the column point to the chain **tip** (the latest live entry of the effective chain), or change :239 to "reverse the tip of the chain rooted at `ledger_entry_id`". Reinstating B means reversing RB, not posting B again; spell that out too. |
| MAJOR | ledger.md:204 | The `lot_id NOT NULL` CHECK leaves out paid-pool `COMPENSATION_ACADEMY` and `ADJUSTMENT_MANUAL`. Lot remaining (:156) then stops matching the pool balance. A −N manual adjustment without a lot leaves the lot's remaining too high, so D-28 conversion (:157) overpays the wallet (lost money) and expiry removes credits that were already taken. A +1 compensation without a lot never expires and is never converted. (This existed before the fix; I missed it in the first pass.) | Require `lot_id` for paid COMPENSATION_ACADEMY (the lot of the entry that charged the session) and for paid ADJUSTMENT_MANUAL when any lot exists (FIFO lot for −N; a stated rule for +N). Otherwise, define conversion and expiry as limited by the pool balance and add a reconciliation of Σ lot remaining against the pool balance. |
| MAJOR | ADR-007-credit-ledger.md:10; decisions.md:77 (D-05); specs/WP-0.7/design.md:25, :35, :76 | The ERD now makes `credit_lots` a source table with facts the ledger does not hold (`amount_paid_minor`, `payment_allocation_id`, `cycle_no`, `base_expires_at`). ADR-007 §5 ("تُشتق من الدفتر") and D-05 ("جدول مساعد يُشتق من الدفتر") still say lots are derived from the ledger. That is a conflict with higher-ranked sources, and it is recorded neither as an ADR note (as was done for §6) nor as a D-xx row. design.md still lists `frozen_days` and `unit_price_minor` and calls lots "derived and rebuildable". An engine built to the ADR could rebuild lots and lose the payment amount and expiry history. | Add a WP-0.7 note under ADR-007 §5, like the §6 note, or append an Open D-xx row for the owner. Update design.md rows D-05 and D-28 and the example at :76. |
| MINOR | billing.md:485 | `wallet_entries.source_ledger_entry_id` still says "remaining sessions × `credit_lots.unit_price_minor`", a column that no longer exists. | Point it at the formula in ledger.md:157. |
| MINOR | ledger.md:146, :154 | The lots trigger lets UPDATE set `expires_at` to any value. Nothing ties it to `base_expires_at + Σ credit_lot_extensions`. | Have the trigger check `NEW.expires_at = base_expires_at + Σ(resumed_at − frozen_from_at)`, or recompute the value from an AFTER INSERT trigger on credit_lot_extensions. |
| MINOR | ledger.md:211 | The reconciliation "attendance-type sum is 0 or −1" cannot see qty-0 `EXTENSION_ABSENCE_EXCUSED` (live CONSUMPTION −1 plus live EXTENSION 0 passes). It also does not say how REVERSAL rows count. | Check "at most one live attendance-type chain per (student, session)", counting each REVERSAL under its chain root's type. |
| MINOR | ledger.md:157 | The conversion rounding rule is postponed to "WP-2.4". Who gets the remainder is a business rule. | Record it as a D-xx (or D-28 detail) row before WP-2.4. |
| MINOR | platform.md:160-174; ADR-007-credit-ledger.md:7 | `record_notes` "never updated by the app" is not enforced, so the written reason for a manual adjustment or reversal can be edited. The DB requires only `reason_code`; whether a note exists (ATT-05, RBAC-04 written reason) is app-only. ADR-007 §2 still lists a `reason` field. | Add a trigger: UPDATE and DELETE allowed only for `sura_retention` (redaction or erasure). Make the note part of the same action for ADJUSTMENT_MANUAL and REVERSAL, with a test in WP-2.3. Add a line to ADR-007 §2 about `reason_code` + `record_notes`. |
| MINOR | billing.md:309-310 | `review_status` has no default, no CHECK that `review_reason_code` is set when it is not `none`, and no rule that allocations, lots and activation wait while it is `needs_review`. | Default `'none'`; add `CHECK ((review_status = 'none') = (review_reason_code IS NULL))`. State that lot creation requires `status = 'succeeded' AND review_status <> 'needs_review'`. |
| MINOR | billing.md:489, :501 | No CHECK that PAYMENT_USE and EXPIRY carry `credit_entry_id` while credit types do not. Nothing ties a linked credit to the same family and currency. A REVERSAL of a PAYMENT_USE is not in the per-credit remaining formula. | Add CHECKs and a composite FK on (credit_entry_id, family_id, currency). Include REVERSALs of linked rows in "remaining per credit". |
| MINOR | billing.md:394, :405 | `issued_at` is NOT NULL, so an invoice counts as issued from the moment it is inserted. The trigger that "guards invoice_lines of an issued invoice" must still allow line INSERTs in the issuing transaction, and nothing ties Σ lines to `subtotal_minor`. | Add a deferred constraint trigger: Σ line amounts = subtotal at commit, and no line INSERT after the issuing transaction. |
| MINOR | teachers.md:372 | The freeze trigger rejects UPDATE and DELETE but not INSERT of a line into a non-draft statement. Such a line sits outside the approved `total_minor`, so it is either never paid or leaves the totals mismatched. | Also reject INSERT (and setting `statement_id`) for a non-draft statement. |
| MINOR | billing.md:332; teachers.md:307 | First-pass MINORs only partly done: `payment_allocations.currency` "equals the payment currency" has no composite FK, and `compensation_rules` has no `CHECK (currency = 'JOD')`. | Add both. |
| MINOR | .claude/skills/credit-ledger/SKILL.md:59, :61, :71 | The skill disagrees with the ERD on key formats (`att:`, `purchase:<payment_id>`, `expiry:<lot_id>`, `comp:`) and still says the trial pool has its own lot. Only the trial-lot point is recorded as deferred. | Update the skill in WP-2.3 to the ERD formats, and list the key formats in the notes.md deferral row too. |

### Verified OK
- **FK order and cycles:** payments #93 has no FK to subscriptions, so `subscriptions.pending_renewal_payment_id` (#94) creates no cycle. Order is payment_allocations #95 → subscription_events #96 → credit_lots #103 → credit_lot_extensions #104 → credit_ledger_entries #105 → session_attendance #106 → wallet_entries #109, and check-erd proves every FK target comes first.
- **Reverse-the-reversal mechanics:** `validate_reversal()` copies student, pool, lot and session and negates qty. `UNIQUE (reverses_entry_id)` keeps chains linear, and the key `reversal:<reverses_entry_id>` is deterministic. Posting A again is blocked by the session key, which forces the chain path. The one gap is the expiry interaction (N1).
- **credit_lot_extensions:** append-only with `forbid_mutation` (conventions:54), idempotent per Resume event, exact interval with a CHECK.
- **Append-only trigger split:** ledger, wallet, subscription_events, version tables and credit_lot_extensions block DELETE for every role. Retention may delete only from the personal-data and log tables (conventions:54-55). `audit_log_personal_data` is now append-only.
- **Invoice numbering:** `invoice_number_series` has `UNIQUE (code)` and takes `next_number` under `FOR UPDATE` in the issuing transaction (billing.md:431-441).
- **Refunds and subscription events:** refunds `UNIQUE (payment_id, provider_ref)` and `UNIQUE (credit_note_invoice_id)`; `subscription_events` `UNIQUE (subscription_id, payment_id, event)`.
- **rule_versions:** has `effective_at` and a non-unique hash, so the full timeline is kept (platform.md:116, :120).
- **Webhooks:** the PayTabs event-id note is in place (platform.md:309).
- **Sign, pool, reason and actor CHECKs** are present (ledger.md:203-208). The scheduling outcome/status CHECK is present (scheduling.md:301).
- **ATT-07:** snapshots are rebuilt by a full SUM of the ledger and checked daily against it (ledger.md:5, :250, :257).
- **Money:** all money is integer minor units with a currency; compensation is JOD-only on statements, lines and payouts.

Files reviewed:
- /home/abdul/sura-academy/docs/02-architecture/erd/ledger.md
- /home/abdul/sura-academy/docs/02-architecture/erd/billing.md
- /home/abdul/sura-academy/docs/02-architecture/erd/teachers.md
- /home/abdul/sura-academy/docs/02-architecture/erd/platform.md
- /home/abdul/sura-academy/docs/02-architecture/erd/scheduling.md
- /home/abdul/sura-academy/docs/02-architecture/erd/README.md
- /home/abdul/sura-academy/docs/02-architecture/data-model-conventions.md
- /home/abdul/sura-academy/docs/02-architecture/adr/ADR-007-credit-ledger.md
- /home/abdul/sura-academy/docs/00-governance/decisions.md
- /home/abdul/sura-academy/specs/WP-0.7/design.md
- /home/abdul/sura-academy/specs/WP-0.7/notes.md
- /home/abdul/sura-academy/.claude/skills/credit-ledger/SKILL.md
