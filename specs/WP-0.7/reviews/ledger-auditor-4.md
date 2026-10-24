<!-- ledger-auditor report, WP-0.7 fourth pass (2026-10-24) -->

## Ledger audit, fourth pass: WP-0.7 (fix commit 12ca30c)

I edited no files. `php scripts/check-erd.php` reports `tables 130 … R1 covered 210/210 · pii columns 82 · erd OK`, and `php scripts/test-erd.php` reports `58/58 as expected`.

**Third-pass items: all five are resolved**
- BLOCKER (post-lock change on an expired lot): resolved for the −1 → −1 case. ledger.md:208 says the replacement uses the reversed entry's lot and that instant expiry is judged on the lot's net remaining after the transaction. Design §3.3 says the same (specs/WP-0.7/design.md:113), and ledger.md:208 calls for a WP-2.3 test of every mapping on an expired lot.
- MAJOR (manual −N): resolved. ledger.md:213 splits −N across lots FIFO, one entry per lot, never overdrawing, with key `manual:<uuid>:<lot_id>` (:209). +N is rejected when no lot is open.
- MINOR (UNIQUE targets): resolved. billing.md:320 adds `UNIQUE (id, currency)` on payments, and billing.md:500 adds `UNIQUE (id, family_id, currency)` on wallet_entries.
- MINOR (rebuild sources): resolved. ledger.md:161 now uses `payments.succeeded_at` (the column exists at billing.md:314) and the allocation order for `cycle_no`. ADR-007:12 adds "وقت نجاح الدفعة" (payment success time).

**New finding from the fix**
| Severity | file:line | Finding | Required fix |
|---|---|---|---|
| BLOCKER (narrow) / MAJOR | ledger.md:208, :213 | The rule "replacement uses the lot of the entry it reverses" holds only when the reversed entry was −1. When it was `EXTENSION_ABSENCE_EXCUSED` (qty 0, which still carries a lot because of :213), an EXCUSED → PRESENT/UNEXCUSED change posts −1 on that lot with nothing freed. (a) If the lot has expired, its credit was already taken by CREDIT_EXPIRY, so the student loses one extra credit (wrong balance). (b) If the lot is open but used up, its remaining goes to −1 and the next lot stays one too high, so D-28 conversion overpays the wallet. The daily check at :220 compares Σ lot remaining with the paid balance, and those still match, so nothing flags it. | Limit the rule: the replacement reuses the reversed entry's lot only for the quantity the reversal freed. Any extra charge goes FIFO to open lots and never overdraws. On an expired lot, absorb it into the expiry: reverse the original expiry and re-post it one smaller. The `expiry:<lot_id>:<source_entry_id>` key, with the triggering entry as source, already supports this. Add "remaining < 0 on any lot" to the daily check at :220. |

The schema needs no change for this fix. Only the rule text at :208 and its copy in design §3.3 need updating.

**Notes for WP-2.3 (tests, not findings)**
- Run every attendance-mapping transition, including 0 ↔ −1, on an open lot, a used-up lot and an expired lot.
- Reject a manual −N larger than the total remaining across all open lots, and test it.
- A REVERSAL of an EXTENSION entry has to undo or offset its `credit_lot_extensions` row and recompute `expires_at`.
- Concurrency: a manual split and a session charge on the same lot under the (`student_id`, `pool`) advisory lock.

Files reviewed:
- /home/abdul/sura-academy/docs/02-architecture/erd/ledger.md
- /home/abdul/sura-academy/docs/02-architecture/erd/billing.md
- /home/abdul/sura-academy/docs/02-architecture/adr/ADR-007-credit-ledger.md
- /home/abdul/sura-academy/specs/WP-0.7/design.md
- /home/abdul/sura-academy/specs/WP-0.7/notes.md
