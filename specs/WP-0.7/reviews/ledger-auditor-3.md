<!-- ledger-auditor-3 report, WP-0.7 third pass (2026-10-24) -->

## Ledger audit, third pass: WP-0.7 (fix commit b354302)

I edited no files. Both checkers pass. `php scripts/check-erd.php` prints `tables 130 (modules 11) · spec/14 mapped 110/110 · migration order 130 · R1 covered 210/210 · pii columns 82 · erd OK`, and `php scripts/test-erd.php` prints `58/58 as expected`.

**Summary:** all four second-pass items and all the MINORs marked fixed are present. But the fix for N1 covers only one path. A second path with the same double charge is still open (new BLOCKER below), and the new lot-choice rule added one MAJOR.

### (1) Second-pass BLOCKER and MAJORs
| Item | Status | Evidence |
|---|---|---|
| N1 BLOCKER: reversing a reversal after the returned credit expired at once | RESOLVED in ledger.md for the A→B→A path. Gap: design §3.3 does not mention the rule, and a sibling path is still open (see below). | docs/02-architecture/erd/ledger.md:207. specs/WP-0.7/design.md:113 has no expiry-first sentence. |
| N2 MAJOR: `ledger_entry_id` pointed to the chain root | RESOLVED | ledger.md:206 ("reverses the chain's tip"), :240, :247 |
| N3 MAJOR: paid COMPENSATION/ADJUSTMENT entries had no lot | RESOLVED: the CHECK now covers every paid-pool entry, and the reconciliation compares Σ lot remaining with the paid balance | ledger.md:212, :219 |
| N4 MAJOR: lots described as derived (ADR-007 §5, D-05) but built as a source table | RESOLVED | ADR-007-credit-ledger.md:12; ledger.md:8-14, :135, :161; design.md:25 (D-05), :35 (D-28), :76, :80 |

### (2) Second-pass MINORs marked fixed (notes.md:76-78)
All are present:
- stale `unit_price_minor` text: billing.md:486. A grep finds no `unit_price_minor` or `frozen_days` left in docs/02-architecture or design.md.
- `expires_at` recompute: ledger.md:152, :160
- reconciliation wording: ledger.md:219
- `record_notes` trigger: platform.md:184-185. ADR-007 §2 note: ADR-007:8.
- `review_status` default and CHECK: billing.md:309, :321
- wallet CHECK, composite FK and REVERSALs: billing.md:502-503
- invoice-line deferred check: billing.md:406. Lines carry their own discount and tax, so Σ = total is consistent.
- statement INSERT freeze: teachers.md:372
- allocation currency FK: billing.md:338
- JOD on compensation rules: teachers.md:319
- D-40 is Open: decisions.md:57, :268-271
- skill deferral now lists the key formats: notes.md:78

### (3) New findings
| Severity | file:line | Finding | Required fix |
|---|---|---|---|
| BLOCKER (narrow path, same cause as N1) | ledger.md:208, :212, :247 | A post-lock change from one −1 type to another (for example PRESENT → ABSENT_UNEXCUSED) on a session charged to a lot that has since expired. The flow posts R1 = +1 to the expired lot, and the "expires at once" rule then posts E = `expiry:<lot>:<R1>` −1. The new entry B (−1) has no lot rule (:212 covers only COMPENSATION, ADJUSTMENT and REVERSAL), so it falls back to FIFO and takes an open lot. Net for the session: A + R1 + E + B = −2; it should be −1. If B instead uses the expired lot while E is still keyed per returned entry, that lot's remaining goes to −1. | State that the replacement entry in a post-lock change uses the lot of the entry it reverses (as COMPENSATION does). Also state that the instant expiry is judged on the lot's net remaining after the whole transaction, so nothing expires when the session charge does not change. Add both to design §3.3, with a WP-2.3 test for the expired-lot case of every attendance mapping. |
| MAJOR | ledger.md:208, :212 | "ADJUSTMENT_MANUAL −N takes the FIFO lot" breaks when N is larger than that lot's remaining. One entry can hold only one `lot_id`, and the key `manual:<request uuid>` allows only one entry per request. So the action either overdraws the first lot or cannot be split. An overdrawn lot leaves the next lot's remaining too high, so D-28 conversion overpays the wallet. Also, "+N the latest open lot" is undefined when no lot is open. | Use the key `manual:<request uuid>:<lot_id>` and split −N across lots FIFO, one entry per lot, in one transaction. Or reject an N larger than the lot's remaining. Define +N when every lot has expired (reject, or post to the latest lot and expire it at once). |
| MINOR | billing.md:338, :502 | The new composite FKs point at `payments (id, currency)` and `wallet_entries (id, family_id, currency)`, but neither table declares that UNIQUE. PostgreSQL rejects the FK and the migration fails. ledger.md:158 does declare the matching UNIQUE for lots. | Add `UNIQUE (id, currency)` on payments and `UNIQUE (id, family_id, currency)` on wallet_entries. |
| MINOR | ledger.md:161; ADR-007:12 | The rebuild list takes `effective_at` from the purchase entry's `created_at`. D-05 defines it as payment success, and that time can differ if the webhook arrives late, which would shift expiry on a rebuild. `cycle_no` has no listed source. | Rebuild `effective_at` from the payment's success time, and `cycle_no` from `subscription_events` or the allocation. |

Files reviewed:
- /home/abdul/sura-academy/docs/02-architecture/erd/ledger.md
- /home/abdul/sura-academy/docs/02-architecture/erd/billing.md
- /home/abdul/sura-academy/docs/02-architecture/erd/teachers.md
- /home/abdul/sura-academy/docs/02-architecture/erd/platform.md
- /home/abdul/sura-academy/docs/02-architecture/adr/ADR-007-credit-ledger.md
- /home/abdul/sura-academy/docs/00-governance/decisions.md
- /home/abdul/sura-academy/specs/WP-0.7/design.md
- /home/abdul/sura-academy/specs/WP-0.7/notes.md
