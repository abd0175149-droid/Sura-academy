<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory

Paths are relative to `docs/01-product/spec/`. N = `11-notifications-messaging-support.md`, A = `12-admin-analytics-kpis.md`, D = `14-data-model-and-states.md`, X = `21-appendices.md`. Where the spec states no states or settings keys, the cell says "none stated". The only key in my two files is `notifications.quiet_hours` (X:35). Fields marked `*` are not in D "critical entities". D lists critical fields for only 6 entities (sessions, credit_ledger_entries, subscriptions, payments, consents, enrollment_history). So every field below for the communications and system tables is `*`.

| ID | Feature | Data impact | Tables | Columns/fields implied | States / enums stated | Settings keys | Cite |
|---|---|---|---|---|---|---|---|
| NOT-01 | Notification engine, scheduling, quiet hours | Y | notifications, message_templates, delivery_logs, settings; NEW:notification_preferences | `*event_type` (inferred), `*template_id`, `*recipient_user_id` (inferred), `*recipient_locale` ("قالب بلغة المستلم", N:4, inferred), `*channel`, `*scheduled_at_utc` (inferred), `*recipient_timezone` (inferred from "توقيت المستلم"), `*quiet_hours_start/end` (per recipient, inferred), `*deferred_until_utc` (inferred) | none stated | `notifications.quiet_hours` = 21:00–08:00 (X:35); N:8 says "افتراضياً" | N:8 |
| NOT-02 | Email channel, SPF/DKIM/DMARC | N for storage; email address and bounce suppression are Y | users, notifications, delivery_logs | `*channel`=email; sender-domain auth is ops config, no field | none stated | none | N:9 |
| NOT-03 | In-app inbox with read state | Y | notifications | `*read_at` ("حالة المقروء"; read_at is inferred), `*recipient_user_id` / `*student_id` (inferred) | read / unread (stated only as "حالة المقروء") | none | N:10 |
| NOT-04 | SMS for OTP and urgent alerts | Y | notifications, delivery_logs, users | `*channel`=sms, `*priority` (inferred; X:57 "أولوية عالية"), `*phone_e164` (inferred, on users/guardians) | none stated | none | N:11 |
| NOT-07 | Templates: AR/EN, variables, versions, preview, test send | Y | message_templates; NEW:message_template_versions (see Gaps) | `*key`, `*locale` (ar/en), `*channel` (inferred), `*subject`, `*body`, `*variables` (inferred), `*version`, `*template_version_id` on notifications (inferred from the D:15 versioning rule), `*test_send_log` (inferred) | none stated | none | N:14 |
| NOT-08 | Bulk messages to class/level/country | Y | announcements, notifications, delivery_logs; NEW:announcement_recipients | `*audience_type` (class/level/country), `*audience_ref` (class_id / level_id / country), `*sent_by`, `*sent_at`, `*recipient_list` ("سجل لمن أُرسلت"), `*required_permission` (inferred) | none stated | none | N:15 |
| NOT-09 | Delivery log, retry, bounce suppression | Y | delivery_logs; NEW:suppressed_addresses | `*notification_id`, `*channel`, `*status`, `*provider_ref` (inferred), `*attempt_count` (inferred), `*last_attempt_at` (inferred), `*bounced_address` / suppression flag | sent، delivered، bounced، failed (مرسل، مُسلَّم، مرتد، فاشل) | none (no retry-count key in X) | N:16 |
| MSG-01 | Support tickets | Y | support_threads, messages, files | `*category`, `*assignee_user_id`, `*internal_note` flag on messages ("ملاحظات داخلية"), `*attachments` → files, `*sla_due_at` ("مؤقت مستوى الخدمة"), `*first_response_at` (inferred), `*csat_score` ("تقييم الرضا"), `*family_id` (inferred, scope rule D:14) | none stated (no ticket states) | none | N:17 |
| MSG-02 | Support SLA: 12h first reply, 1h for pre-class login issues | Y | support_threads | `*sla_due_at`, `*priority` or `*is_pre_class_login_issue` (inferred), `*related_session_id` (inferred, to detect "قبل الحصة") | none stated | none: 12h and 1h are NOT in X settings | N:18 |
| MSG-03 | Student announcements, age-appropriate | Y | announcements | `*sender` (academy only), `*audience`, `*age_band` or `*language_level` (inferred from "بلغة مناسبة للعمر"), `*title`, `*body`, `*published_at` | none stated | none | N:19 |
| ADM-01 | Live ops dashboard | N (reads) | sessions, participant_events, join_token_log, payments, attendance_exceptions, trials, subscriptions | uses `sessions.status`, `actual_teacher_id`, `subscriptions.status`, `renew_trigger_balance`. Detecting "تأخرت المعلمة / لم يدخل أحد" needs participant join timestamps (inferred) | session-status labels "بدأت، تأخرت، لم يدخل أحد" are not in the D session states | none | A:8 |
| ADM-02 | Family CRM: filters, timeline, notes, tags, tasks, lead stages | Y | families, family_notes, leads, lead_events, follow_up_tasks | `*tags` (NEW:tags / taggables, see Gaps), `*note body`, `*author`, `*lead_stage`, `*task due/assignee/status` (inferred), timeline = union of events (derived, inferred) | lead stages named but not enumerated | none | A:9 |
| ADM-03 | Students admin, suspend without delete | Y | students, student_profiles, placements, class_enrollments, enrollment_history | `archived_at` (D:13), `*suspended` state (inferred) | none enumerated | none | A:10 |
| ADM-04 | Teachers admin | Y | teachers, teacher_availability, teacher_documents, compensation_statements, classes | `*quality metrics` (not defined) | teacher states per D:58 | none | A:11 |
| ADM-05 | Classes and scheduling admin | Y | classes, class_schedule_rules, sessions, waitlist_entries, calendar_exceptions | no new fields; conflict detection is a query | class states per D:53 | none | A:12 |
| ADM-06 | Assessments and trials admin, conversion | Y | assessment_slots, assessments, assessment_scores, placements, trials | `*conversion` is derived (KPI A:28) | trial states per D:59 | none | A:13 |
| ADM-07 | Curriculum and content admin | Y | content_items, content_versions, media_assets, lesson_plans | none new | none | none | A:14 |
| ADM-08 | Finance admin, approve transfers, revenue export | Y | payments, invoices, refunds, wallet_entries, settlements, audit_logs | `*approved_by` / `*approved_at` for bank transfers (inferred), `*export log` (A:4 "مسجل") | payment states D:56, refund states D:57 | none | A:15 |
| ADM-09 | Credit ledger viewer, exceptions, manual entries with reason | Y | credit_ledger_entries, attendance_exceptions, audit_logs | `reason` (D:43); manual entry = ledger row | none new | none | A:16 |
| ADM-10 | Report review: sample, return, approve | Y | reports, report_reviews | `*is_sampled` (inferred), `*reviewer`, `*decision` | report states D:55 | `reports.review_sample_pct` (X:33, not named in A) | A:17 |
| ADM-11 | Communications admin | Y | message_templates, announcements, delivery_logs | see NOT-07/08/09 | none | none | A:18 |
| ADM-12 | Settings, versioned, who/when | Y | settings; NEW:setting_versions (see Gaps) | `*key`, `*value`, `*version`, `*changed_by`, `*changed_at`, `*effective_from` (inferred) | none | all X keys (X:7–39) | A:19 |
| ADM-13 | Audit log search, read-only | Y | audit_logs | `*actor_user_id`, `*entity_type`, `*entity_id`, `*action`, `*occurred_at`, indexed for search by user/entity/action/date | append-only (D:16) | none | A:20 |
| ADM-14 | Safeguarding register, restricted | Y | safeguarding_incidents | `*reporter`, `*child/student_id` (inferred), `*description` (free text), `*severity`/`*status` (not stated), `*access role` (safeguarding officer + system admin only) | none stated | none | A:21 |
| ADM-15 | Feature flags per group | Y | feature_flags | `*key`, `*enabled`, `*target_group` (user/role/family/cohort not specified), `*rollout` (inferred) | none | none | A:22 |

KPI table (A:25–37) has no IDs. These are the stored or derived data each one needs:

- **Active students** (A:27): needs subscription status and credit balance with expiry at month end. Month-end snapshot is `credit_balance_snapshots` (inferred).
- **Trial conversion** (A:28): trial attended date and subscription start, 14-day window (trials, subscriptions).
- **Monthly churn** (A:29): cancelled_at and credit expiry.
- **Average occupancy** (A:30): enrollments ÷ active classes.
- **Attendance rate** (A:31): attendance statuses present / late / partial / all. Statuses are defined elsewhere (`attendance.*` keys in X:15–16).
- **Teacher cancellations** (A:32): session cancellation reason "teacher absence without substitute" (sessions.outcome, inferred).
- **Reports on time** (A:33): report due date and published_at.
- **Login success** (A:34): login attempts and outcomes. See Gaps.
- **MRR** (A:35): plan price per active subscription.
- **CAC** (A:36): marketing spend (NEW, see Gaps) plus lead channel (`*channel`) and new paid-student count.
- **Failed payments recovered** (A:37): payments failed → later succeeded (provider_ref / retry link).

## Gaps

Data needed by R1 text that has no home in D's table list (D:20–36):

1. **User notification preferences** (channel choice, language, timezone, quiet hours override). N:4: "القناة التي اختارها ... توقيت يحترم منطقته الزمنية وساعات الهدوء". There is no `notification_preferences` table, and `users` has no listed fields. N:8: "ساعات هدوء 21:00–08:00 افتراضياً" implies per-user override. Proposed: `NEW:notification_preferences`.
2. **Template versions**. D:15: "الإعدادات والقواعد والوثائق القانونية والقوالب بإصدارات". N:14: "إصدارات". Only `message_templates` exists, with no `message_template_versions`. Compare `content_versions` for content.
3. **Setting versions and change history**. A:19: "بإصدارات ومن غيّرها ومتى". Only `settings` exists. There is no `setting_versions`, and `rule_versions` is for ledger rules.
4. **Bounced-address suppression list**. N:16: "إيقاف العناوين المرتدة". Proposed: `NEW:suppressed_addresses`, or a flag on users/guardians.
5. **Bulk-message recipient record**. N:15: "سجل لمن أُرسلت". It could be derived from `notifications` rows, but this is not stated. `announcements` is also the table for MSG-03.
6. **Tags (وسوم)**. A:9: "وسوم". No tags or taggables table.
7. **Support ticket structure**. N:17: "تصنيف، إسناد، ملاحظات داخلية، مرفقات، مؤقت مستوى الخدمة، تقييم الرضا". There are only `support_threads` and `messages`. CSAT, category and SLA clock have no listed columns. Attachments could use `files`.
8. **Login attempt records** for the "نجاح الدخول" KPI (A:34: "دخول ناجح من أول محاولة ÷ محاولات الدخول"). `auth_sessions` and `join_token_log` may not capture failed attempts.
9. **Marketing spend** for CAC (A:36: "إنفاق التسويق"). There is no table. A lead acquisition channel/source (A:23, R2 "السوق والقناة") also has no field on `leads`.
10. **Analytics event store and pseudonymous IDs** (A:39: "تستخدم معرّفات مستعارة. قائمة الأحداث المتتبعة في الملحق"). There is no table, and no pseudonym-mapping field on `students`/`users`.
11. **Data-export log**. A:4: "كل تصدير للبيانات محكوم بالدور ومسجل". It may fit `audit_logs`, but this is not stated.
12. **Feature-flag targeting** (A:22: "لمجموعة محددة"). `feature_flags` has no group-membership structure listed.
13. **Support SLA values**. N:18 gives 12h and 1h, but X:7–39 has no settings key for them. Rule "Business numbers come from versioned settings" is in CLAUDE.md.
14. **Teacher-late / nobody-joined detection** (A:8). It needs join timestamps: `participant_events` or `join_token_log` (inferred). Confirm they carry them.
15. **Quality score for teachers** (A:11: "الجودة"). No defined field.

## Personal data and retention

No retention period is stated in N or A for any field below. The only periods in X are `recordings.family_days` / `admin_days` = 30 / 90 (X:34) and `billing.wallet_freeze_months` (X:27), neither tied to these rows.

- **Guardian email and phone**: used for delivery (N:9, N:11); delivery_logs hold addresses and statuses (N:16). Retention is not stated.
- **Notification content and delivery logs**: they may contain child names. Retention is not stated.
- **Support ticket messages, internal notes, attachments** (N:17): free text and files may include child data. Retention is not stated.
- **Family CRM notes, tags, follow-up tasks** (A:9): free-text notes. Retention is not stated.
- **Safeguarding incident reports** (A:21): free-text child-protection data, access restricted to the safeguarding officer and system admin. Retention is not stated.
- **Audit log** (A:20): holds the user and entity of every action. Retention is not stated. D:4 says financial, attendance and report records are never permanently deleted. Permanent deletion happens only via privacy requests and the retention schedule (D:13).
- **Student announcements** (N:19): age band. No child PII is stated.
- **Analytics** (A:39): "التحليلات لا ترسل أسماء الأطفال أو بياناتهم لأي أداة خارجية، وتستخدم معرّفات مستعارة". Child names and data must not go to external tools.
- **N:4**: "لا تُعرض بيانات اتصال أي طرف للآخر". Contact details must not be shown between parties.
- **Consent IP and user agent** (D:46, not in my files): `ip`, `user_agent`.

## Append-only, audit, money, time

- **ADM-13** (A:20): audit log is read-only (D:16: "سجل تدقيق منفصل للإضافة فقط"). Searchable by user, entity, action and date.
- **ADM-12** (A:19): settings changes keep history, who and when.
- **ADM-09** (A:16): manual ledger entries require a reason. Ledger is append-only (D:43).
- **A:4**: data exports are role-gated and logged.
- **NOT-09** (N:16): delivery-log statuses change over time. Whether the log is append-only or a mutable status is not stated.
- **NOT-08** (N:15): the recipient list of a bulk send is kept.
- **MSG-01 / MSG-02**: SLA timer is a time instant (UTC per D:11). The deadline logic ("قبل الحصة") depends on session time.
- **NOT-01** (N:8): scheduling in the recipient's timezone. Quiet hours 21:00–08:00 "بتوقيت المستلم" (X:35) are local times, so a recipient IANA zone is needed. That resembles D:11's pattern for class schedules, but D does not state it for notifications.
- **X:56**: DST-change notice 14 days ahead. `scheduling.dst_alert_days` = 21 is also in X:37, so the two values differ (see Ambiguities).
- **Money in my files**: ADM-08 (A:15) revenue and transfers. KPIs: MRR (A:35), CAC ≤ 90$ (A:36). Money must be minor units plus currency (D:12). The CAC currency "$" is not tied to a key (`billing.currency` = USD, X:28).
- **Idempotency**: none stated in N or A. Notification dedupe is not stated.
- **Soft-delete**: ADM-03 "الإيقاف دون حذف" → `archived_at` (D:13).

## Ambiguities

1. **SMS scope**. N:9 (NOT-02): "قناة أساسية لكل الإشعارات". N:11 (NOT-04): "لرموز التحقق والتنبيهات العاجلة فقط". X:45 uses SMS for the verification code. X:57 uses "رسالة نصية وداخل التطبيق" to operations for urgent teacher absence. "عاجلة" is not defined, and the recipient of SMS (staff versus guardian) is not stated in N.
2. **Quiet hours versus "فوري" notifications**. N:8: quiet hours 21:00–08:00. X:45–58 list many triggers as "فوري". Whether urgent or immediate items bypass quiet hours is not stated. X:57 says "فوري، أولوية عالية".
3. **DST notice 14 days versus 21 days**. X:56: "قبل 14 يوماً". X:37: `scheduling.dst_alert_days` = 21 يوماً.
4. **Template language**. N:4: "قالب بلغة المستلم". N:14: "عربي وإنجليزي". Where the recipient's language is stored is not stated. Student reminders in-app (X:47) and age-appropriate language (N:19) have no language field.
5. **Recipient of student reminders**. X:47: "ولي الأمر، والطالب داخل التطبيق". Student/child inbox rows (NOT-03) conflict with no-child-PII channels: notifications scoped by `student_id` versus `family_id` is not stated.
6. **MSG-03 "من الأكاديمية فقط"** (N:19) versus NOT-08 bulk messages "بصلاحية محددة" (N:15). Whether announcements are a separate stream from bulk messages is not stated. Both use `announcements` in D:34 as the only home.
7. **MSG-02 SLA** (N:18: 12 hours; 1 hour for login problems before class). It is not in X settings, so CLAUDE.md "Business numbers come from versioned settings" conflicts with there being no key. Also "قبل الحصة" has no stated window.
8. **ADM-02 lead stages** (A:9: "مراحل العميل المحتمل"). Stages are not enumerated anywhere in D, N or A. D has `leads` and `lead_events` only.
9. **ADM-14 access** (A:21: "مسؤول الحماية ومدير النظام فقط") versus D:14, which requires a family/class/teacher scope column on every table. The safeguarding role is not in my files, and the incident's scope key is not stated.
10. **ADM-01 "تأخرت المعلمة / لم يدخل أحد"** (A:8) are not in D:54 session states (مجدولة → مباشرة → منعقدة، أو ملغاة، أو مؤجلة). They are either derived or extra states.
11. **KPI "الطلاب النشطون"** (A:27: "اشتراك نشط أو رصيد غير منتهٍ في نهاية الشهر"). It needs a month-end snapshot. `credit_balance_snapshots` exists (D:31), but its granularity is not stated.
12. **D:4 says "نحو 90 جدولاً في 15 مجالاً"**. The D:20–36 table has 15 domain rows. Counting tables was not requested.
13. **KPI "الطلاب النشطون" (A:27)** says "اشتراك نشط" while the D:64–82 diagram shows 9 states, including `grace`. Whether grace counts as active is not stated.
14. **CAC "≤ 90$"** (A:36) uses a hard-coded currency symbol and value. X:7–39 has no key for CAC target. The 11 KPI targets in A:27–37 are not in X settings.

## R2/R3 rows with data impact (one line each)

- NOT-05 — WhatsApp Business, approved templates, family channel consent — notifications, delivery_logs, message_templates, consents
- NOT-06 — Browser push notifications for installed app — notifications, delivery_logs; NEW:push_subscriptions (inferred)
- MSG-04 — Guardian-teacher in-platform conversations visible to supervisor — support_threads, messages (inferred)
- MSG-05 — Help center articles — NEW:help_articles (no spec/14 table; inferred)
- ADM-16 — Cohort retention by subscription month, market, channel — subscriptions, leads (channel field missing)
