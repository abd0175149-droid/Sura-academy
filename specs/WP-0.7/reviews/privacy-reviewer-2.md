<!-- privacy-reviewer-2 report, WP-0.7 second pass (2026-10-24) -->

## WP-0.7 privacy review, second pass

**Result: 0 BLOCKER, 2 new MAJOR, 10 new MINOR.** All 13 first-pass MAJORs are fixed in the documents. My first report said 12 MAJORs in its header, but its table and notes.md both list 13.

I checked the fix commit 75434bd against main and HEAD~1, every changed ERD, `data-model-conventions.md`, `design.md` §3.3–3.4, the notes.md table, D-38 and D-39, the child-privacy skill, and the spec text for CLS-07, CLS-15, SAFE-02, TCH-03, ONB-04 and ONB-12. I edited nothing.

- `php scripts/check-erd.php` passes: 130 tables, 210/210 R1 IDs, 79 PII columns.
- `php scripts/test-erd.php` passes 57/57.
- None of the fixes adds a child field outside PRV-04/ONB-03, a tracker, or a private adult-child channel.

### First-pass MAJORs

| # | Finding | Status | Evidence |
| --- | --- | --- | --- |
| 1 | Retention could not delete from append-only tables | RESOLVED, but see new MAJOR 1 | data-model-conventions.md:52-55, design.md:110 |
| 2 | Free-text reasons in append-only tables | RESOLVED: all five now use `reason_code`, and the text moves to `record_notes`. See new MAJOR 2 | ledger.md:190, platform.md:134, scheduling.md:257, billing.md:352, billing.md:495 |
| 3 | Untagged free text in normal tables | RESOLVED: all tagged and registered; new checker rule E8 | ledger.md:114,121; scheduling.md:314,381,403,407; learning.md:200; billing.md:277; identity.md:156; check-erd.php:40,304. Some gaps remain (MINOR 7, 8) |
| 4 | `teachers.status_reason` could leak safeguarding detail | RESOLVED | teachers.md:136, families.md:402. MINOR 6 on one code value |
| 5 | Registry could not find a person's rows; two config shapes | RESOLVED except `record_notes` | Locate by column at pii-registry.md:15,28-108; single shape at pii-registry.md:157-186; design §3.4 aligned |
| 6 | No erasure rule for recordings; no proof of consent check | RESOLVED | pii-registry.md:22, live.md:7, `consent_snapshot` (ids only) at live.md:124 |
| 7 | Retention could destroy safeguarding evidence | RESOLVED as far as the docs can go | D-38 Open (decisions.md:55, 251); `legal_holds` at platform.md:176-192; name snapshot at families.md:401. MINOR 2 |
| 8 | A child's IP could land in the audit log | RESOLVED | platform.md:142, pii-registry.md:20 |
| 9 | Core child data had no retention rule | RESOLVED as a proposal | pii-registry.md:47-53, 119, 131 (`departed_at` trigger), 134 (legal list) |
| 10 | Export file had no retention or access rule | RESOLVED | families.md:322, pii-registry.md:129, 140 |
| 11 | Class chat was editable | RESOLVED: now append-only, deleted by `sura_retention` only | live.md:161, 174; data-model-conventions.md:55 |
| 12 | VPC gate not stated | RESOLVED in the ERD | families.md:185. Not added to design.md §3.3 (MINOR 10) |
| 13 | Fallback link could go to a third-party site | RESOLVED: allow-listed to our own video project, in-app route in child mode | live.md:65 |

### New findings

Severity | file:line | finding | required fix
--- | --- | --- | ---
MAJOR | data-model-conventions.md:54; families.md:417, 429; pii-registry.md:127 | The fix puts `safeguarding_incident_actions` under the strict `forbid_mutation` trigger, which blocks deletes for every role. But D-15 and the retention table delete it after 7 years, so the 7-year deletion can never run. This is the same problem as first-pass MAJOR 1, now for this table. | Move it to the `forbid_mutation_except_retention` list (purge only past 7 years from `closed_at`, and only if no hold is open), and update design.md:110.
MAJOR | pii-registry.md:40; platform.md:160-174 | `record_notes` now holds the free text that may name a child (ledger, wallet, enrollment and audit reasons). Its Locate by is `record_notes.notable_id`, a polymorphic id that never reaches a student, family or user id. The checker accepts it only because the column exists. So an erasure request cannot find notes about a child, and they stay for the noted record's period (Jordanian financial-law period for ledger rows). Also, `body` is NOT NULL but its action is `erase` (set to NULL), which would fail. It is the only such column in the registry. | Add `subject_type`/`subject_id` (student, user or family) to `record_notes`, filled when the note is written, and locate by that. Or list one path per `notable_type` (e.g. `credit_ledger_entries.student_id`, `subscription_events → subscriptions.family_id`, `enrollment_history → class_enrollments.student_id`, `audit_logs.entity_id/actor_id`). Change the action to `delete_row` or `pseudonymize`. Add a `record_notes` row to the retention table. Make the checker reject a polymorphic `_id` as the only Locate by path.
MINOR | platform.md:161, 177 | `record_notes` and `legal_holds` are Kind mutable. "Never updated by the app" is not enforced, so the written reason behind a reversal (RBAC-04) can be rewritten. A hold can be deleted with no trace, which removes evidence protection. | `record_notes`: trigger rejects UPDATE; only `sura_retention` deletes. `legal_holds`: no DELETE, `released_at` write-once, and placing or releasing a hold logged in `safeguarding_incident_actions`.
MINOR | pii-registry.md:23, 184-185; platform.md:192 | `'honour_legal_holds' => true` and "both jobs skip held records" already apply D-38 option A, while D-38 is Open and decisions.md says the table has no logic until it is decided. Holding data against an erasure request is exactly the legal question in D-38. | Mark both as "pending D-38".
MINOR | pii-registry.md:19; design.md:110; data-model-conventions.md:91 | The rule "append-only tables carry no free text" is broken by `session_chat_messages.body` and `safeguarding_incident_actions.action_taken`. | Reword: "except tables whose whole row is deleted by `sura_retention` or kept under D-15".
MINOR | data-model-conventions.md:55-69 | `forbid_mutation_except_retention` is only described in prose; the SQL example covers only the strict function. If the per-table purge functions are SECURITY DEFINER, `current_user` becomes the function owner and the trigger rejects them. | Add the SQL. State SECURITY INVOKER with EXECUTE granted only to `sura_retention`, or check `session_user`.
MINOR | pii-registry.md:147-155 | The recipients table gives no region ("—") for SES, which receives guardian email and the child's first name, or for Twilio and Sentry. AWS as host (database, compute and Redis in eu-central-1, per architecture.md §6) is not listed; only S3 is. | Fill in the regions, or mark them for legal review in WP-0.8. Add AWS hosting as a recipient (PRV-08).
MINOR | teachers.md:136 | The value `status_reason_code = safeguarding` tells operations and admin that a safeguarding concern exists about a teacher. That is outside the two roles allowed by SAFE-02. | Use a neutral value (e.g. `restricted`) and keep the category only in the incident.
MINOR | families.md:377; platform.md:300, 267 | Free text the E8 name rule misses is still untagged: `follow_up_tasks.title` (CRM text that will name children), `webhook_calls.exception` and `outbox_events.last_error`. These carry the same risk as `failed_jobs.exception`, which was tagged. | Tag them PII and register them (12-month technical logs), or justify with `no-PII:`. Extend the name rule to `title`, `exception`, `error` and `message`.
MINOR | check-erd.php:305 | `\bPII\b` also matches a bare "no-PII", so a column can pass E8 with no stated reason. | Use `(?<!no-)\bPII\b|no-PII:\s*\S`.
MINOR | r1-coverage.md:209 | TCH-03 lets teachers see "ملاحظات التعلم" (learning notes), but the mapping still names no column for it. It also says teachers never see "guardian notes". If learning notes means `student_profiles.notes` (ONB-04), that contradicts TCH-03. | Name the column, or append an Open D-xx (stop-on-conflict rule).
MINOR | specs/WP-0.7/design.md §3.3 (around line 107) | The VPC invariant is in families.md:185 but not in design §3.3, where the first pass asked for it. | Add one line.

### Still passing
- PRV-04: no child photo, email, phone, address or school column.
- New child-related columns are ids only, or are the restricted 7-year incident snapshot.
- No private adult-child channel. The STU-10 teacher bulk-message exclusion is noted (messaging.md:140).
- All first-pass MINORs marked fixed in notes.md check out where I looked: Argon2id plus pepper for the PIN, region values and `copied_at`, a reason required for `admin` recording access, append-only cookie consents and audit side table, immutable incident description, and converted-lead erasure.

### Files
- /home/abdul/sura-academy/docs/02-architecture/data-model-conventions.md
- /home/abdul/sura-academy/docs/02-architecture/erd/pii-registry.md
- /home/abdul/sura-academy/docs/02-architecture/erd/platform.md
- /home/abdul/sura-academy/docs/02-architecture/erd/families.md
- /home/abdul/sura-academy/docs/02-architecture/erd/live.md
- /home/abdul/sura-academy/docs/02-architecture/erd/teachers.md
- /home/abdul/sura-academy/docs/02-architecture/erd/r1-coverage.md
- /home/abdul/sura-academy/specs/WP-0.7/design.md
- /home/abdul/sura-academy/scripts/check-erd.php
