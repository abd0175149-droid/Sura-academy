# سجل البيانات الشخصية (تصميم `config/privacy.php`)

ينفّذ D-14 وADR-009 §5. كل عمود فيه بيانات شخصية موسوم `PII` في ملف وحدته وله صف هنا، والفاحص يتحقق من التطابق في الاتجاهين، ومن أن كل نص حر موسوم أو مبرَّر بـ `no-PII:`. WP-1.5 تحوّل هذا السجل إلى `config/privacy.php`. مهمة طلب الحذف (PRV-07) ومهمة الاحتفاظ الأسبوعية (spec/15) تقرآنه، ولا تعرفان أي عمود شخصي من خارجه.

## الأفعال

| الفعل | المعنى |
| --- | --- |
| `erase` | يُفرَّغ العمود (`NULL`) |
| `pseudonymize` | يُستبدل بقيمة ثابتة لا تدل على الشخص (مثل `محذوف-3f9a` أو `deleted+<id>@invalid`)، للأعمدة الإلزامية أو المعروضة في السجلات |
| `delete_row` | يُحذف الصف كله، لأنه شخصي كله |
| `delete_file` | يُحذف الملف من التخزين ثم صفه في `files` |
| `retain_until_period` | يبقى رغم طلب الحذف حتى نهاية مدة قانونية أو مدة احتفاظ، ثم يُحذف أو تُخفى هويته وفق الجدول أدناه. يُستعمل أيضاً حين لا يمكن ربط الصف بشخص بعينه (نص مشترك، أو حمولة مزود) |

عمود **Locate by**: كيف تجد مهمة الحذف صفوف الشخص، أي مسار `table.column` يصل إلى معرّفه. الصفوف التي لا ترتبط بشخص بعينه تذكر عمود ساعة الاحتفاظ بدلاً منه، وفعلها `retain_until_period`.

قواعد عامة (D-14):
- القيود المالية ودفتر الرصيد والحضور وتاريخ التسجيل لا تُحذف. تبقى مرتبطة بمعرّفات لا تدل على شخص بعد محو الاسم والبريد والهاتف (`students.pseudonymized_at`، `users.pseudonymized_at`).
- جداول الإضافة فقط لا تحمل نصاً حراً، والنص الحر في `record_notes` القابل للمحو. يُستثنى ما يُحذف صفه كله بدور الاحتفاظ أو يُحفظ بمدة D-15: دردشة الحصة وخطوات بلاغ الحماية.
- سجل التدقيق يحتفظ بمعرّف الفاعل فقط. عنوانه وجهازه والقيم الشخصية في `audit_log_personal_data`، ويُحذف ذلك الصف. لا يُسجَّل عنوان ولا جهاز حين يكون الفاعل طفلاً.
- الحذف القانوني ينفّذه دور قاعدة البيانات `sura_retention` فقط، بدالة حذف لكل جدول (data-model-conventions.md)، ويُسجَّل كل حذف في `audit_logs` (`privacy.erased`، `retention.deleted`) بالمعرّفات فقط.
- طلب حذف بيانات طفل يحذف أيضاً تسجيلات الجلسات التي حضرها ضمن مدة احتفاظها، ويجدها عبر `session_attendance.student_id` (D-14 «الملفات والتسجيلات تُحذف نهائياً»، D-11 «ما لم يُطلب حذفها»).
- لا يُحذف ولا يُمحى أي صف عليه تعليق قانوني غير محرر في `legal_holds` (D-38، الخيار أ).
- لا تُرسل بيانات شخصية إلى السجلات التقنية ولا Sentry ولا التحليلات ولا أي نموذج ذكاء اصطناعي (SEC-12، ADR-009 §4 و§7).

## الأعمدة

| Column | Subject | Locate by | Action | Retention | Source |
| --- | --- | --- | --- | --- | --- |
| `users.name` | guardian / teacher / staff | `users.id` | pseudonymize | account life | D-14 |
| `users.email` | guardian / teacher / staff | `users.id` | pseudonymize | account life | D-14 |
| `users.phone_e164` | guardian / teacher / staff | `users.id` | erase | account life | D-14 |
| `password_reset_tokens.email` | adult | `users.email` | delete_row | 60 minutes | SEC-01 |
| `auth_sessions.ip` | adult | `auth_sessions.user_id` | erase | 12 months (technical logs) | F17:49 |
| `auth_sessions.user_agent` | adult | `auth_sessions.user_id` | erase | 12 months (technical logs) | F17:49 |
| `device_authorizations.device_label` | guardian | `device_authorizations.family_id` | erase | while the device is authorized | SEC-04 |
| `audit_log_personal_data.ip` | adult | `audit_log_personal_data.audit_log_id`, `audit_logs.actor_id` | delete_row | audit period, at least 2 years; earlier on an erasure request | SEC-11, F17:50, D-14 |
| `audit_log_personal_data.user_agent` | adult | `audit_log_personal_data.audit_log_id`, `audit_logs.actor_id` | delete_row | as above | SEC-11, D-14 |
| `audit_log_personal_data.personal_changes` | any | `audit_log_personal_data.audit_log_id`, `audit_logs.entity_id` | delete_row | as above | SEC-11, D-14 |
| `record_notes.body` | free text | `record_notes.subject_student_id`, `record_notes.subject_user_id`, `record_notes.subject_family_id` | delete_row | with the noted record's period; earlier on an erasure request | D-14 |
| `files.path` | any (owner decides) | `submission_files.file_id`, `recordings.file_id`, `teacher_documents.file_id`, `payments.transfer_proof_file_id`, `data_requests.export_file_id`, `files.uploader_id` | delete_file | by owner: homework 12 months after the student leaves; recordings 30 / 90 days; transfer proofs and invoices per accounting law; teacher documents not stated | F17:44-47 |
| `files.original_name` | any | `submission_files.file_id`, `teacher_documents.file_id`, `files.uploader_id` | erase | with the file | D-14 |
| `webhook_calls.payload` | guardian / child | `webhook_calls.created_at` | retain_until_period | 12 months (technical logs); not linkable to a person | F17:49 |
| `webhook_calls.headers` | adult | `webhook_calls.created_at` | retain_until_period | 12 months (technical logs) | F17:49 |
| `failed_jobs.exception` | any | `failed_jobs.failed_at` | retain_until_period | 12 months (technical logs) | F17:49 |
| `webhook_calls.exception` | any | `webhook_calls.created_at` | retain_until_period | 12 months (technical logs) | F17:49 |
| `outbox_events.last_error` | any | `outbox_events.occurred_at` | retain_until_period | 12 months (technical logs) | F17:49 |
| `family_invitations.email` | guardian | `family_invitations.family_id` | delete_row | until accepted or expired | ONB-12 |
| `students.first_name` | child | `students.id` | pseudonymize | account life; proposed: pseudonymized at `departed_at` + 3 years (as reports) | PRV-04, D-14 |
| `students.first_name_normalized` | child | `students.id` | erase | proposed: erased at `departed_at` | D-09, D-14 |
| `students.birth_month` | child | `students.id` | erase | proposed: erased at `departed_at` + 3 years | PRV-04, D-14 |
| `students.birth_year` | child | `students.id` | erase | proposed: erased at `departed_at` + 3 years | PRV-04, D-14 |
| `student_profiles.primary_language` | child | `student_profiles.student_id` | erase | proposed: 12 months after `departed_at` | ONB-03 |
| `student_profiles.self_assessment` | child | `student_profiles.student_id` | erase | proposed: 12 months after `departed_at` | ONB-03 |
| `student_profiles.notes` | child | `student_profiles.student_id` | erase | proposed: 12 months after `departed_at` | ONB-04 |
| `family_notes.body` | free text | `family_notes.family_id` | delete_row | account life (period not stated) | ADM-02 |
| `consents.ip` | guardian / teacher | `consents.user_id` | retain_until_period | account life + 3 years | D-15 |
| `consents.user_agent` | guardian / teacher | `consents.user_id` | retain_until_period | account life + 3 years | D-15 |
| `cookie_consents.visitor_ref` | visitor | `cookie_consents.user_id`, `cookie_consents.created_at` | retain_until_period | cookie lifetime, 12 months (proposed; not stated) | PUB-10 |
| `data_requests.resolution_note` | free text | `data_requests.family_id` | retain_until_period | as evidence of the request, 3 years after completion (proposed) | PRV-07 |
| `leads.name` | lead | `leads.email`, `leads.converted_family_id` | delete_row | 12 months without a subscription; 30 days after conversion (proposed) | F17:48 |
| `leads.email` | lead | `leads.email`, `leads.converted_family_id` | delete_row | as above | F17:48 |
| `leads.phone_e164` | lead | `leads.email`, `leads.converted_family_id` | delete_row | as above | F17:48 |
| `leads.message` | lead | `leads.email`, `leads.converted_family_id` | delete_row | as above | F17:48 |
| `lead_events.body` | lead | `lead_events.lead_id` | delete_row | with the lead | F17:48 |
| `follow_up_tasks.title` | free text | `follow_up_tasks.family_id`, `follow_up_tasks.lead_id` | pseudonymize | with the family or lead | ADM-02 |
| `follow_up_tasks.body` | free text | `follow_up_tasks.family_id`, `follow_up_tasks.lead_id` | erase | with the family or lead | ADM-02 |
| `waitlist_entries.child_age` | child (visitor) | `waitlist_entries.lead_id` | delete_row | with the lead | F17:48 |
| `safeguarding_incidents.description` | child | `safeguarding_incidents.student_id` | retain_until_period | 7 years | D-15 |
| `safeguarding_incidents.student_first_name_snapshot` | child | `safeguarding_incidents.student_id` | retain_until_period | 7 years | D-15 |
| `safeguarding_incident_actions.action_taken` | child | `safeguarding_incident_actions.incident_id` | retain_until_period | 7 years | D-15 |
| `quiz_attempts.answers` | child | `quiz_attempts.student_id` | erase | 3 years after the student leaves (grades) | F17:46 |
| `assessments.recommendation` | child | `assessments.student_id` | erase | 3 years after the student leaves | F17:46 |
| `classes.capacity_override_reason` | free text | `classes.archived_at` | retain_until_period | 3 years after the class ends (proposed); not linkable to one person | F17:46 |
| `classes.status_reason` | free text | `classes.archived_at` | retain_until_period | as above | F17:46 |
| `class_sessions.teacher_notes` | free text (whole class) | `class_sessions.class_id` | retain_until_period | 3 years after the class ends (proposed, as reports); shared by several children | F17:46 |
| `class_sessions.maintenance_override_reason` | free text | `class_sessions.class_id` | retain_until_period | as above | F17:46 |
| `session_changes.reason_note` | free text | `session_changes.session_id` | retain_until_period | as above | F17:46 |
| `change_requests.details` | guardian / teacher | `change_requests.requester_id` | erase | account life | PAR-06, TCH-11 |
| `change_requests.decision_note` | free text | `change_requests.requester_id` | erase | account life | PAR-06, TCH-11 |
| `teacher_leaves.reason` | teacher (may be health) | `teacher_leaves.teacher_id` | erase | employment (period after leaving not stated) | TCH-09 |
| `absence_requests.reason` | guardian / child | `absence_requests.student_id` | erase | with attendance; the text is erased on request, the row stays | spec/14, D-14 |
| `absence_requests.decision_note` | free text | `absence_requests.student_id` | erase | as above | spec/14, D-14 |
| `attendance_exceptions.details` | free text | `attendance_exceptions.student_id` | erase | as above | ATT-06 |
| `attendance_exceptions.resolution_note` | free text | `attendance_exceptions.student_id` | erase | as above | ATT-06 |
| `live_rooms.fallback_reason` | free text | `live_rooms.fallback_created_by` | erase | 12 months (technical) | CLS-15 |
| `session_chat_messages.body` | free text | `session_chat_messages.sender_id` | delete_row | 12 months | D-15 |
| `submissions.text_body` | child | `submissions.student_id` | erase | 12 months after the student leaves | F17:45 |
| `feedback.note` | child | `feedback.submission_id`, `submissions.student_id` | erase | 3 years after the student leaves (grades) | F17:46 |
| `report_items.body` | child | `report_items.report_id`, `reports.student_id` | erase | 3 years after the student leaves, then anonymized | F17:46 |
| `report_reviews.comment` | child | `report_reviews.report_id`, `reports.student_id` | erase | as above | F17:46 |
| `notifications.data` | guardian / child | `notifications.notifiable_id` | delete_row | 12 months (proposed; not stated) | NOT-03 |
| `delivery_logs.recipient_masked` | adult | `delivery_logs.recipient_id` | retain_until_period | 12 months (technical logs) | F17:49 |
| `support_messages.body` | free text | `support_messages.thread_id`, `support_threads.family_id` | delete_row | 12 months after the thread closes | D-15 |
| `subscriptions.cancel_reason` | guardian | `subscriptions.family_id` | erase | account life | BIL-14 |
| `payment_methods.provider_token_ref` | guardian | `payment_methods.family_id` | delete_row | until revoked; the token is revoked at the gateway first | BIL-05 |
| `payment_methods.last4` | guardian | `payment_methods.family_id` | delete_row | with the method | BIL-05 |
| `payment_methods.fingerprint` | guardian | `payment_methods.family_id` | delete_row | with the method | D-09 |
| `invoices.customer_snapshot` | guardian | `invoices.family_id` | retain_until_period | Jordanian law and accounting rules, restricted archive | F17:47 |
| `teachers.qualifications` | teacher | `teachers.user_id` | erase | employment (period after leaving not stated) | TOP-02 |
| `teacher_bank_accounts.account_holder_name` | teacher | `teacher_bank_accounts.teacher_id` | delete_row | until the last payout after leaving (period not stated) | SEC-08 |
| `teacher_bank_accounts.iban` | teacher | `teacher_bank_accounts.teacher_id` | delete_row | as above | SEC-08 |
| `teacher_bank_accounts.cliq_alias` | teacher | `teacher_bank_accounts.teacher_id` | delete_row | as above | SEC-08 |
| `teacher_applications.applicant_name` | applicant | `teacher_applications.email` | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_applications.email` | applicant | `teacher_applications.email` | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_applications.phone_e164` | applicant | `teacher_applications.email` | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_applications.cover_note` | applicant | `teacher_applications.email` | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_interviews.recommendation` | applicant | `teacher_interviews.teacher_application_id` | delete_row | with the application | TOP-01 |
| `observations.notes` | teacher | `observations.teacher_id` | erase | not stated | TOP-01 |
| `compensation_rules.reason` | teacher | `compensation_rules.teacher_id` | retain_until_period | financial records, per Jordanian law | F17:47 |
| `compensation_lines.reason` | teacher | `compensation_lines.teacher_id` | retain_until_period | financial records, per Jordanian law | F17:47 |

## جدول الاحتفاظ حسب نوع البيانات

لمهمة الاحتفاظ الأسبوعية (spec/15). المصدر جدول الاحتفاظ في `spec/17` (F17:42-50) وافتراضات D-15 (تحتاج مراجعة قانونية).

| نوع البيانات | الجداول | المدة | بعدها | المصدر |
| --- | --- | --- | --- | --- |
| تسجيلات الحصص | `recordings`، ملفاتها | 30 يوماً للأسر، 90 للإدارة | حذف نهائي، ومن المزود خلال 24 ساعة | F17:44، D-16 |
| ملفات الواجبات والتسجيلات الصوتية | `submission_files`، `files`، `submissions.text_body` | 12 شهراً بعد مغادرة الطالب (`students.departed_at`) | حذف نهائي | F17:45 |
| التقارير والدرجات | `reports`، `report_items`، `report_reviews`، `feedback`، `quiz_attempts`، `assessments` | 3 سنوات بعد مغادرة الطالب | إخفاء الهوية (تبقى الصفوف، spec/14) | F17:46 |
| بيانات الطفل الأساسية | `students`، `student_profiles` | مقترح: الملف والإجابات 12 شهراً، والاسم وتاريخ الميلاد 3 سنوات بعد المغادرة | محو أو اسم مستعار | PRV-06 (مقترح) |
| السجلات المالية | `payments`، `invoices`، `refunds`، `wallet_entries`، `credit_ledger_entries`، `compensation_*` | حسب القانون الأردني | أرشيف مقيد الوصول | F17:47 |
| العملاء المحتملون دون اشتراك | `leads`، `lead_events`، `waitlist_entries` للزوار | 12 شهراً | حذف | F17:48 |
| السجلات التقنية | `auth_sessions`، `webhook_calls`، `delivery_logs`، `join_token_log`، `failed_jobs`، `outbox_events` المنشورة | 12 شهراً | حذف | F17:49 |
| سجل التدقيق | `audit_logs`، `audit_log_personal_data`، `recording_access_log` | سنتان على الأقل | أرشيف | F17:50 |
| دردشة الحصة | `session_chat_messages` | 12 شهراً | حذف | D-15 |
| أحداث الدخول والخروج | `participant_events` | 12 شهراً | حذف | D-15 |
| الموافقات | `consents` | عمر الحساب + 3 سنوات | حذف | D-15 |
| بلاغات الحماية | `safeguarding_incidents`، `safeguarding_incident_actions` | 7 سنوات | حذف | D-15 |
| محادثات الدعم | `support_threads`، `support_messages` | 12 شهراً بعد الإغلاق | حذف | D-15 |
| أسباب مكتوبة | `record_notes` | مدة السجل الذي تشرحه | حذف | D-14 |
| ملف تصدير البيانات | `data_requests.export_file_id` | 30 يوماً بعد الإنجاز (مقترح) | حذف | PRV-07 (مقترح) |

متى تُضبط `students.departed_at`: مقترح أن تُضبط تلقائياً بعد 90 يوماً بلا تسجيل نشط ولا اشتراك حي، أو فوراً عند إغلاق الحساب. تحسمه WP-5.2.

مدد غير منصوصة، تذهب إلى ملف المستشار القانوني (WP-0.8):
- بيانات الطفل الأساسية بعد مغادرته.
- بيانات المتقدمات للتوظيف.
- وثائق المعلمات وبياناتها البنكية بعد انتهاء العمل.
- ملاحظات CRM.
- الإشعارات داخل التطبيق.
- موافقات ملفات الارتباط.
- ملف تصدير البيانات وسجل طلباتها.
- تعليق الحذف لأدلة بلاغات الحماية (D-38).

كل مدة موسومة «مقترح» أعلاه افتراضية حتى يُحسم.

## من يستلم البيانات (لإشعار PRV-01 وقائمة اتفاقيات PRV-08)

| المستلم | ما يصله | المنطقة | المرجع |
| --- | --- | --- | --- |
| LiveKit Cloud (الفيديو) | معرّف المشارك المعتم، والاسم الأول للطفل، وعنوان IP ومعلومات الجهاز، والصوت والصورة مباشرة | منطقة بيانات أوروبية؛ حصر المرور في D-35 | ADR-005، D-35 |
| AWS (الاستضافة) | قاعدة البيانات والخوادم وRedis، أي كل بيانات المنصة | `eu-central-1` | architecture.md §6، 16:15 |
| Amazon S3 | الملفات والتسجيلات | `eu-central-1` | D-16 |
| Amazon SES (البريد) | بريد ولي الأمر، والاسم الأول للطفل في نص بعض الرسائل | تُختار منطقة أوروبية؛ مراجعة قانونية في WP-0.8 | 16:13 |
| Twilio (الرسائل النصية والتحقق) | هاتف البالغ؛ لا بيانات طفل أبداً | مراجعة قانونية في WP-0.8 | 16:12 |
| PayTabs (الدفع) | بيانات فوترة ولي الأمر وبطاقته (لدى البوابة فقط) | الأردن | ADR-006، D-36 |
| PostHog | معرّفات معتمة فقط، ولا شيء من وضع الطفل | السحابة الأوروبية | D-23 |
| Sentry | الأخطاء بلا بيانات شخصية | مراجعة قانونية في WP-0.8 | ADR-009 §4 |

## شكل `config/privacy.php` (لـ WP-1.5)

يُكتب من الجدولين أعلاه، واختبار في WP-1.5 يقارنه بهذا السجل. هذا هو الشكل الوحيد المعتمد (design.md §3.4 يشير إليه).

```php
return [
    // Erasure request (PRV-07, PAR-13): table => locate path + [column => action]. Files go first.
    'erasure' => [
        'files' => ['locate' => ['owner_id', 'uploader_id'], 'columns' => ['path' => 'delete_file', 'original_name' => 'erase']],
        'users' => ['locate' => ['id'], 'columns' => ['name' => 'pseudonymize', 'email' => 'pseudonymize', 'phone_e164' => 'erase']],
        'students' => ['locate' => ['id'], 'columns' => [
            'first_name' => 'pseudonymize', 'first_name_normalized' => 'erase', 'birth_month' => 'erase', 'birth_year' => 'erase',
        ]],
        'report_items' => ['locate' => ['report_id' => 'reports.student_id'], 'columns' => ['body' => 'erase']],
        'audit_log_personal_data' => ['locate' => ['audit_log_id' => 'audit_logs.actor_id'], 'columns' => ['*' => 'delete_row']],
        'consents' => ['locate' => ['user_id'], 'columns' => ['ip' => 'retain_until_period', 'user_agent' => 'retain_until_period']],
        // … one entry per table in the registry
    ],
    // Weekly retention job (spec/15): what to delete or anonymize, and when. Periods come from settings when a key exists.
    'retention' => [
        'recordings' => ['clock' => 'available_at', 'days' => ['family' => 'recordings.family_days', 'admin' => 'recordings.admin_days'], 'action' => 'delete_file', 'source' => 'F17:44'],
        'submission_files' => ['clock' => 'students.departed_at', 'months' => 12, 'action' => 'delete_file', 'source' => 'F17:45'],
        'report_items' => ['clock' => 'students.departed_at', 'years' => 3, 'action' => 'erase', 'source' => 'F17:46'],
        'participant_events' => ['clock' => 'occurred_at', 'months' => 12, 'action' => 'delete_row', 'source' => 'D-15'],
        'safeguarding_incidents' => ['clock' => 'closed_at', 'years' => 7, 'action' => 'delete_row', 'source' => 'D-15'],
        // … one entry per row of the retention table
    ],
    // D-38 option a: both jobs skip any row with an unreleased legal hold.
    'honour_legal_holds' => true,
];
```
