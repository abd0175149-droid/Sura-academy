# سجل البيانات الشخصية (تصميم `config/privacy.php`)

ينفّذ D-14 وADR-009 §5. كل عمود فيه بيانات شخصية قابلة للمحو موسوم `PII` في ملف وحدته وله صف هنا، والفاحص يتحقق من التطابق في الاتجاهين. WP-1.5 تحوّل هذا السجل إلى `config/privacy.php`. مهمة طلب الحذف (PRV-07) ومهمة الاحتفاظ الأسبوعية (spec/15) تقرآنه، ولا تعرفان أي عمود شخصي من خارجه.

## الأفعال

| الفعل | المعنى |
| --- | --- |
| `erase` | يُفرَّغ العمود (`NULL`) |
| `pseudonymize` | يُستبدل بقيمة ثابتة لا تدل على الشخص (مثل `محذوف-3f9a` أو `deleted+<id>@invalid`)، للأعمدة الإلزامية أو المعروضة في السجلات |
| `delete_row` | يُحذف الصف كله، لأنه شخصي كله |
| `delete_file` | يُحذف الملف من التخزين ثم صفه في `files` |
| `retain_until_period` | يبقى رغم طلب الحذف حتى نهاية مدة قانونية أو مدة احتفاظ، ثم يُحذف أو تُخفى هويته وفق الجدول أدناه |

قواعد عامة (D-14):
- القيود المالية ودفتر الرصيد والحضور وتاريخ التسجيل لا تُحذف. تبقى مرتبطة بمعرّفات لا تدل على شخص بعد محو الاسم والبريد والهاتف (`students.pseudonymized_at`، `users.pseudonymized_at`).
- سجل التدقيق يحتفظ بمعرّف الفاعل فقط. عنوانه وجهازه والقيم الشخصية قبل التعديل وبعده في `audit_log_personal_data`، ويُحذف ذلك الصف.
- الحذف القانوني ينفّذه دور قاعدة البيانات `sura_retention` على جداول بعينها، ويُسجَّل كل حذف في `audit_logs` (`privacy.erased`، `retention.deleted`) بالمعرّفات فقط.
- لا تُرسل بيانات شخصية إلى السجلات التقنية ولا Sentry ولا التحليلات ولا أي نموذج ذكاء اصطناعي (SEC-12، ADR-009 §4 و§7).

## الأعمدة

| Column | Subject | Action | Retention | Source |
| --- | --- | --- | --- | --- |
| `users.name` | guardian / teacher / staff | pseudonymize | account life | D-14 |
| `users.email` | guardian / teacher / staff | pseudonymize | account life | D-14 |
| `users.phone_e164` | guardian / teacher / staff | erase | account life | D-14 |
| `password_reset_tokens.email` | adult | delete_row | 60 minutes | SEC-01 |
| `auth_sessions.ip` | adult | erase | 12 months (technical logs) | F17:49 |
| `auth_sessions.user_agent` | adult | erase | 12 months (technical logs) | F17:49 |
| `audit_log_personal_data.ip` | adult | delete_row | audit period, at least 2 years; earlier on an erasure request | SEC-11, F17:50, D-14 |
| `audit_log_personal_data.user_agent` | adult | delete_row | as above | SEC-11, D-14 |
| `audit_log_personal_data.personal_changes` | any | delete_row | as above | SEC-11, D-14 |
| `files.path` | any (owner decides) | delete_file | by owner: homework 12 months after the student leaves; recordings 30 / 90 days; transfer proofs and invoices per accounting law; teacher documents not stated | F17:44-47 |
| `files.original_name` | any | erase | with the file | D-14 |
| `webhook_calls.payload` | guardian | delete_row | 12 months (technical logs) | F17:49 |
| `family_invitations.email` | guardian | delete_row | until accepted or expired | ONB-12 |
| `students.first_name` | child | pseudonymize | account life | PRV-04, D-14 |
| `students.first_name_normalized` | child | erase | account life | D-09, D-14 |
| `students.birth_month` | child | erase | account life | PRV-04, D-14 |
| `students.birth_year` | child | erase | account life | PRV-04, D-14 |
| `student_profiles.primary_language` | child | erase | account life | ONB-03 |
| `student_profiles.self_assessment` | child | erase | account life | ONB-03 |
| `student_profiles.notes` | child | erase | account life | ONB-04 |
| `family_notes.body` | free text | delete_row | account life (period not stated) | ADM-02 |
| `consents.ip` | guardian / teacher | retain_until_period | account life + 3 years | D-15 |
| `consents.user_agent` | guardian / teacher | retain_until_period | account life + 3 years | D-15 |
| `cookie_consents.visitor_ref` | visitor | retain_until_period | cookie lifetime, 12 months (proposed; not stated) | PUB-10 |
| `leads.name` | lead | delete_row | 12 months without a subscription | F17:48 |
| `leads.email` | lead | delete_row | 12 months without a subscription | F17:48 |
| `leads.phone_e164` | lead | delete_row | 12 months without a subscription | F17:48 |
| `leads.message` | lead | delete_row | 12 months without a subscription | F17:48 |
| `lead_events.body` | lead | delete_row | with the lead | F17:48 |
| `follow_up_tasks.body` | free text | erase | with the family or lead | ADM-02 |
| `safeguarding_incidents.description` | child | retain_until_period | 7 years | D-15 |
| `safeguarding_incident_actions.action_taken` | child | retain_until_period | 7 years | D-15 |
| `quiz_attempts.answers` | child | erase | 3 years after the student leaves (grades) | F17:46 |
| `assessments.recommendation` | child | erase | 3 years after the student leaves | F17:46 |
| `class_sessions.teacher_notes` | free text | erase | 3 years after the class ends (proposed, as reports) | F17:46 |
| `absence_requests.reason` | guardian / child | erase | with attendance; the text is erased on request, the row stays | spec/14, D-14 |
| `session_chat_messages.body` | free text | delete_row | 12 months | D-15 |
| `submissions.text_body` | child | erase | 12 months after the student leaves | F17:45 |
| `feedback.note` | child | erase | 3 years after the student leaves (grades) | F17:46 |
| `report_items.body` | child | erase | 3 years after the student leaves, then anonymized | F17:46 |
| `notifications.data` | guardian / child | delete_row | 12 months (proposed; not stated) | NOT-03 |
| `support_messages.body` | free text | delete_row | 12 months after the thread closes | D-15 |
| `payment_methods.provider_token_ref` | guardian | delete_row | until revoked; the token is revoked at the gateway first | BIL-05 |
| `payment_methods.last4` | guardian | delete_row | with the method | BIL-05 |
| `payment_methods.fingerprint` | guardian | delete_row | with the method | D-09 |
| `invoices.customer_snapshot` | guardian | retain_until_period | Jordanian law and accounting rules, restricted archive | F17:47 |
| `teachers.qualifications` | teacher | erase | employment (period after leaving not stated) | TOP-02 |
| `teacher_bank_accounts.account_holder_name` | teacher | delete_row | until the last payout after leaving (period not stated) | SEC-08 |
| `teacher_bank_accounts.iban` | teacher | delete_row | as above | SEC-08 |
| `teacher_bank_accounts.cliq_alias` | teacher | delete_row | as above | SEC-08 |
| `teacher_applications.applicant_name` | applicant | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_applications.email` | applicant | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_applications.phone_e164` | applicant | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_applications.cover_note` | applicant | delete_row | not stated; TBD(WP-2.6) | PUB-09 |
| `teacher_interviews.recommendation` | applicant | delete_row | with the application | TOP-01 |
| `observations.notes` | teacher | erase | not stated | TOP-01 |

## جدول الاحتفاظ حسب نوع البيانات

لمهمة الاحتفاظ الأسبوعية (spec/15). المصدر جدول الاحتفاظ في `spec/17` (F17:42-50) وافتراضات D-15 (تحتاج مراجعة قانونية).

| نوع البيانات | الجداول | المدة | بعدها | المصدر |
| --- | --- | --- | --- | --- |
| تسجيلات الحصص | `recordings`، ملفاتها | 30 يوماً للأسر، 90 للإدارة | حذف نهائي، ومن المزود خلال 24 ساعة | F17:44، D-16 |
| ملفات الواجبات والتسجيلات الصوتية | `submission_files`، `files`، `submissions.text_body` | 12 شهراً بعد مغادرة الطالب (`students.departed_at`) | حذف نهائي | F17:45 |
| التقارير والدرجات | `reports`، `report_items`، `feedback`، `quiz_attempts`، `assessments` | 3 سنوات بعد مغادرة الطالب | إخفاء الهوية (تبقى الصفوف، spec/14) | F17:46 |
| السجلات المالية | `payments`، `invoices`، `refunds`، `wallet_entries`، `credit_ledger_entries`، `compensation_*` | حسب القانون الأردني | أرشيف مقيد الوصول | F17:47 |
| العملاء المحتملون دون اشتراك | `leads`، `lead_events`، `waitlist_entries` للزوار | 12 شهراً | حذف | F17:48 |
| السجلات التقنية | `auth_sessions`، `webhook_calls`، `delivery_logs`، `join_token_log` | 12 شهراً | حذف | F17:49 |
| سجل التدقيق | `audit_logs`، `audit_log_personal_data` | سنتان على الأقل | أرشيف | F17:50 |
| دردشة الحصة | `session_chat_messages` | 12 شهراً | حذف | D-15 |
| أحداث الدخول والخروج | `participant_events` | 12 شهراً | حذف | D-15 |
| الموافقات | `consents` | عمر الحساب + 3 سنوات | حذف | D-15 |
| بلاغات الحماية | `safeguarding_incidents`، `safeguarding_incident_actions` | 7 سنوات | حذف | D-15 |
| محادثات الدعم | `support_threads`، `support_messages` | 12 شهراً بعد الإغلاق | حذف | D-15 |

مدد غير منصوصة، تذهب إلى ملف المستشار القانوني (WP-0.8): بيانات المتقدمات للتوظيف، ووثائق المعلمات وبياناتها البنكية بعد انتهاء العمل، وملاحظات CRM، والإشعارات داخل التطبيق، وموافقات ملفات تعريف الارتباط، وعنوان الجهاز في سجل التدقيق عند طلب الحذف قبل انقضاء السنتين. المقترح في الجدول أعلاه افتراضي حتى يُحسم.

## شكل `config/privacy.php` (لـ WP-1.5)

يُولَّد من الجدول أعلاه، أو يُكتب ويُختبر ضده: اختبار في WP-1.5 يقارن الملف بهذا السجل.

```php
return [
    // Erasure request (PRV-07, PAR-13): table => [column => action]. Order matters: files before rows.
    'erasure' => [
        'files' => ['path' => 'delete_file', 'original_name' => 'erase'],
        'users' => ['name' => 'pseudonymize', 'email' => 'pseudonymize', 'phone_e164' => 'erase'],
        'students' => [
            'first_name' => 'pseudonymize',
            'first_name_normalized' => 'erase',
            'birth_month' => 'erase',
            'birth_year' => 'erase',
        ],
        'student_profiles' => ['primary_language' => 'erase', 'self_assessment' => 'erase', 'notes' => 'erase'],
        'audit_log_personal_data' => ['*' => 'delete_row'],
        'consents' => ['ip' => 'retain_until_period', 'user_agent' => 'retain_until_period'],
        // … one entry per row of the registry
    ],
    // Weekly retention job (spec/15): what to delete or anonymize, and when.
    'retention' => [
        'recordings' => ['clock' => 'available_at', 'days' => ['family' => 30, 'admin' => 90], 'action' => 'delete_file', 'source' => 'F17:44'],
        'submission_files' => ['clock' => 'students.departed_at', 'months' => 12, 'action' => 'delete_file', 'source' => 'F17:45'],
        'report_items' => ['clock' => 'students.departed_at', 'years' => 3, 'action' => 'erase', 'source' => 'F17:46'],
        'participant_events' => ['clock' => 'occurred_at', 'months' => 12, 'action' => 'delete_row', 'source' => 'D-15'],
        'safeguarding_incidents' => ['clock' => 'closed_at', 'years' => 7, 'action' => 'delete_row', 'source' => 'D-15'],
        // … one entry per row of the retention table
    ],
];
```

المدد في الملف تُقرأ من الإعدادات بإصداراتها حين تكون مفاتيح فيها (مثل `recordings.family_days`)، ولا تُكتب أرقاماً ثابتة (الدستور م5).
