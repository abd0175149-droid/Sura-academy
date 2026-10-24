<!-- privacy-reviewer-3 report, WP-0.7 third pass (2026-10-24) -->

## WP-0.7 privacy review, third pass

**Result: 0 BLOCKER, 0 new MAJOR, 2 small MINORs.** I edited nothing.

- `php scripts/check-erd.php` passes: 130 tables, 210/210 R1 IDs, 82 PII columns.
- `php scripts/test-erd.php` passes 58/58.

### (1) Second-pass MAJORs
| # | Item | Status | Evidence |
| --- | --- | --- | --- |
| 1 | `safeguarding_incident_actions` could never be purged after 7 years | RESOLVED | The table moved to `forbid_mutation_except_retention`, purged 7 years after the incident closes (data-model-conventions.md:54-55). design.md:111 matches. The retention row is at pii-registry.md:71 and :130. |
| 2 | `record_notes` could not be found by the person it is about; NOT NULL `body` had action `erase` | RESOLVED | `subject_student_id`, `subject_user_id` and `subject_family_id` foreign keys plus indexes (platform.md:174-176, 183). Locate by uses those columns and the action is `delete_row` (pii-registry.md:40). Retention row at pii-registry.md:132. A trigger rejects UPDATE and DELETE except for `sura_retention` (platform.md:183). The checker rule against polymorphic paths was not added. The reason given in notes.md is acceptable now that the subject columns exist. |

### (2) Second-pass MINORs marked fixed (notes.md:81)
- `record_notes` and `legal_holds` triggers: RESOLVED. `record_notes` at platform.md:183. For `legal_holds`, DELETE is rejected, `released_at` is write-once, and placing or releasing a hold is logged as an incident action.
- Hold logic pending D-38: RESOLVED. pii-registry.md:23 and 189-190 set `'honour_legal_holds' => null`. The `legal_holds` section in platform.md says "Pending D-38".
- Wording of the free-text rule: RESOLVED in pii-registry.md:19 and data-model-conventions.md:105. **Partly open in design.md:111** (MINOR A below).
- Retention trigger SQL with SECURITY INVOKER: RESOLVED (data-model-conventions.md:57-69).
- Recipient regions and AWS hosting: RESOLVED (pii-registry.md:151-159). SES, Twilio and Sentry are marked for legal review in WP-0.8.
- Neutral `restricted` reason code: RESOLVED (teachers.md:136).
- `title` / `exception` / `error` columns: RESOLVED. Tagged at families.md:377 and platform.md:277, 310, 347; registered at pii-registry.md:45-47 and :66. The name list was extended (data-model-conventions.md:105).
- E8 accepting a bare "no-PII": RESOLVED (check-erd.php:305).
- TCH-03 learning notes column: RESOLVED. r1-coverage.md:209 names `class_sessions.teacher_notes` and `report_items.body`, and keeps the guardian's ONB-04 note out of teacher view.
- VPC line in design §3.3: RESOLVED (design.md:106).

### (3) New issues from the fixes
No new BLOCKER or MAJOR. Two MINORs:

Severity | file:line | finding | required fix
--- | --- | --- | ---
MINOR | docs/02-architecture/erd/platform.md:174-176, 183 | All three `record_notes.subject_*` columns can be NULL together. A note saved with no subject cannot be found by an erasure request, which brings back the second-pass MAJOR 2 gap for that row. | Add `CHECK (num_nonnulls(subject_student_id, subject_user_id, subject_family_id) >= 1)`, and require `subject_student_id` when the noted record has a `student_id`.
MINOR | specs/WP-0.7/design.md:111 | The sentence "Append-only tables carry `reason_code`; free text lives in the erasable `record_notes`" still has no exception. It contradicts data-model-conventions.md:105, which exempts `session_chat_messages.body` and `safeguarding_incident_actions.action_taken`. | Add the same exception clause.

### Still passing
- PRV-04: no new child field. New columns are ids only.
- No tracker or third-party call in a child surface.
- No private adult-child channel.

### Files
- /home/abdul/sura-academy/docs/02-architecture/data-model-conventions.md
- /home/abdul/sura-academy/docs/02-architecture/erd/platform.md
- /home/abdul/sura-academy/docs/02-architecture/erd/pii-registry.md
- /home/abdul/sura-academy/docs/02-architecture/erd/families.md
- /home/abdul/sura-academy/docs/02-architecture/erd/teachers.md
- /home/abdul/sura-academy/docs/02-architecture/erd/r1-coverage.md
- /home/abdul/sura-academy/specs/WP-0.7/design.md
- /home/abdul/sura-academy/scripts/check-erd.php
