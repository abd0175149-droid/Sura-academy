# اصطلاحات نموذج البيانات

تكمل هذه القواعد `spec/14-data-model-and-states.md`. كل هجرة تلتزم بها، ووكيل `ledger-auditor` يراجعها.

## الأسماء

- الجداول بالجمع وsnake_case، والنماذج بالمفرد وPascalCase. المسرد `docs/00-governance/glossary.md` إلزامي.
- **أسماء معدّلة عن المواصفات (مع السبب):**

| في المواصفات | المعتمد | السبب |
| --- | --- | --- |
| `sessions` | `class_sessions` (والمفاتيح الأجنبية تبقى `session_id`) | Laravel يستخدم `sessions` لجلسات الويب |
| `modules` | `curriculum_units` | تجنب الخلط مع وحدات الكود |
| `classes` (النموذج) | الجدول `classes` والنموذج `ClassGroup` | `Class` كلمة محجوزة في PHP |
| `role_permissions`, `user_roles` | `role_has_permissions`, `model_has_roles` | جداول حزمة spatie/laravel-permission |
| `feature_flags` | `features` | جدول Laravel Pennant |
| `webhook_events` | `webhook_calls` | حزمة spatie/laravel-webhook-client |
| `notifications` | `notifications` (جدول Laravel القياسي للإشعارات داخل التطبيق) + `delivery_logs` لكل قناة | توافق مع قناة قاعدة البيانات في Laravel |

- جلسات الويب في Redis (`SESSION_DRIVER=redis`). تُحذف هجرة جدول `sessions` الافتراضية في Laravel.

## الأنواع

| النوع | القاعدة |
| --- | --- |
| المعرّف | `uuid` بالإصدار 7 (`HasUuids` في Laravel)، مفتاح أساسي `id` |
| اللحظة | `timestampTz` بتوقيت UTC، الاسم ينتهي بـ `_at` (`starts_at`) |
| التاريخ دون وقت | `date`، الاسم ينتهي بـ `_on` |
| الساعة المحلية | `time` مع عمود `ref_timezone` (IANA) في الجدول نفسه |
| المال | `bigInteger` باسم `*_minor` + عمود `currency` (`char(3)`)؛ الدينار بالفلس، الدولار بالسنت |
| النسبة | `integer` بنقاط الأساس (`*_bps`: 12.5% = 1250) |
| الحالة | `string` مع قيد CHECK بالقيم المسموحة + PHP backed enum |
| النصوص ثنائية اللغة | عمودان `*_ar` و`*_en` للنصوص القصيرة؛ جداول `*_translations` للمحتوى الطويل |
| JSON | `jsonb` فقط لبيانات بلا قواعد عمل (لقطات، سياق)، لا لحقول يُستعلم عنها |

## الأعمدة الإلزامية

- `created_at`, `updated_at` في كل جدول قابل للتعديل؛ `created_by` و`updated_by` (uuid قابل للفراغ) في الجداول التي يعدّلها البشر.
- `archived_at` للأرشفة بدل الحذف.
- أعمدة النطاق حيث تنطبق: `family_id`، `class_id`، `teacher_id`، لتطبيق سياسات الصلاحيات بالاستعلام.

## جداول الإضافة فقط

`credit_ledger_entries`، `audit_logs`، `wallet_entries`، `subscription_events`، `enrollment_history`، `consents`، `setting_versions`، `rule_versions`، `participant_events`، `delivery_logs`، `join_token_log`، `recording_access_log`، `safeguarding_incident_actions`.

أُضيفت الثلاثة الأخيرة في WP-0.7، وكلها سجلات وصول أو خطوات (SEC-10، SAFE-02).

لكل منها:
1. بلا `updated_at`.
2. محفّز في PostgreSQL يرفض التعديل والحذف:

```sql
CREATE OR REPLACE FUNCTION forbid_mutation() RETURNS trigger AS $$
BEGIN
  RAISE EXCEPTION 'append-only table %: % is forbidden', TG_TABLE_NAME, TG_OP
    USING ERRCODE = 'insufficient_privilege';
END $$ LANGUAGE plpgsql;

CREATE TRIGGER credit_ledger_entries_append_only
  BEFORE UPDATE OR DELETE ON credit_ledger_entries
  FOR EACH ROW EXECUTE FUNCTION forbid_mutation();
```

3. سحب صلاحيتي UPDATE وDELETE من دور قاعدة بيانات التطبيق (`sura_app`). الهجرات تعمل بدور منفصل (`sura_migrator`).
4. مهام الاحتفاظ التي تحتاج حذفاً قانونياً تعمل بدور ثالث محدود (`sura_retention`) على جداول بعينها، وتسجّل كل حذف في التدقيق.

## الفهارس والقيود

- مفتاح أجنبي لكل علاقة مع `ON DELETE RESTRICT` افتراضاً.
- قيد فريد `(student_id, session_id, type)` في `credit_ledger_entries` حيث `session_id` ليس فارغاً.
- قيد فريد `idempotency_key` في كل جدول يقبل عمليات مالية أو رصيدية.
- `payments.provider_ref` فريد لكل مزود.
- فهارس لأعمدة النطاق والحالة والزمن الأكثر استعلاماً.

## الهجرات

- اسم الملف يبدأ بالوحدة: `2026_12_14_000100_ledger_create_credit_ledger_entries.php`.
- لا تعديل لهجرة دُمجت في الفرع الرئيسي؛ التغيير بهجرة جديدة.
- التغييرات المدمرة على مرحلتين (توسيع ثم تقليص) كما في خط النشر.
- كل هجرة تعمل على قاعدة فارغة ضمن الاختبارات.

## فصل البيانات الشخصية (D-14)

البيانات الشخصية القابلة للمحو في أعمدة أو جداول معروفة مسجلة في `config/privacy.php`: أسماء أولياء الأمور، البريد، الهاتف، الاسم الأول للطفل، الملاحظات الحرة، الملفات المرفوعة. طلب الحذف يستبدلها باسم مستعار أو يمحوها، ويُبقي الصفوف المالية والرصيدية بمعرّفاتها.

## جداول مضافة على المواصفات

| الجدول | الوحدة | السبب |
| --- | --- | --- |
| `credit_lots` | Ledger | D-05: صلاحية الرصيد واستهلاكه بالترتيب |
| `setting_versions` | Platform | تاريخ قيم الإعدادات (ADM-12) |
| `seat_holds` | Scheduling | D-30: حجز المقعد أثناء الدفع |
| `notification_preferences` | Messaging | PAR-12 |
| `learning_outcomes`, `outcome_alignments` | Curriculum | CR-002 / D-26؛ `level_outcomes` في spec/14 دُمج في `learning_outcomes` (WP-0.7) |
| `audit_log_personal_data` | Platform | D-14 + SEC-11: الجزء الشخصي من سجل التدقيق قابل للمحو (WP-0.7) |
| `tags`, `taggables` | Platform | ADM-02، CNT-07: الوسوم (WP-0.7) |
| `student_login_codes` | Identity | STU-01: رمز لمرة واحدة أو QR (WP-0.7) |
| `family_invitations` | Families | ONB-12: دعوة العمليات (WP-0.7) |
| `cookie_consents` | Families | PUB-10: الزائر بلا حساب (WP-0.7) |
| `safeguarding_incident_actions` | Families | SAFE-02: كل خطوة في البلاغ (WP-0.7) |
| `placement_quiz_items` | Curriculum | ASM-01: أسئلة اختبار المستوى؛ `questions` في spec/14 لبنك R2 (WP-0.7) |
| `session_chat_messages` | Live | CLS-07: دردشة الحصة المحفوظة (WP-0.7) |
| `message_template_versions` | Messaging | NOT-07: إصدارات القوالب (WP-0.7) |
| `suppressed_recipients` | Messaging | NOT-09: العناوين المرتدة (WP-0.7) |
| `teacher_levels` | Teachers | TOP-02، SCH-11: المستويات المؤهلة لها المعلمة (WP-0.7) |
| `teacher_bank_accounts` | Teachers | TOP-02، SEC-08: البيانات البنكية مشفرة ومنفصلة (WP-0.7) |
| `teacher_interviews` | Teachers | TOP-01: نموذج تقييم المقابلة (WP-0.7) |
| `job_batches`, `failed_jobs`, `password_reset_tokens`, `personal_access_tokens`, `model_has_permissions` | Platform، Identity | جداول Laravel والحزم (WP-0.7) |

المخطط الكامل لكل جدول في `docs/02-architecture/erd/` (WP-0.7)، و`php scripts/check-erd.php` يفحص اتساقه مع هذا الملف.
