# المسرد: المصطلح ← الاسم في الكود

يوحّد هذا المسرد الأسماء بين المواصفات العربية والكود. الأسماء في عمود «الكود» إلزامية. عند تعارض اسم جدول في المواصفات مع اسم هنا، المرجع هو هذا الملف و`docs/02-architecture/data-model-conventions.md` (السبب مذكور في الملاحظات).

| المصطلح | English | النموذج (Model) | الجدول | الوحدة | ملاحظات |
| --- | --- | --- | --- | --- | --- |
| الأسرة | Family | `Family` | `families` | Families | وحدة الفوترة والصلاحية |
| ولي الأمر | Guardian | `Guardian` | `guardians` | Families | صاحب الحساب؛ مستخدم `users` |
| الطالب / الطفل | Student | `Student` | `students` | Families | حساب مُدار بلا بريد ولا كلمة مرور |
| ملف الطالب | Student profile | `StudentProfile` | `student_profiles` | Families | |
| رمز الطفل | Child PIN | `StudentPin` | `student_pins` | Identity | مخزّن مشفراً (hash) |
| رمز دخول لمرة واحدة | Student login code | `StudentLoginCode` | `student_login_codes` | Identity | STU-01؛ رمز أو QR من بوابة ولي الأمر (WP-0.7) |
| جهاز مفوّض | Authorized device | `DeviceAuthorization` | `device_authorizations` | Identity | |
| عميل محتمل | Lead | `Lead` | `leads` | Families | |
| قائمة الانتظار | Waitlist entry | `WaitlistEntry` | `waitlist_entries` | Scheduling | |
| البرنامج / المرحلة / المستوى | Program / Stage / Level | `Program` / `Stage` / `Level` | `programs` / `stages` / `levels` | Curriculum | |
| الوحدة الدراسية | Curriculum unit | `CurriculumUnit` | `curriculum_units` | Curriculum | اسمها في المواصفات `modules`؛ غُيّر لتجنب الخلط مع وحدات الكود |
| الدرس / خطة الدرس | Lesson / Lesson plan | `Lesson` / `LessonPlan` | `lessons` / `lesson_plans` | Curriculum | |
| عنصر محتوى | Content item | `ContentItem` | `content_items` | Curriculum | |
| نتاج تعلم | Learning outcome | `LearningOutcome` | `learning_outcomes` | Curriculum | CR-002؛ رمز ثابت مثل `AR-L2-R-03` |
| ربط بنتاج | Outcome alignment | `OutcomeAlignment` | `outcome_alignments` | Curriculum | OUT-02 (D-26) |
| بند اختبار المستوى | Placement quiz item | `PlacementQuizItem` | `placement_quiz_items` | Curriculum | ASM-01 (WP-0.7) |
| الشعبة | Class (group) | `ClassGroup` | `classes` | Scheduling | `Class` كلمة محجوزة في PHP |
| قاعدة جدول الشعبة | Schedule rule | `ClassScheduleRule` | `class_schedule_rules` | Scheduling | ساعة محلية + منطقة IANA |
| تسجيل في شعبة | Enrollment | `ClassEnrollment` | `class_enrollments` | Scheduling | `kind`: regular, trial (D-08) |
| الجلسة / الحصة | Class session | `ClassSession` | `class_sessions` | Scheduling | اسمها في المواصفات `sessions`؛ غُيّر لأن Laravel يستخدم `sessions` لجلسات الويب |
| استثناء تقويم / عطلة | Calendar exception | `CalendarException` | `calendar_exceptions` | Scheduling | `status`: tentative, confirmed (D-12) |
| توفر المعلمة / إجازة | Availability / Leave | `TeacherAvailability` / `TeacherLeave` | `teacher_availability` / `teacher_leaves` | Scheduling | |
| طلب تغيير موعد | Change request | `ScheduleChangeRequest` | `change_requests` | Scheduling | |
| الغرفة الافتراضية | Live room | `LiveRoom` | `live_rooms` | Live | |
| حدث مشارك | Participant event | `ParticipantEvent` | `participant_events` | Live | |
| التسجيل المرئي | Recording | `Recording` | `recordings` | Live | |
| دردشة الحصة | Session chat message | `SessionChatMessage` | `session_chat_messages` | Live | CLS-07؛ عامة فقط (WP-0.7) |
| حضور الجلسة | Session attendance | `SessionAttendance` | `session_attendance` | Ledger | الحالات: PRESENT, LATE, PARTIAL, ABSENT_EXCUSED, ABSENT_UNEXCUSED |
| طلب غياب | Absence request | `AbsenceRequest` | `absence_requests` | Ledger | |
| قيد الدفتر | Ledger entry | `CreditLedgerEntry` | `credit_ledger_entries` | Ledger | للإضافة فقط |
| دفعة رصيد | Credit lot | `CreditLot` | `credit_lots` | Ledger | D-05؛ جدول مشتق |
| إصدار قاعدة | Rule version | `RuleVersion` | `rule_versions` | Platform | لقطة للإعدادات المنتجة للقيد |
| استثناء حضور | Attendance exception | `AttendanceException` | `attendance_exceptions` | Ledger | |
| الخطة / السعر | Plan / Price | `Plan` / `Price` | `plans` / `prices` | Billing | |
| قاعدة خصم / قسيمة | Discount rule / Coupon | `DiscountRule` / `Coupon` | `discount_rules` / `coupons` | Billing | |
| الاشتراك | Subscription | `Subscription` | `subscriptions` | Billing | 9 حالات (state-machines.md) |
| الدفعة | Payment | `Payment` | `payments` | Billing | `provider_ref` فريد |
| الفاتورة | Invoice | `Invoice` | `invoices` | Billing | ترقيم متسلسل للفواتير فقط |
| الاسترداد | Refund | `Refund` | `refunds` | Billing | |
| المحفظة | Wallet entry | `WalletEntry` | `wallet_entries` | Billing | للإضافة فقط |
| الواجب / التسليم | Assignment / Submission | `Assignment` / `Submission` | `assignments` / `submissions` | Learning | |
| التقرير | Report | `Report` | `reports` | Learning | |
| المعلمة | Teacher | `Teacher` | `teachers` | Teachers | |
| مستوى مؤهلة له | Teacher level | `TeacherLevel` | `teacher_levels` | Teachers | TOP-02، SCH-11 (WP-0.7) |
| حساب بنكي للمعلمة | Teacher bank account | `TeacherBankAccount` | `teacher_bank_accounts` | Teachers | مشفّر (SEC-08، WP-0.7) |
| مقابلة توظيف | Teacher interview | `TeacherInterview` | `teacher_interviews` | Teachers | TOP-01 (WP-0.7) |
| بند مستحقات / كشف | Compensation line / statement | `CompensationLine` / `CompensationStatement` | `compensation_lines` / `compensation_statements` | Teachers | بالفلس (JOD × 1000) |
| قالب رسالة | Message template | `MessageTemplate` | `message_templates` | Messaging | |
| إصدار قالب | Message template version | `MessageTemplateVersion` | `message_template_versions` | Messaging | NOT-07 (WP-0.7) |
| عنوان موقوف | Suppressed recipient | `SuppressedRecipient` | `suppressed_recipients` | Messaging | NOT-09؛ العنوان مجزأ (WP-0.7) |
| إشعار داخل التطبيق | In-app notification | Laravel `DatabaseNotification` | `notifications` | Messaging | جدول Laravel القياسي |
| سجل التسليم | Delivery log | `DeliveryLog` | `delivery_logs` | Messaging | |
| تذكرة دعم / رسالة فيها | Support thread / message | `SupportThread` / `SupportMessage` | `support_threads` / `support_messages` | Messaging | `messages` في المواصفات صار `support_messages` (WP-0.7) |
| الإعداد / إصداره | Setting / version | `Setting` / `SettingVersion` | `settings` / `setting_versions` | Platform | |
| سجل التدقيق | Audit log | `AuditLog` | `audit_logs` | Platform | للإضافة فقط |
| الجزء الشخصي من التدقيق | Audit personal data | `AuditLogPersonalData` | `audit_log_personal_data` | Platform | قابل للمحو (D-14، WP-0.7) |
| وسم | Tag | `Tag` | `tags` / `taggables` | Platform | ADM-02، CNT-07 (WP-0.7) |
| حدث صادر | Outbox event | `OutboxEvent` | `outbox_events` | Platform | |
| بلاغ حماية | Safeguarding incident | `SafeguardingIncident` | `safeguarding_incidents` | Families | وصول مقيد |
| خطوة في البلاغ | Safeguarding incident action | `SafeguardingIncidentAction` | `safeguarding_incident_actions` | Families | للإضافة فقط (SAFE-02، WP-0.7) |
| دعوة أسرة | Family invitation | `FamilyInvitation` | `family_invitations` | Families | ONB-12 (WP-0.7) |
| موافقة ملفات الارتباط | Cookie consent | `CookieConsent` | `cookie_consents` | Families | PUB-10؛ للزائر بلا حساب (WP-0.7) |
| وثيقة قانونية / موافقة | Legal document / Consent | `LegalDocumentVersion` / `Consent` | `legal_documents` / `consents` | Families | الموافقة لا تُعدَّل |
| طلب بيانات | Data request | `DataRequest` | `data_requests` | Families | PRV-07 |
| التجربة | Trial | `Trial` | `trials` | Curriculum (Assessment) | |
| تحديد المستوى | Placement | `Placement` | `placements` | Curriculum (Assessment) | |

## مصطلحات العمليات

| المصطلح | المعنى المعتمد |
| --- | --- |
| حزمة عمل (WP) | وحدة تنفيذ لها معرّف ونطاق ومعايير قبول في `docs/03-plan/phase-*.md` |
| بوابة (ب0..ب5) | نقطة قرار في خطة الإصدارات بأسبوع محدد |
| التجربة الميدانية | تشغيل حقيقي مع أسر ومعلمات (الأسابيع 9–30) لقياس أرقام بوابة التحقق |
| وضع الظل | تشغيل دفتر الرصيد بالتوازي مع سجل العمليات اليدوي للمقارنة دون الاعتماد عليه |
| نقطة تمديد | حقل أو جدول يُضاف مبكراً لمنع إعادة عمل لميزة لاحقة، دون واجهة أو منطق |
