# المسرد: المصطلح ← الاسم في الكود

يوحّد هذا المسرد الأسماء بين المواصفات العربية والكود. الأسماء في عمود «الكود» إلزامية. عند تعارض اسم جدول في المواصفات مع اسم هنا، المرجع هو هذا الملف و`docs/02-architecture/data-model-conventions.md` (السبب مذكور في الملاحظات).

| المصطلح | English | النموذج (Model) | الجدول | الوحدة | ملاحظات |
| --- | --- | --- | --- | --- | --- |
| الأسرة | Family | `Family` | `families` | Families | وحدة الفوترة والصلاحية |
| ولي الأمر | Guardian | `Guardian` | `guardians` | Families | صاحب الحساب؛ مستخدم `users` |
| الطالب / الطفل | Student | `Student` | `students` | Families | حساب مُدار بلا بريد ولا كلمة مرور |
| ملف الطالب | Student profile | `StudentProfile` | `student_profiles` | Families | |
| رمز الطفل | Child PIN | `StudentPin` | `student_pins` | Identity | مخزّن مشفراً (hash) |
| جهاز مفوّض | Authorized device | `DeviceAuthorization` | `device_authorizations` | Identity | |
| عميل محتمل | Lead | `Lead` | `leads` | Families | |
| قائمة الانتظار | Waitlist entry | `WaitlistEntry` | `waitlist_entries` | Scheduling | |
| البرنامج / المرحلة / المستوى | Program / Stage / Level | `Program` / `Stage` / `Level` | `programs` / `stages` / `levels` | Curriculum | |
| الوحدة الدراسية | Curriculum unit | `CurriculumUnit` | `curriculum_units` | Curriculum | اسمها في المواصفات `modules`؛ غُيّر لتجنب الخلط مع وحدات الكود |
| الدرس / خطة الدرس | Lesson / Lesson plan | `Lesson` / `LessonPlan` | `lessons` / `lesson_plans` | Curriculum | |
| عنصر محتوى | Content item | `ContentItem` | `content_items` | Curriculum | |
| نتاج تعلم | Learning outcome | `LearningOutcome` | `learning_outcomes` | Curriculum | CR-002؛ رمز ثابت مثل `AR-L2-R-03` |
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
| بند مستحقات / كشف | Compensation line / statement | `CompensationLine` / `CompensationStatement` | `compensation_lines` / `compensation_statements` | Teachers | بالفلس (JOD × 1000) |
| قالب رسالة | Message template | `MessageTemplate` | `message_templates` | Messaging | |
| إشعار داخل التطبيق | In-app notification | Laravel `DatabaseNotification` | `notifications` | Messaging | جدول Laravel القياسي |
| سجل التسليم | Delivery log | `DeliveryLog` | `delivery_logs` | Messaging | |
| تذكرة دعم | Support thread | `SupportThread` | `support_threads` | Messaging | |
| الإعداد / إصداره | Setting / version | `Setting` / `SettingVersion` | `settings` / `setting_versions` | Platform | |
| سجل التدقيق | Audit log | `AuditLog` | `audit_logs` | Platform | للإضافة فقط |
| حدث صادر | Outbox event | `OutboxEvent` | `outbox_events` | Platform | |
| بلاغ حماية | Safeguarding incident | `SafeguardingIncident` | `safeguarding_incidents` | Families | وصول مقيد |
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
