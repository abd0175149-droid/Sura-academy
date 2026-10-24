<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory
Files: S6 = `docs/01-product/spec/06-teacher-portal-ops-compensation.md`; S14 = `docs/01-product/spec/14-data-model-and-states.md`; S21 = `docs/01-product/spec/21-appendices.md`.
Note: S14 has no `teacher_leaves`-style teacher tables beyond the list at S14:35 (teachers, teacher_applications, teacher_documents, trainings, training_completions, observations, compensation_rules, compensation_lines, compensation_statements, payouts). The scheduling tables at S14:29 include teacher_availability, teacher_leaves, change_requests. S14 "critical entities" (S14:40-47) lists no teacher or compensation entity, so every column below carries `*`.

| ID | Feature | Data impact | Tables | Columns/fields implied | States / enums | Settings keys | Cite |
| --- | --- | --- | --- | --- | --- | --- | --- |
| TCH-01 | Today dashboard | N (queries only) | sessions, reports, submissions | *shown in Asia/Amman; late reports, ungraded submissions, DST alerts (all derived) | none | none | S6:10 |
| TCH-02 | Schedule day/week view | N (queries only) | sessions, classes | *cancel and postpone markers (from session status) | session: ملغاة, مؤجلة (S14:54) | none | S6:11 |
| TCH-03 | Class page | N (queries only) | classes, students, student_profiles, class_enrollments | *first name only, level, learning_notes (inferred; text says "ملاحظات التعلم"), curriculum progress, attendance summary | none | none | S6:12 |
| TCH-04 | Session workspace | N (defers to virtual classroom section) | sessions, live_rooms, session_attendance, lesson_plans | *session notes (inferred from "الملاحظات") | none | none | S6:13 |
| TCH-05 | Attendance confirmation window | Y | session_attendance, attendance_exceptions | *auto_suggested_status, *confirmed_at, *confirmed_by, *locked (inferred), supervisor edit after lock | auto-suggested, confirmed, locked | `attendance.teacher_confirm_hours`=12 (S21:19) | S6:14 |
| TCH-06 | Curriculum progress per session | Y | sessions, lessons | *lesson_id covered per session (inferred); weekly class summary auto-filled | none | none | S6:15 |
| TCH-07 | Homework and grading | Y | assignments, submissions, submission_files, feedback | *score or rubric criterion, *text note (feedback) | none | none | S6:16 |
| TCH-08 | Tiered reports | Y | reports, report_items, report_reviews, message_templates | *per-student quick indicators, class summary, monthly detailed report, templates | report: مهمة → مسودة → مُرسل → (معاد للتعديل) → منشور → مُطّلع عليه (S14:55) | `reports.review_sample_pct`=20%, `new_teacher_full_review_months`=3 (S21:33, not in this row) | S6:17 |
| TCH-09 | Availability and leave | Y | teacher_availability, teacher_leaves | *weekly slots in Amman time, exceptions, leave request needing admin approval, approval status (inferred) | leave approval (values not stated) | none | S6:18 |
| TCH-10 | Emergency absence report | Y | teacher_leaves (inferred), change_requests (inferred), notifications | *immediate ops alert, substitute search start (inferred) | none | none | S6:19 |
| TCH-11 | Schedule change request | Y | change_requests | *request only, does not change schedule directly | none | none | S6:20 |
| TCH-12 | Earnings statement view | Y | compensation_statements, compensation_lines, support_threads | *eligible classes, executed sessions, formula, adjustments, status; dispute button creates ticket for finance | none (statement status values not stated) | none | S6:21 |
| TOP-01 | Onboarding pipeline (records) | Y (R1 records only) | teachers, teacher_applications, teacher_documents, trainings, training_completions, observations | *application, screening, interview with scoring form, documents (incl. certificate of no criminal record), training, observation session, approval, activation | teacher: متقدمة → مقبولة في الفرز → مُقابَلة → في التدريب → تحت المراقبة → نشطة → موقوفة أو منتهية (S14:58) | none | S6:29 |
| TOP-02 | Teacher profile | Y | teachers, teacher_documents | *languages, qualifications, qualified_levels, max_classes, bank_details (encrypted), document expires_at | none | none | S6:30 |
| TOP-03 | Acknowledgements | Y | consents (inferred), legal_documents | *acceptance of contract, code of conduct, child protection policy, each with its version | none | none (versioning rule at S14:15) | S6:31 |
| TOP-05 | Suspension and termination | Y | teachers, classes, sessions | *block new class assignment; list of the teacher's future sessions for reassignment before suspension | teacher: موقوفة أو منتهية (S14:58) | none | S6:33 |
| PAY-01 | Unit of calculation | Y | compensation_lines, compensation_rules | *per executed session: class pay ÷ 8, to the teacher who actually taught it (sessions.actual_teacher_id, S14:42), including substitute | none | none | S6:39 |
| PAY-02 | Hybrid pay | Y | compensation_rules | *base amount, *per-student amount, enrolled student count; examples 45/50/55 JOD for 2/3/4 students | none | `teacher.pay_base_jod`=35, `teacher.pay_per_student_jod`=5 (S21:30) | S6:40 |
| PAY-03 | Night premium | Y | compensation_rules, compensation_lines | *15% on sessions starting after 22:00 Amman time | none | `teacher.night_premium_pct`=15%, `teacher.night_start`=22:00 Amman (S21:31) | S6:41 |
| PAY-04 | Urgent cover | Y | compensation_rules, compensation_lines | *extra amount to the substitute when assigned less than 24 hours before the session (amount not stated in the row) | none | `teacher.substitute_per_session_jod`=6.25 (S21:32) (mapping not stated in S6) | S6:42 |
| PAY-05 | Adjustments | Y | compensation_lines, audit_logs | *add or deduct line, *written reason, *finance approval (approver, approved_at inferred), audit entry | none | none | S6:43 |
| PAY-06 | Monthly cycle (R1 calculation and export) | Y | compensation_statements, payouts, compensation_lines | *auto-calc on the 1st of each month, finance review and approval, bank transfer file, payment reference recorded; R2 is the full approval workflow | none (statement and payout states not stated) | none | S6:44 |
| PAY-07 | Price exception | Y | compensation_rules | *special rate per teacher or per class; only Finance or System Admin may set | none | none | S6:45 |

## Gaps
- TOP-01 "مقابلة بنموذج تقييم" (S6:29): an interview scoring form has no home. `observations` is the closest table, but its text is "حصة مراقبة". Candidate: `NEW:interview_evaluations` (inferred).
- TOP-01 "طلب، فرز" (S6:29): screening result and outcome are not columns of `teacher_applications` in S14 (S14 gives no fields for it).
- TOP-02 "البيانات البنكية مشفرة" (S6:30): no table is named for encrypted bank details. `teachers` is the only candidate. Whether it is a separate `NEW:teacher_bank_accounts` is not stated.
- TOP-02 "اللغات، المؤهلات، المستويات المؤهلة لها، الحد الأقصى للشعب" (S6:30): `NEW:teacher_qualified_levels` (a link to `levels`) is needed, since S14 has no such table. Languages and qualifications are not placed in any table.
- TOP-03 "قبول العقد ومدونة السلوك وسياسة حماية الطفل بإصداراتها" (S6:31): `consents` (S14:46) has `user_id, student_id, purpose, legal_document_version_id`. Teacher acceptance is not stated to use it, and `legal_documents` versioning is not stated to cover teacher contracts.
- TOP-05 (S6:33): "منع إسناد شعب جديدة" has no stated flag. The state "موقوفة" (S14:58) may cover it.
- PAY-01 (S6:39): "أجر الشعبة" needs a stored class pay value or its derivation. `compensation_rules` has no listed fields in S14.
- PAY-02 (S6:40): the enrolled-student count at calculation time is not stored anywhere that S14 names (`class_enrollments` only).
- PAY-05 (S6:43): there is no adjustments table. Likely `compensation_lines` with a type, but this is not stated.
- PAY-06 (S6:44): `compensation_statements` has no stated period field, and "ملف تحويل بنكي" (export file) is not tied to `files`.
- TCH-12 (S6:21): the dispute "تذكرة للمالية" is tied to `support_threads` only by inference. S14 does not say a finance queue exists.
- PAY-04 (S6:42): "مبلغ إضافي" is not given in the S6 row. It appears only as the setting at S21:32.
- TCH-05 (S6:14): the "locked after 12 hours" state has no named field.

## Personal data and retention
- TCH-03 (S6:12): students shown by first name, level and learning notes only (minimum child data). No retention stated.
- TOP-02 (S6:30): teacher languages, qualifications and encrypted bank details are personal data. Documents carry expiry dates. No retention stated.
- TOP-01 (S6:29): teacher documents include the certificate of no criminal record. No retention stated.
- TOP-01 (S6:29): the interview evaluation form is personal data about applicants. No retention stated.
- TCH-07 and TCH-08 (S6:16-17): teacher text notes and reports about children. No retention stated.
- TCH-12 (S6:21): earnings data is personal financial data for the teacher. No retention stated.
- The S6 file states no retention periods. General rule: S14:13 says permanent deletion is only through privacy requests and the retention schedule.

## Append-only, audit, money, time
- TCH-01 (S6:4, S6:10): the teacher portal is shown in Amman time. DST-change alerts also appear.
- TCH-09 (S6:18): availability is entered in Amman time (a local time, with the zone not otherwise stated).
- PAY-03 (S6:41): 22:00 Amman time is a local-time rule (`Asia/Amman` as the zone, per the CLAUDE.md note; S6 itself says only "بتوقيت عمّان").
- PAY-02, PAY-04 (S6:40, S6:42): money in JOD. Per S14:12, store as integer fils, 3 decimals. Settings S21:30 and S21:32 show decimal values (35, 5, 6.25).
- PAY-05 (S6:43): adjustments carry a written reason and finance approval, and are "مسجلة في التدقيق" (`audit_logs`, append-only per S14:16).
- PAY-06 (S6:44): monthly auto-calculation on the 1st (a time trigger), a bank transfer file, and a recorded payment reference. S14:4 says financial records are never permanently deleted, only archived or corrected by a reversing entry.
- PAY-07 (S6:45): a price exception restricted by permission (Finance or System Admin). The change is presumably audited, but this is not stated.
- TCH-05 (S6:14): a 12-hour confirmation window and a lock after it. Supervisor-only edits after the lock.
- TOP-03 (S6:31): acceptances are versioned (S14:15). Whether they are immutable is not stated.
- General (S14:15): rules are versioned and every record points to the version applied. `rule_versions` appears at S14:31, tied to the ledger.

## Ambiguities
- Settings value type: PAY-02 states "35 و5 دنانير" (S6:40) and S21:32 lists `teacher.substitute_per_session_jod` = 6.25. S14:12 says money is integer fils (JOD has 3 decimals). The settings keys use a `_jod` suffix with decimals, not minor units.
- PAY-04 (S6:42) says "مبلغ إضافي للبديلة عند تكليف أقل من 24 ساعة"; S21:32 gives `teacher.substitute_per_session_jod` = 6.25 "per session". The S6 row does not name this key. The 24-hour threshold has no settings key in S21:30-35.
- PAY-01 (S6:39) says per-session pay = class pay ÷ 8 "في كل الحالات بما فيها البديلة". PAY-04 adds a substitute extra amount. It is unclear whether the 6.25 JOD is that extra amount or a different substitute rate (S21:32 says only "substitute_per_session").
- PAY-01 vs PAY-02: PAY-01 defines class pay ÷ 8. PAY-02 gives 45/50/55 JOD per class for 2/3/4 students. The unit is therefore per-session, but S6 does not say whether "÷ 8" refers to 8 sessions per month. This is not stated in S6.
- PAY-02 (S6:40) says the amount is "لكل طالب مسجل", with no stated moment at which the enrolled count is fixed (enrollment can change in the month).
- PAY-03 (S6:41) says "بعد 22:00" while S21:31 says `night_start` = 22:00. It is unstated whether a start at exactly 22:00 qualifies.
- PAY-06 (S6:44) says "R1 احتساب وتصدير، R2 سير اعتماد كامل", but the same row lists "مراجعة واعتماد المالية" as part of the cycle. It is unclear which part of the approval is in R1.
- TOP-01 (S6:29) is "R1 سجلات، R2 سير عمل كامل", but the teacher state machine (S14:58) already lists the full lifecycle. Which states are R1 is not stated.
- S14:58 teacher states have no explicit link to TOP-05 "الإيقاف والإنهاء" other than "موقوفة أو منتهية".
- TCH-05 (S6:14) "12 ساعة" has the key `attendance.teacher_confirm_hours` = 12 at S21:19, but the start of the window (session end or session start) is not stated.
- S14:4 says "نحو 90 جدولاً"; S6 introduces records (interview evaluation, bank details, qualified levels) with no table in S14 (see Gaps).

## R2/R3 rows with data impact (one line each)
- TCH-13 — voice note on homework — feedback, submission_files (or `files`)
- TCH-14 — training library and completion tracking — trainings, training_completions
- TOP-04 — quality: observation forms, supervisor notes, teacher indicators (punctuality, reports on time, student retention) — observations, teachers
- (PAY-06 and TOP-01 each have an R2 workflow part; the R2 part is not itemized separately in the text.)
