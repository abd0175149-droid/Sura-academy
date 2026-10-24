<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory

Cite prefixes: `P` = docs/01-product/spec/03-parent-portal.md, `S` = docs/01-product/spec/04-student-portal.md, `D` = docs/01-product/spec/14-data-model-and-states.md. These two files name no settings keys, so the Settings column is "none" on every row. Where a setting is implied but unnamed, the row says so.

| ID | Feature | Data impact | Tables | Columns/fields implied | States / enums stated | Settings keys | Cite |
|---|---|---|---|---|---|---|---|
| PAR-01 | Parent dashboard per-child cards, family alerts | Y (query only) | families, students, class_enrollments, classes, sessions, credit_balance_snapshots, invoices, reports, assignments, change_requests, notifications | none new; display of level, class, next session, balance, alert types (payment due, new report, homework, schedule change) | none | none | P:8 |
| PAR-02 | Add child, edit profile within family | Y | students, student_profiles, families | `family_id` (D:14 scope rule); `created_by`/`updated_by` (D:16); `*` child name/profile fields not enumerated (inferred) | none | none | P:10 |
| PAR-03 | Child profile: placement history, attendance, etc. | Y (query) | placements, enrollment_history, class_enrollments, classes, session_attendance, reports, assignments, submissions, feedback, recordings, consents, content_items | `enrollment_history` fields (D:47); `*` first-teacher reference (inferred, see Ambiguities); allowed recordings need per-child consent (P:20) | none beyond lifecycle from_state/to_state | none | P:11 |
| PAR-04 | Calendar weekly/monthly/list, add to device | Y (query) | sessions, classes, class_schedule_rules, calendar_exceptions, class_enrollments | `*` family display time zone (inferred from P:4); sessions fields (D:42) | session states (D:54) | none | P:12 |
| PAR-05 | Report absence with makeup eligibility preview | Y | absence_requests, session_attendance, attendance_exceptions, credit_ledger_entries, sessions | `*` makeup-eligible flag/computation (inferred); deadline and cap are not named | absence request: مُرسل → مقبول آلياً أو بانتظار المراجعة → مقبول/مرفوض (D:60) | none (deadline "المهلة" and cap "السقف" implied, unnamed) | P:13 |
| PAR-06 | Request schedule or class change | Y | change_requests | `*` request type (time vs class), requester, child/class refs, status (all inferred); no automatic class change | none stated | none | P:14 |
| PAR-07 | Subscription and credit balance view | Y (query) | subscriptions, plans, prices, credit_balance_snapshots, credit_ledger_entries | `auto_renew`, `renew_trigger_balance` (D:44); `*` next renewal date (inferred, not in D:44 fields) | subscription 9 states (D:68-82) | none | P:15 |
| PAR-08 | Payments, invoices, receipts, retry, transfer proof | Y | payment_methods, payments, invoices, invoice_lines, files, refunds | `amount_minor`, `currency`, `method`, `provider`, `provider_ref`, `status`, `idempotency_key` (D:45); `*` transfer-proof file link (inferred); receipt/invoice PDF as `files` (inferred); saved methods "via gateway" | payment: معلقة → ناجحة/فاشلة → مستردة جزئياً/كلياً (D:56) | none | P:16 |
| PAR-09 | Self-cancel with freeze option and refund policy shown | Y | subscriptions, subscription_events, refunds, payments | `status`, `cancelled_at`, `paused_until` (D:44); every state change into `subscription_events` (D:44) | cancelled; refund: مطلوب → معتمد/مرفوض → منفذ (D:57); subscription → refunded (D:82) | none (refund policy unnamed) | P:17 |
| PAR-10 | Reports: weekly, monthly, level; acknowledge, PDF | Y | reports, report_items, report_reviews, files | `*` acknowledged-at/by (inferred from "مُطّلع عليه"); `*` report kind: weekly indicator / monthly / level (inferred) | report: مهمة → مسودة → مُرسل → (معاد للتعديل) → منشور → مُطّلع عليه (D:55) | none | P:18 |
| PAR-11 | Support chat with attachments, in-app notification center | Y | support_threads, messages, files, notifications | `*` thread topic/category (inferred from "حسب الموضوع"); attachments as `files` (inferred); notification read state (inferred) | none | none | P:19 |
| PAR-12 | Notification preferences per type/channel, quiet hours | Y | NEW:notification_preferences (inferred), message_templates, notifications, delivery_logs | `*` user_id, notification_type, channel, quiet_hours start/end, family time zone (inferred) | none | none | P:20 |
| PAR-13 | Privacy center: consents, withdraw, export, delete | Y | consents, legal_documents, data_requests | `user_id`, `student_id`, `purpose`, `legal_document_version_id`, `granted_at`, `withdrawn_at`, `ip`, `user_agent` (D:46); per-child recording consent (P:20); `*` data_requests type: copy / delete (inferred); withdrawal is a new row (D:46) | no data_requests states given | none | P:20 |
| PAR-14 | Child mode: PIN to enter, parent password to exit | Y | student_pins, device_authorizations, auth_sessions, users | `*` pin hash, attempt/lockout counters, device id (inferred; spec/14 lists no columns for these tables); exit uses parent password (existing user credential) | none | none | P:21 |
| STU-01 | Child login: PIN, one-time code, or QR | Y | student_pins, device_authorizations, auth_sessions, NEW:student_login_tokens (inferred) | `*` one-time code/QR token, expiry, consumed_at, issuing guardian (inferred) | none | none | S:8 |
| STU-02 | Home: countdown, join button, continue, tasks, stars | Y (query) | sessions, live_rooms, assignments, submissions, NEW:student_progress (see STU-09), class_enrollments | `*` join-window start/end (inferred) | session states (D:54) | none (join window unnamed) | S:9 |
| STU-03 | Device check before class | N (no persistence stated) | none | none stated | none | none | S:10 |
| STU-04 | Join class embedded, student role, limited permissions | Y | live_rooms, join_token_log, participant_events | `*` role = student (inferred); token log per join (inferred) | none | none | S:11 |
| STU-05 | Lessons and content by level | Y (query) | levels, modules, lessons, content_items, content_versions, media_assets, class_enrollments | content types: vocalized text, video, audio, PDF worksheet, link (S:12) | none | none | S:12 |
| STU-06 | Homework: submit text/image/audio/file, view feedback | Y | assignments, submissions, submission_files, feedback, files | `*` submission kind: text / handwriting image / voice recording / file (inferred) | none stated | none | S:13 |
| STU-07 | On-screen Arabic keyboard | N | none | `*` optional diacritics preference only if persisted (not stated) | none | none | S:14 |
| STU-08 | Recordings: own class only, retention, consent, no download | Y | recordings, recording_access_log, consents, class_enrollments | per-child recording consent (P:20); `*` retention expiry (inferred, "ضمن مدة الاحتفاظ"); access logged (table exists D:30) | none | none (retention period unnamed) | S:15 |
| STU-09 | Progress: stars and simple levels | Y | NEW:student_progress (no spec/14 table) | `*` star count, progress level, derived from attendance and homework (inferred) | none | none | S:16 |
| STU-10 | Announcements, academy only | Y | announcements | `*` audience/targeting (inferred); constraint: no direct teacher/student channel (S:17) | none | none | S:17 |
| STU-11 | Usability for young children | N | none | none | none | none | S:18 |

## Gaps

- Notification preferences (PAR-12): "لكل نوع وقناة، مع ساعات هدوء حسب توقيت الأسرة" (P:19). D:34 lists message_templates, notifications and delivery_logs, none of which holds per-user preferences or quiet hours.
- Family time zone (P:4): "الأوقات تُعرض دائماً بمنطقته الزمنية مع اسمها ظاهراً". `families` and `users` have no listed time-zone field. D:11 only covers a class's `ref_timezone`.
- Progress stars and levels (STU-09, STU-02): "نجوم ومستويات بسيطة مرتبطة بالحضور والواجبات" (S:16) and "نجوم التقدم" (S:9). No table in D:20-36 stores a student's stars or progress level. `level_outcomes` and `placements` are curriculum and assessment, not gamification.
- One-time code and QR child login (STU-01): "على جهاز الطفل برمز لمرة واحدة أو QR من بوابة ولي الأمر" (S:8). D:22 has `student_pins` and `device_authorizations`. Neither is described as holding one-time tokens, and spec/14 lists no columns for either.
- Transfer proof (PAR-08): "رفع إثبات تحويل" (P:15). `files` and `payments` exist. D:45 lists no field linking a proof file to a payment, and no `method` values.
- Report weekly indicators (PAR-10): "المؤشرات الأسبوعية والتقرير الشهري وتقارير المستوى" (P:17). `reports` and `report_items` exist, but no field or type distinguishes weekly, monthly and level reports.
- Next renewal date (PAR-07): "موعد التجديد التالي" (P:14). D:44 `subscriptions` has `started_at`, `auto_renew`, `renew_trigger_balance`, `paused_until`, `cancelled_at`, but no next-renewal field.
- Retention periods and limits (STU-08, PAR-05): "ضمن مدة الاحتفاظ" (S:15) and "حسب المهلة والسقف" (P:12). No settings keys or values are named in these two files.
- Child-mode PIN fields (PAR-14): "برمز PIN، والخروج منها بكلمة مرور ولي الأمر" (P:21). No columns listed for `student_pins` or `device_authorizations` in D:38-47.
- Avatar and badges: neither R1 file mentions an avatar. Badges appear only in STU-14 (R3), "شارات ومتجر جوائز" (S:21). No R1 data home is required by the text.

## Personal data and retention

- Child account has no email and no contact data: "ولا يحمل بريداً إلكترونياً ولا بيانات اتصال" (S:4).
- Child profile fields, name and others (PAR-02, P:10): the fields themselves are not enumerated in these files. Birth data is not mentioned.
- Child PIN (P:21, S:8): a credential. Retention not stated.
- Child devices (`device_authorizations`, D:22; P:21, S:8): authorization records. Retention not stated.
- Consent record `ip` and `user_agent` (D:46): personal data. Consents are never edited, and withdrawal adds a row. Retention not stated.
- Per-child recording consent (P:20).
- Homework files (S:13): handwriting images of the child, voice recordings of the child reading, text and files.
- Class recordings (S:15): the child appears in them. Retention is "ضمن مدة الاحتفاظ" with no value stated. Access is limited to the child's own class and requires parent consent. No download.
- Support messages and attachments (P:19): free text and files from the parent.
- Transfer proof upload (P:15): a financial document.
- Saved payment methods (P:15): "المحفوظة عبر البوابة", meaning held at the gateway. No card data is stated as stored locally.
- Privacy requests (P:20): copy of data or account deletion. Deletion is the permitted path to permanent deletion: "الحذف النهائي فقط بطلبات الخصوصية وجدول الاحتفاظ" (D:13). Financial, attendance and report records are never permanently deleted, only archived or corrected by reversal (D:4).
- Forbidden in the child UI: "أي معلومة مالية، بيانات اتصال المعلمة أو الطلاب الآخرين، روابط إلى مواقع خارجية دون تنبيه، إعلانات أو أدوات تتبع تسويقية" (S:23).
- No private teacher-to-child channel: "لا تواصل مباشر مع المعلمة أو الطلاب خارج الحصة" (S:17).

## Append-only, audit, money, time

- Consents are immutable, and withdrawal is a new record (D:46; PAR-13, P:20).
- `enrollment_history` is written on every student lifecycle transition (D:47). PAR-03 shows "التسكين وتاريخه" (P:11).
- Every subscription state change is written to `subscription_events` (D:44). PAR-09 cancel and PAR-07 auto-renew changes touch this (P:14, P:16).
- Credit ledger: no edits or deletes, reversal entries only, unique (student, session, type) (D:43). PAR-05 and PAR-07 read it (P:12, P:14).
- Audit columns `created_by`/`updated_by` on every table, plus an append-only audit log (D:16).
- Money: `amount_minor` plus `currency` (D:12, D:45). Amounts appear in PAR-07, PAR-08 and PAR-09 (P:14, P:15, P:16). Refund policy is shown before cancel confirmation (P:16).
- Idempotency: `payments.idempotency_key` and unique `provider_ref` per gateway (D:45). PAR-08 "إعادة محاولة دفعة فاشلة" (P:15) is a retry path.
- Time: "الأوقات تُعرض دائماً بمنطقته الزمنية مع اسمها ظاهراً" (P:4). Instants are `timestamptz` UTC, and class patterns are local hour plus IANA zone (D:11).
- PAR-12 quiet hours are "حسب توقيت الأسرة" (P:19), a local time in the family zone.
- STU-02 join button is active "في نافذة الدخول فقط" (S:9). This is a time-window rule on sessions.
- PAR-05 absence deadline "المهلة" (P:12) is a time rule.
- Grace period of 5 days (D:76-77, D:86) is a time-based state transition.

## Ambiguities

- PAR-03 "الشعبة واسم المعلمة الأول" (P:11) can mean either "the teacher's first name" or "the first teacher of the class". `sessions.actual_teacher_id` (D:42) allows a different teacher per session.
- PAR-09 vs PAR-15: PAR-09 (R1) says cancellation shows "خيار التجميد" (P:16). PAR-15 (R2) is "التجميد الذاتي ... تجميد الاشتراك لفترة محددة" (P:22). It is unclear whether a freeze is available to the parent in R1, or only displayed. D:44 already has `paused_until` and a `paused` state (D:72-73).
- PAR-14 vs STU-01: P:21 describes entry by PIN on the parent's device. S:8 also describes entry on the child's own device by one-time code or QR. D:22 names only `student_pins` and `device_authorizations`. The token store for one-time codes is not specified.
- S:4 says the child account holds no email. D:46 `consents` has `user_id` and `student_id`, and D:22 lists `student_pins` separately from `users`. The text does not say whether a student has a `users` row.
- PAR-03 "التسجيلات المسموحة" (P:11) vs STU-08 "حصص شعبته فقط، ضمن مدة الاحتفاظ ووفق موافقة ولي الأمر" (S:15). Consent is per child (P:20). The scope rule (own class only) and the consent rule are stated separately, and the retention duration is unstated in both files.
- PAR-12 "لكل نوع وقناة" (P:19): the list of types and channels is not given in these files. D:34 has `delivery_logs` and `notifications` but no preferences entity.
- STU-02 "نجوم التقدم" (S:9) and STU-09 "نجوم ومستويات" (S:16) are R1. STU-14 puts "شارات ومتجر جوائز" in R3, "خارج نطاق الوثيقة الأصلية" (S:21). The boundary between R1 stars and R3 badges is not defined by a data entity.
- PAR-08 "رفع إثبات تحويل" (P:15) implies a bank-transfer method. D:45 lists `provider` and `method` but no enumerated values, and `provider_ref` is described as unique "لكل بوابة".
- PAR-10 "تقارير المستوى" (P:17) vs the report states in D:55: the states are listed but no report types are.

## R2/R3 rows with data impact (one line each)

- PAR-15 — Self-service freeze for a set period — subscriptions (`paused_until`), subscription_events
- PAR-16 — Permanent ICS calendar link — sessions, classes, NEW:calendar_feed_tokens (inferred)
- PAR-17 — Second guardian with owner-set permissions — guardians, users, roles, user_roles, permissions
- PAR-18 — Referral program with credit reward — NEW:referrals (inferred), coupons, wallet_entries, credit_ledger_entries
- PAR-19 — Homework comments, supervisor-visible — messages, assignments, support_threads (inferred)
- STU-12 — Short quizzes with auto-grading — tests, questions, test_attempts, test_answers
- STU-13 — Handwriting canvas submitted as homework — submissions, submission_files, files
- STU-14 (R3) — Badges and rewards shop — NEW:badges (inferred), NEW:student_progress
