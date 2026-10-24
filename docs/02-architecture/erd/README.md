# نموذج البيانات (ERD) — خط الأساس للإصدار الأول

خط أساس نموذج البيانات للإصدار الأول (WP-0.7). يكمل `spec/14-data-model-and-states.md` و`data-model-conventions.md` و`state-machines.md` ولا يحل محلها. عند التعارض المرجع هو ترتيب المصادر في `CLAUDE.md`. الهجرات تبدأ في WP-1.1 وتلتزم بهذه الملفات، وأي تغيير لاحق يعدّل الملف هنا في الحزمة نفسها.

`php scripts/check-erd.php` يفحص هذه الملفات (ويعمل ضمن `scripts/verify.sh`)، و`php scripts/check-erd.php --write` يعيد توليد المخططات.

## الملفات

| الملف | الوحدة | المحتوى |
| --- | --- | --- |
| [platform.md](platform.md) | Platform | الإعدادات وإصداراتها، إصدارات القواعد، التدقيق، الملفات، الوسوم، الصادر، أعلام الميزات، الـ webhooks، جداول Laravel |
| [identity.md](identity.md) | Identity | الحسابات، الجلسات والأجهزة، التحقق الثنائي، رمز الطفل، الأدوار والصلاحيات |
| [families.md](families.md) | Families | الأسر، أولياء الأمور، الطلاب، الوثائق القانونية والموافقات، طلبات البيانات، العملاء المحتملون، بلاغات الحماية |
| [curriculum.md](curriculum.md) | Curriculum | المنهج والمحتوى والنتاجات، اختبار المستوى، المقابلات، التسكين، التجارب |
| [teachers.md](teachers.md) | Teachers | المعلمات، التأهيل، الوثائق، التدريب، الملاحظة، المستحقات |
| [scheduling.md](scheduling.md) | Scheduling | الشعب، قواعد الجدول، التسجيل، الجلسات، العطل، التوفر، الإجازات، قائمة الانتظار، حجز المقعد |
| [live.md](live.md) | Live | الغرف، رموز الدخول، أحداث المشاركين، التسجيلات، دردشة الحصة |
| [ledger.md](ledger.md) | Ledger | الحضور، طلبات الغياب، دفتر الرصيد، دفعات الرصيد، الاستثناءات |
| [billing.md](billing.md) | Billing | الخطط والأسعار، الخصومات، الاشتراكات، الدفعات، الفواتير، الاسترداد، المحفظة |
| [learning.md](learning.md) | Learning | الواجبات والتسليمات والتصحيح، التقارير ومراجعتها |
| [messaging.md](messaging.md) | Messaging | القوالب، الإشعارات، سجل التسليم، التفضيلات، الدعم، الإعلانات |
| [pii-registry.md](pii-registry.md) | — | سجل البيانات الشخصية: تصميم `config/privacy.php` (D-14) |
| [r1-coverage.md](r1-coverage.md) | — | كل متطلب R1 ← الأعمدة التي تخدمه |

## صيغة ملف الوحدة

يُكتب ملف الوحدة بهذا الترتيب:
- السطر الأول `# ERD — <Module>`.
- فقرة تعريف بالوحدة.
- المخطط بين `<!-- erd:diagram:start -->` و`<!-- erd:diagram:end -->`. يُولَّد آلياً من جداول الأعمدة ولا يُحرَّر يدوياً. يعرض المفاتيح الأساسية والأجنبية والعلاقات، ولا يرسم `created_by` و`updated_by`.
- قسم لكل جدول:

```markdown
### `table_name`
- Kind: mutable · Spec: <اسمه في spec/14 أو NEW (السبب)> · Reqs: <المعرّفات> · Scope: <عمود النطاق أو none (السبب)>

| Column | Type | Null | References | Notes |
| --- | --- | --- | --- | --- |
| id | uuid | no | | PK (v7) |

Constraints and indexes:
- `UNIQUE (...)`
```

- **Kind:**
  - `mutable`: جدول عادي.
  - `append-only`: للإضافة فقط، ويطابق قائمة `data-model-conventions.md`.
  - `derived`: يُعاد بناؤه من جداول أخرى.
  - `package`: مخططه تملكه حزمة Composer، مع مفاتيح مورفية uuid.
  - `framework`: بنية Laravel التحتية.

  جداول `package` و`framework` معفاة من قواعد الأسماء والأنواع، لا من المفتاح الأساسي والمراجع.
- **Type:** `uuid`، `string`، `text`، `smallint`، `integer`، `bigint`، `boolean`، `timestamptz`، `date`، `time`، `jsonb`، `inet`، `char(n)`. لا `float` ولا `decimal` ولا `timestamp` بلا منطقة.
- **References:** `table.column`، ومعناه مفتاح أجنبي بـ `ON DELETE RESTRICT` ما لم تذكر الملاحظات غير ذلك.
- **Notes**، رموز يقرؤها الفاحص:
  - `PK`: مفتاح أساسي.
  - `PII`: بيانات شخصية، ولها صف في `pii-registry.md`.
  - `values: a|b`: القيم المسموحة لقيد CHECK.
  - `TBD(WP-x.y)`: قيم لم تحددها المواصفات، تحددها الحزمة المذكورة.
  - `polymorphic`: معرّف مورفي، يرافقه عمود `*_type`.
  - `encrypted`: يُشفَّر في التطبيق (Laravel encrypted cast).
  - `hashed`: تجزئة لا تُعكس.
  - كل حالة مقترحة غير منصوصة في المواصفات تُكتب «proposed».
- معرّفات المزودين الخارجيين تنتهي بـ `_ref` لا `_id`.
- `created_by` و`updated_by` (`uuid`، يقبل الفراغ، مرجعه `users.id`) في الجداول التي يعدّلها البشر فقط.

## Spec/14 mapping

كل جدول في قسم «الجداول حسب المجال» من `spec/14`:
- `same`: الاسم نفسه.
- `renamed`: اسم جديد، والسبب مكتوب.
- `merged`: دُمج في جدول آخر.
- `package`: جدول حزمة.
- `deferred`: لإصدار لاحق، مع المتطلب الذي يأتي به.

| spec/14 table | Domain | Disposition | Approved table | Note |
| --- | --- | --- | --- | --- |
| users | الهوية والوصول | same | users | |
| roles | الهوية والوصول | package | roles | spatie/laravel-permission |
| permissions | الهوية والوصول | package | permissions | spatie/laravel-permission |
| role_permissions | الهوية والوصول | package | role_has_permissions | اسم جدول الحزمة (data-model-conventions.md) |
| user_roles | الهوية والوصول | package | model_has_roles | اسم جدول الحزمة (data-model-conventions.md) |
| auth_sessions | الهوية والوصول | same | auth_sessions | فهرس لجلسات Redis لقائمة الأجهزة والخروج من الكل (SEC-03)؛ جلسة الويب نفسها في Redis |
| mfa_factors | الهوية والوصول | merged | users | Fortify (ADR-002) يحفظ سر TOTP ورموز الاسترداد وتاريخ التأكيد في `users`؛ R1 يحتاج TOTP فقط (SEC-02) |
| student_pins | الهوية والوصول | same | student_pins | |
| device_authorizations | الهوية والوصول | same | device_authorizations | |
| legal_documents | الموافقات والقانون | same | legal_documents | كل صف إصدار واحد (النموذج `LegalDocumentVersion` في المسرد) |
| consents | الموافقات والقانون | same | consents | |
| data_requests | الموافقات والقانون | same | data_requests | |
| families | الأسر والطلاب | same | families | |
| guardians | الأسر والطلاب | same | guardians | |
| students | الأسر والطلاب | same | students | |
| student_profiles | الأسر والطلاب | same | student_profiles | |
| family_notes | الأسر والطلاب | same | family_notes | |
| leads | العملاء المحتملون | same | leads | |
| lead_events | العملاء المحتملون | same | lead_events | |
| follow_up_tasks | العملاء المحتملون | same | follow_up_tasks | |
| waitlist_entries | العملاء المحتملون | same | waitlist_entries | الوحدة Scheduling (المسرد) |
| programs | المنهج | same | programs | |
| stages | المنهج | same | stages | |
| levels | المنهج | same | levels | |
| level_outcomes | المنهج | merged | learning_outcomes | عبارات «يستطيع أن» لكل مستوى (CNT-09) صفوف في كتالوج النتاجات (D-26) |
| modules | المنهج | renamed | curriculum_units | تجنب الخلط مع وحدات الكود (data-model-conventions.md) |
| lessons | المنهج | same | lessons | |
| lesson_plans | المنهج | same | lesson_plans | |
| content_items | المنهج | same | content_items | |
| content_versions | المنهج | deferred | — | R2: CNT-10 (سجل النسخ والرجوع). R1 يكفيه `status` و`archived_at` (CNT-06) |
| media_assets | المنهج | same | media_assets | |
| qr_codes | المنهج | deferred | — | R2: CNT-11 |
| placement_quizzes | التقييم | same | placement_quizzes | |
| quiz_attempts | التقييم | same | quiz_attempts | |
| assessment_slots | التقييم | same | assessment_slots | |
| assessments | التقييم | same | assessments | |
| assessment_scores | التقييم | same | assessment_scores | |
| placements | التقييم | same | placements | |
| tests | التقييم | deferred | — | R2: ASM-04 |
| questions | التقييم | deferred | — | R2: ASM-04, ASM-07. أسئلة اختبار المستوى في `placement_quiz_items` (ASM-01) |
| test_attempts | التقييم | deferred | — | R2: ASM-05 |
| test_answers | التقييم | deferred | — | R2: ASM-05 |
| trials | التجارب | same | trials | |
| classes | الجدولة | same | classes | النموذج `ClassGroup` |
| class_schedule_rules | الجدولة | same | class_schedule_rules | |
| class_enrollments | الجدولة | same | class_enrollments | |
| enrollment_history | الجدولة | same | enrollment_history | |
| sessions | الجدولة | renamed | class_sessions | Laravel يستخدم `sessions` لجلسات الويب؛ المفاتيح الأجنبية تبقى `session_id` |
| session_changes | الجدولة | same | session_changes | |
| calendar_exceptions | الجدولة | same | calendar_exceptions | |
| teacher_availability | الجدولة | same | teacher_availability | |
| teacher_leaves | الجدولة | same | teacher_leaves | |
| change_requests | الجدولة | same | change_requests | النموذج `ScheduleChangeRequest` |
| student_availability | الجدولة | same | student_availability | ساعة محلية + منطقة (D-37) |
| live_rooms | الفصل الافتراضي | same | live_rooms | |
| join_token_log | الفصل الافتراضي | same | join_token_log | |
| participant_events | الفصل الافتراضي | same | participant_events | |
| recordings | الفصل الافتراضي | same | recordings | |
| recording_access_log | الفصل الافتراضي | same | recording_access_log | |
| session_attendance | الحضور والرصيد | same | session_attendance | |
| absence_requests | الحضور والرصيد | same | absence_requests | |
| credit_ledger_entries | الحضور والرصيد | same | credit_ledger_entries | |
| credit_balance_snapshots | الحضور والرصيد | same | credit_balance_snapshots | |
| rule_versions | الحضور والرصيد | same | rule_versions | الوحدة Platform (المسرد) |
| attendance_exceptions | الحضور والرصيد | same | attendance_exceptions | |
| plans | الفوترة | same | plans | |
| prices | الفوترة | same | prices | |
| discount_rules | الفوترة | same | discount_rules | |
| coupons | الفوترة | same | coupons | يحمل رموز الإحالة أيضاً (BIL-04) |
| coupon_redemptions | الفوترة | same | coupon_redemptions | |
| subscriptions | الفوترة | same | subscriptions | |
| subscription_events | الفوترة | same | subscription_events | |
| payment_methods | الفوترة | same | payment_methods | |
| payments | الفوترة | same | payments | |
| payment_allocations | الفوترة | same | payment_allocations | |
| invoices | الفوترة | same | invoices | |
| invoice_lines | الفوترة | same | invoice_lines | |
| refunds | الفوترة | same | refunds | |
| wallet_entries | الفوترة | same | wallet_entries | |
| webhook_events | الفوترة | package | webhook_calls | spatie/laravel-webhook-client؛ الوحدة Platform، لأحداث الدفع والفيديو معاً |
| settlements | الفوترة | deferred | — | R2: BIL-17 (مهمة التسوية R2 في spec/15) |
| assignments | التعلم | same | assignments | |
| submissions | التعلم | same | submissions | |
| submission_files | التعلم | same | submission_files | |
| feedback | التعلم | same | feedback | |
| reports | التعلم | same | reports | |
| report_items | التعلم | same | report_items | |
| report_reviews | التعلم | same | report_reviews | |
| message_templates | التواصل | same | message_templates | |
| notifications | التواصل | same | notifications | جدول Laravel القياسي للإشعارات داخل التطبيق |
| delivery_logs | التواصل | same | delivery_logs | |
| support_threads | التواصل | same | support_threads | |
| messages | التواصل | renamed | support_messages | رسائل تذاكر الدعم؛ الاسم يفصلها عن دردشة الحصة `session_chat_messages` |
| announcements | التواصل | same | announcements | |
| teachers | المعلمات | same | teachers | |
| teacher_applications | المعلمات | same | teacher_applications | |
| teacher_documents | المعلمات | same | teacher_documents | |
| trainings | المعلمات | same | trainings | |
| training_completions | المعلمات | same | training_completions | |
| observations | المعلمات | same | observations | |
| compensation_rules | المعلمات | same | compensation_rules | |
| compensation_lines | المعلمات | same | compensation_lines | |
| compensation_statements | المعلمات | same | compensation_statements | |
| payouts | المعلمات | same | payouts | |
| settings | النظام | same | settings | |
| audit_logs | النظام | same | audit_logs | |
| files | النظام | same | files | |
| feature_flags | النظام | package | features | Laravel Pennant |
| outbox_events | النظام | same | outbox_events | |
| safeguarding_incidents | النظام | same | safeguarding_incidents | الوحدة Families (المسرد) |

## Migration order

ترتيب إنشاء الجداول. كل مفتاح أجنبي يشير إلى جدول قبله، والفاحص يتحقق من ذلك. الطبقات حسب اعتماد الوحدات:
1. `users`.
2. نواة Platform.
3. بقية Identity.
4. Families.
5. نواة Teachers.
6. Curriculum.
7. Scheduling.
8. Live.
9. نواة Billing.
10. Ledger.
11. المحفظة.
12. Learning.
13. Messaging.
14. مستحقات Teachers.

هجرات Laravel الافتراضية الثلاث الموجودة (`users` بمعرّف `bigint`، و`cache`، و`jobs`) تستبدلها WP-1.1 بنسخ تتبع هذا الترتيب. الحزمة التي تنشئ كل جدول هي أول حزمة تحتاجه.

| # | Table | Module | Notes |
| --- | --- | --- | --- |
| 1 | users | Identity |  |
| 2 | settings | Platform |  |
| 3 | setting_versions | Platform |  |
| 4 | rule_versions | Platform |  |
| 5 | audit_logs | Platform |  |
| 6 | audit_log_personal_data | Platform |  |
| 7 | files | Platform |  |
| 8 | tags | Platform |  |
| 9 | taggables | Platform |  |
| 10 | outbox_events | Platform |  |
| 11 | features | Platform |  |
| 12 | webhook_calls | Platform |  |
| 13 | job_batches | Platform |  |
| 14 | failed_jobs | Platform |  |
| 15 | password_reset_tokens | Identity |  |
| 16 | auth_sessions | Identity |  |
| 17 | roles | Identity |  |
| 18 | permissions | Identity |  |
| 19 | role_has_permissions | Identity |  |
| 20 | model_has_roles | Identity |  |
| 21 | model_has_permissions | Identity |  |
| 22 | personal_access_tokens | Identity |  |
| 23 | families | Families |  |
| 24 | device_authorizations | Identity | after families |
| 25 | guardians | Families |  |
| 26 | students | Families |  |
| 27 | student_pins | Identity | after students |
| 28 | student_login_codes | Identity | after students |
| 29 | student_profiles | Families |  |
| 30 | family_notes | Families |  |
| 31 | family_invitations | Families |  |
| 32 | legal_documents | Families |  |
| 33 | consents | Families |  |
| 34 | cookie_consents | Families |  |
| 35 | data_requests | Families |  |
| 36 | leads | Families |  |
| 37 | lead_events | Families |  |
| 38 | follow_up_tasks | Families |  |
| 39 | teachers | Teachers |  |
| 40 | teacher_bank_accounts | Teachers |  |
| 41 | teacher_applications | Teachers |  |
| 42 | teacher_interviews | Teachers |  |
| 43 | teacher_documents | Teachers |  |
| 44 | trainings | Teachers |  |
| 45 | training_completions | Teachers |  |
| 46 | programs | Curriculum |  |
| 47 | stages | Curriculum |  |
| 48 | levels | Curriculum |  |
| 49 | teacher_levels | Teachers | after levels |
| 50 | curriculum_units | Curriculum |  |
| 51 | lessons | Curriculum |  |
| 52 | lesson_plans | Curriculum |  |
| 53 | media_assets | Curriculum |  |
| 54 | content_items | Curriculum |  |
| 55 | learning_outcomes | Curriculum |  |
| 56 | outcome_alignments | Curriculum |  |
| 57 | placement_quizzes | Curriculum |  |
| 58 | placement_quiz_items | Curriculum |  |
| 59 | quiz_attempts | Curriculum |  |
| 60 | assessment_slots | Curriculum |  |
| 61 | assessments | Curriculum |  |
| 62 | assessment_scores | Curriculum |  |
| 63 | placements | Curriculum |  |
| 64 | classes | Scheduling |  |
| 65 | class_schedule_rules | Scheduling |  |
| 66 | seat_holds | Scheduling |  |
| 67 | class_enrollments | Scheduling |  |
| 68 | enrollment_history | Scheduling |  |
| 69 | class_sessions | Scheduling |  |
| 70 | safeguarding_incidents | Families | after class_sessions |
| 71 | safeguarding_incident_actions | Families |  |
| 72 | trials | Curriculum | after class_enrollments, class_sessions |
| 73 | calendar_exceptions | Scheduling |  |
| 74 | session_changes | Scheduling |  |
| 75 | teacher_availability | Scheduling |  |
| 76 | teacher_leaves | Scheduling |  |
| 77 | change_requests | Scheduling |  |
| 78 | waitlist_entries | Scheduling |  |
| 79 | student_availability | Scheduling |  |
| 80 | live_rooms | Live |  |
| 81 | join_token_log | Live |  |
| 82 | participant_events | Live |  |
| 83 | recordings | Live |  |
| 84 | recording_access_log | Live |  |
| 85 | session_chat_messages | Live |  |
| 86 | plans | Billing |  |
| 87 | prices | Billing |  |
| 88 | discount_rules | Billing |  |
| 89 | coupons | Billing |  |
| 90 | payment_methods | Billing |  |
| 91 | subscriptions | Billing |  |
| 92 | payments | Billing |  |
| 93 | payment_allocations | Billing |  |
| 94 | subscription_events | Billing |  |
| 95 | coupon_redemptions | Billing |  |
| 96 | invoices | Billing |  |
| 97 | invoice_lines | Billing |  |
| 98 | refunds | Billing |  |
| 99 | absence_requests | Ledger |  |
| 100 | session_attendance | Ledger |  |
| 101 | credit_lots | Ledger |  |
| 102 | credit_ledger_entries | Ledger |  |
| 103 | credit_balance_snapshots | Ledger |  |
| 104 | attendance_exceptions | Ledger |  |
| 105 | wallet_entries | Billing |  |
| 106 | assignments | Learning |  |
| 107 | submissions | Learning |  |
| 108 | submission_files | Learning |  |
| 109 | feedback | Learning |  |
| 110 | reports | Learning |  |
| 111 | report_items | Learning |  |
| 112 | report_reviews | Learning |  |
| 113 | message_templates | Messaging |  |
| 114 | message_template_versions | Messaging |  |
| 115 | notifications | Messaging |  |
| 116 | announcements | Messaging |  |
| 117 | delivery_logs | Messaging |  |
| 118 | notification_preferences | Messaging |  |
| 119 | suppressed_recipients | Messaging |  |
| 120 | support_threads | Messaging |  |
| 121 | support_messages | Messaging |  |
| 122 | observations | Teachers |  |
| 123 | compensation_rules | Teachers |  |
| 124 | compensation_statements | Teachers |  |
| 125 | compensation_lines | Teachers |  |
| 126 | payouts | Teachers |  |
