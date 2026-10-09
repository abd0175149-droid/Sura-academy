# مصفوفة التتبع

يربط كل متطلب بحزمة الخلفية (المنطق والبيانات والـ API) وحزمة التجربة (الواجهة أو التحقق النهائي). الحالة يحدّثها Claude عند إغلاق الحزمة: `—` لم يبدأ · `In progress` · `Implemented` · `Verified` (بعد ب3).

`php scripts/check-traceability.php` يقرأ هذا الجدول. لا تغيّر ترتيب الأعمدة.

المجموع: 253 متطلباً — R1: 210 · R2: 38 · R3: 5.

## المتطلبات

| المعرّف | الأولوية | الميزة | حزمة الخلفية | حزمة التجربة | ملف المواصفات | الحالة |
| --- | --- | --- | --- | --- | --- | --- |
| ADM-01 | R1 | لوحة العمليات الحية | WP-4.1 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-02 | R1 | إدارة علاقات الأسر | WP-1.5 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-03 | R1 | الطلاب | WP-1.5 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-04 | R1 | المعلمات | WP-2.6 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-05 | R1 | الشعب والجدولة | WP-2.2 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-06 | R1 | التقييمات والتجارب | WP-2.7 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-07 | R1 | المنهج والمحتوى | WP-2.1 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-08 | R1 | المالية | WP-2.5 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-09 | R1 | الرصيد والحضور | WP-2.3 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-10 | R1 | مراجعة التقارير | WP-4.6 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-11 | R1 | الاتصالات | WP-4.8 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-12 | R1 | الإعدادات | WP-1.4 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-13 | R1 | سجل التدقيق | WP-1.3 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-14 | R1 | سجل حماية الطفل | WP-1.5 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-15 | R1 | مفاتيح الميزات | WP-1.4 | WP-4.1 | `12-admin-analytics-kpis.md` | — |
| ADM-16 | R2 | تحليل الأفواج | WP-6.4 | WP-6.4 | `12-admin-analytics-kpis.md` | — |
| ASM-01 | R1 | اختبار المستوى الأولي | WP-2.7 | WP-4.4 | `07-content-assessment-homework-reports.md` | — |
| ASM-02 | R1 | نموذج المقابلة الشفوية | WP-2.7 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| ASM-03 | R1 | قرار التسكين | WP-2.7 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| ASM-04 | R2 | منشئ الاختبارات | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| ASM-05 | R2 | المحاولات والتصحيح | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| ASM-06 | R2 | حجب النتيجة | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| ASM-07 | R2 | بنك الأسئلة | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| ASM-08 | R2 | شهادة المستوى | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| ATT-01 | R1 | اقتراح الحالة آلياً من أحداث الدخول والخروج، وتأكيد المعلمة خلال 12 سا | WP-2.3 | WP-4.2 | `09-attendance-ledger-rules.md` | — |
| ATT-02 | R1 | المهلة تُحسب بين وقت الطلب ووقت البدء بتوقيت UTC؛ 24 ساعة بالضبط تُعد  | WP-2.3 | WP-4.3 | `09-attendance-ledger-rules.md` | — |
| ATT-03 | R1 | سقف الغياب المبرر: حصتان لكل 8 حصص (إعداد) | WP-2.3 | WP-4.3 | `09-attendance-ledger-rules.md` | — |
| ATT-04 | R1 | كل قيد يحفظ إصدار القاعدة التي أنتجته، فتغيير الإعدادات لا يغير الماضي | WP-2.3 | — | `09-attendance-ledger-rules.md` | — |
| ATT-05 | R1 | بعد قفل الحضور، أي تعديل يحتاج المشرف وسبباً، ويُنفذ بقيد عكسي جديد | WP-2.3 | WP-4.1 | `09-attendance-ledger-rules.md` | — |
| ATT-06 | R1 | قائمة استثناءات للعمليات: نزاعات، بيانات متعارضة، تأكيد متأخر | WP-2.3 | WP-4.1 | `09-attendance-ledger-rules.md` | — |
| ATT-07 | R1 | الرصيد المعروض = مجموع القيود لكل طالب ولكل نوع رصيد، مع لقطة محسوبة ل | WP-2.3 | WP-4.3 | `09-attendance-ledger-rules.md` | — |
| ATT-08 | R1 | معالجة كل جلسة مرة واحدة فقط، ولو وصل حدث الانتهاء مرتين | WP-2.3 | — | `09-attendance-ledger-rules.md` | — |
| ATT-09 | R1 | جلسات التجربة نوع مستقل لا يدخل في الرصيد المدفوع ولا في مستحقات الدور | WP-2.3 | — | `09-attendance-ledger-rules.md` | — |
| BIL-01 | R1 | الخطط | WP-2.4 | WP-4.1 | `10-billing-and-payments.md` | — |
| BIL-02 | R1 | سجل الأسعار | WP-2.4 | WP-4.1 | `10-billing-and-payments.md` | — |
| BIL-03 | R1 | الخصم العائلي | WP-2.4 | WP-4.1 | `10-billing-and-payments.md` | — |
| BIL-04 | R1 | القسائم | WP-2.4 | WP-4.1 | `10-billing-and-payments.md` | — |
| BIL-05 | R1 | صفحة الدفع | WP-2.5 | WP-4.4 | `10-billing-and-payments.md` | — |
| BIL-06 | R1 | حالات الاشتراك | WP-2.4 | WP-4.3 | `10-billing-and-payments.md` | — |
| BIL-07 | R1 | التجديد المتجدد | WP-2.5 | WP-4.3 | `10-billing-and-payments.md` | — |
| BIL-08 | R1 | تحصيل الدفعات الفاشلة | WP-2.5 | WP-4.3 | `10-billing-and-payments.md` | — |
| BIL-09 | R1 | التحويل اليدوي | WP-2.5 | WP-4.1 | `10-billing-and-payments.md` | — |
| BIL-10 | R1 | توزيع الدفعة | WP-2.4 | WP-4.4 | `10-billing-and-payments.md` | — |
| BIL-11 | R1 | الفواتير والإيصالات | WP-2.4 | WP-4.3 | `10-billing-and-payments.md` | — |
| BIL-12 | R1 | الاسترداد | WP-2.5 | WP-4.1 | `10-billing-and-payments.md` | — |
| BIL-13 | R1 | الرصيد المالي للأسرة | WP-2.4 | WP-4.3 | `10-billing-and-payments.md` | — |
| BIL-14 | R1 | الإلغاء والتجميد الذاتيان | WP-2.4 | WP-4.3 | `10-billing-and-payments.md` | — |
| BIL-15 | R1 | طبقة تجريد الدفع | WP-2.5 | — | `10-billing-and-payments.md` | — |
| BIL-16 | R1 | أحداث البوابة | WP-2.5 | — | `10-billing-and-payments.md` | — |
| BIL-17 | R2 | التسوية | WP-6.4 | WP-6.4 | `10-billing-and-payments.md` | — |
| BIL-18 | R2 | الضرائب حسب بلد العميل | WP-6.4 | WP-6.4 | `10-billing-and-payments.md` | — |
| BIL-19 | R3 | عملات متعددة للعرض والدفع | WP-7.1 | WP-7.1 | `10-billing-and-payments.md` | — |
| CLS-01 | R1 | فيديو وصوت مدمجان | WP-3.1 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-02 | R1 | نافذة الدخول | WP-3.1 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-03 | R1 | رمز دخول من الخادم | WP-3.1 | — | `05-live-classroom.md` | — |
| CLS-04 | R1 | الأدوار داخل الغرفة | WP-3.1 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-05 | R1 | منع الغرباء | WP-3.1 | — | `05-live-classroom.md` | — |
| CLS-06 | R1 | مشاركة الشاشة والصوت | WP-3.1 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-07 | R1 | الدردشة داخل الحصة | WP-3.2 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-08 | R1 | رفع اليد والتفاعلات وكتم الذات | — | WP-3.4 | `05-live-classroom.md` | — |
| CLS-09 | R1 | الحضور الآلي | WP-3.2 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-10 | R1 | التسجيل السحابي | WP-3.3 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-11 | R1 | مساحة عمل المعلمة | WP-3.2 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-12 | R1 | زر البلاغ الطارئ | WP-3.2 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-13 | R1 | دخول البديلة والمشرف | WP-3.1 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-14 | R1 | وضع الاتصال الضعيف | — | WP-3.4 | `05-live-classroom.md` | — |
| CLS-15 | R1 | رابط احتياطي | WP-3.1 | WP-3.4 | `05-live-classroom.md` | — |
| CLS-16 | R2 | السبورة والكتابة على المعروض | WP-6.2 | WP-6.2 | `05-live-classroom.md` | — |
| CLS-17 | R2 | عارض شرائح داخلي | WP-6.2 | WP-6.2 | `05-live-classroom.md` | — |
| CLS-18 | R2 | مؤقت الأنشطة | WP-6.2 | WP-6.2 | `05-live-classroom.md` | — |
| CLS-19 | R2 | نجوم داخل الحصة | WP-6.2 | WP-6.2 | `05-live-classroom.md` | — |
| CLS-20 | R2 | علامة مائية في التسجيلات | WP-6.5 | WP-6.5 | `05-live-classroom.md` | — |
| CNT-01 | R1 | الهرم | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-02 | R1 | أنواع العناصر | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-03 | R1 | الظهور | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-04 | R1 | خطة الدرس | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-05 | R1 | موارد المعلمة | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-06 | R1 | مسودة ونشر وأرشفة | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-07 | R1 | مكتبة الوسائط | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-08 | R1 | الطباعة العربية | WP-1.6 | WP-4.5 | `07-content-assessment-homework-reports.md` | — |
| CNT-09 | R1 | إطار المستويات | WP-2.1 | WP-4.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-10 | R2 | الإصدارات | WP-6.3 | WP-6.3 | `07-content-assessment-homework-reports.md` | — |
| CNT-11 | R2 | رموز QR | WP-6.3 | WP-6.3 | `07-content-assessment-homework-reports.md` | — |
| CNT-12 | R2 | أنشطة تفاعلية | WP-6.2 | WP-6.2 | `07-content-assessment-homework-reports.md` | — |
| CNT-13 | R2 | كراسة المستوى | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| CNT-14 | R3 | قراءة آلية بالعربية | WP-7.1 | WP-7.1 | `07-content-assessment-homework-reports.md` | — |
| HW-01 | R1 | منشئ الواجب | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| HW-02 | R1 | أنواع التسليم | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| HW-03 | R1 | إعادة التسليم | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| HW-04 | R1 | التصحيح | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| HW-05 | R1 | التأخير والتذكير | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| HW-06 | R1 | إزالة بيانات الصور | WP-1.4 | — | `07-content-assessment-homework-reports.md` | — |
| MSG-01 | R1 | تذاكر الدعم | WP-4.8 | WP-4.1 | `11-notifications-messaging-support.md` | — |
| MSG-02 | R1 | مستويات الخدمة | WP-4.8 | WP-4.1 | `11-notifications-messaging-support.md` | — |
| MSG-03 | R1 | إعلانات للطلاب | WP-4.8 | WP-4.5 | `11-notifications-messaging-support.md` | — |
| MSG-04 | R2 | تواصل ولي الأمر والمعلمة | WP-6.5 | WP-6.5 | `11-notifications-messaging-support.md` | — |
| MSG-05 | R2 | مركز مساعدة | WP-6.5 | WP-6.5 | `11-notifications-messaging-support.md` | — |
| NOT-01 | R1 | محرك الإشعارات | WP-1.4 | WP-4.8 | `11-notifications-messaging-support.md` | — |
| NOT-02 | R1 | البريد الإلكتروني | WP-1.4 | WP-4.8 | `11-notifications-messaging-support.md` | — |
| NOT-03 | R1 | صندوق داخل التطبيق | WP-1.4 | WP-4.8 | `11-notifications-messaging-support.md` | — |
| NOT-04 | R1 | الرسائل النصية | WP-4.8 | — | `11-notifications-messaging-support.md` | — |
| NOT-05 | R2 | واتساب للأعمال | WP-6.5 | WP-6.5 | `11-notifications-messaging-support.md` | — |
| NOT-06 | R2 | إشعارات المتصفح | WP-6.5 | WP-6.5 | `11-notifications-messaging-support.md` | — |
| NOT-07 | R1 | القوالب | WP-1.4 | WP-4.8 | `11-notifications-messaging-support.md` | — |
| NOT-08 | R1 | الرسائل الجماعية | WP-4.8 | WP-4.1 | `11-notifications-messaging-support.md` | — |
| NOT-09 | R1 | سجل التسليم | WP-1.4 | WP-4.8 | `11-notifications-messaging-support.md` | — |
| ONB-01 | R1 | حساب ولي الأمر | WP-1.2 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-02 | R1 | التحقق | WP-1.2 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-03 | R1 | إضافة طفل بأقل البيانات | WP-1.5 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-04 | R1 | تنبيه الملاحظات | WP-1.5 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-05 | R1 | اختبار تحديد مستوى أولي | WP-2.7 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-06 | R1 | اختيار المسار بعد الاختبار | WP-2.7 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-07 | R1 | حجز مقابلة التقييم | WP-2.7 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-08 | R1 | حجز المقعد التجريبي | WP-2.7 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-09 | R1 | أهلية التجربة | WP-2.7 | — | `02-public-site-and-onboarding.md` | — |
| ONB-10 | R1 | العرض والدفع بعد التجربة | WP-2.5 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-11 | R1 | متتبع التقدم | WP-2.7 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-12 | R1 | تسجيل بمساعدة الإدارة | WP-1.5 | WP-4.1 | `02-public-site-and-onboarding.md` | — |
| ONB-13 | R1 | تفضيلات المواعيد | WP-2.2 | WP-4.4 | `02-public-site-and-onboarding.md` | — |
| ONB-14 | R3 | الدخول عبر Google وApple | WP-7.1 | WP-7.1 | `02-public-site-and-onboarding.md` | — |
| PAR-01 | R1 | اللوحة الرئيسية | WP-4.3 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-02 | R1 | إدارة الأبناء | WP-1.5 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-03 | R1 | ملف الطفل | WP-1.5 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-04 | R1 | التقويم | WP-2.2 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-05 | R1 | الإبلاغ عن غياب | WP-2.3 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-06 | R1 | طلب تغيير موعد أو شعبة | WP-2.2 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-07 | R1 | الاشتراك والرصيد | WP-2.4 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-08 | R1 | المدفوعات والفواتير | WP-2.5 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-09 | R1 | الإلغاء والتجميد | WP-2.4 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-10 | R1 | التقارير | WP-4.6 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-11 | R1 | الرسائل والدعم | WP-4.8 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-12 | R1 | تفضيلات الإشعارات | WP-1.4 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-13 | R1 | مركز الخصوصية | WP-1.5 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-14 | R1 | وضع الطفل | WP-1.2 | WP-4.3 | `03-parent-portal.md` | — |
| PAR-15 | R2 | التجميد الذاتي | WP-6.5 | WP-6.5 | `03-parent-portal.md` | — |
| PAR-16 | R2 | رابط تقويم دائم | WP-6.5 | WP-6.5 | `03-parent-portal.md` | — |
| PAR-17 | R2 | ولي أمر ثانٍ | WP-6.5 | WP-6.5 | `03-parent-portal.md` | — |
| PAR-18 | R2 | برنامج الإحالة | WP-6.5 | WP-6.5 | `03-parent-portal.md` | — |
| PAR-19 | R2 | تعليقات على الواجبات | WP-6.5 | WP-6.5 | `03-parent-portal.md` | — |
| PAY-01 | R1 | وحدة الحساب | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| PAY-02 | R1 | الأجر الهجين | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| PAY-03 | R1 | العلاوة الليلية | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| PAY-04 | R1 | التغطية العاجلة | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| PAY-05 | R1 | التعديلات | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| PAY-06 | R1 احتساب وتصدير، R2 سير اعتماد كامل (جزء R2 في WP-6.4) | الدورة الشهرية | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| PAY-07 | R1 | استثناء السعر | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| PRV-01 | R1 | إشعار مباشر لولي الأمر قبل جمع بيانات الطفل، يسمي الأطراف الثالثة التي | WP-1.5 | WP-4.4 | `17-security-privacy-safeguarding.md` | — |
| PRV-02 | R1 | موافقة أبوية قابلة للتحقق بطريقة يعتمدها المستشار القانوني، مثل معاملة | WP-1.5 | WP-4.4 | `17-security-privacy-safeguarding.md` | — |
| PRV-03 | R1 | موافقة منفصلة لأي مشاركة لبيانات الطفل مع طرف ثالث لا تحتاجها الخدمة ن | WP-1.5 | WP-4.4 | `17-security-privacy-safeguarding.md` | — |
| PRV-04 | R1 | الحد الأدنى من بيانات الطفل: الاسم الأول، الشهر والسنة للميلاد، معلوما | WP-1.5 | WP-5.2 | `17-security-privacy-safeguarding.md` | — |
| PRV-05 | R1 | برنامج أمن معلومات مكتوب: منسق مسؤول، تقييم مخاطر سنوي، اختبار ومراقبة | WP-5.2 | WP-5.2 | `17-security-privacy-safeguarding.md` | — |
| PRV-06 | R1 | سياسة احتفاظ منشورة تذكر الغرض ومدة الحذف لكل نوع من بيانات الأطفال (ا | WP-5.2 | WP-5.2 | `17-security-privacy-safeguarding.md` | — |
| PRV-07 | R1 | حق ولي الأمر في الاطلاع والتصحيح والحذف ووقف الاستخدام، عبر طلب من الب | WP-1.5 | WP-4.3 | `17-security-privacy-safeguarding.md` | — |
| PRV-08 | R1 | اتفاقية معالجة بيانات مع كل مزود (الفيديو، البريد، الاستضافة، الدفع)،  | WP-5.2 | WP-5.2 | `17-security-privacy-safeguarding.md` | — |
| PRV-09 | R1 | تقييم أثر لحماية البيانات قبل الإطلاق، وتطبيق معايير تصميم خدمات الأطف | WP-5.2 | WP-5.2 | `17-security-privacy-safeguarding.md` | — |
| PRV-10 | R1 | لا إعلانات ولا أدوات تتبع تسويقية في أي صفحة يراها الطفل؛ أدوات التسوي | WP-1.6 | WP-5.2 | `17-security-privacy-safeguarding.md` | — |
| PUB-01 | R1 | الصفحة الرئيسية | — | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-02 | R1 | صفحات البرامج والمراحل | WP-2.1 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-03 | R1 | صفحة الأسعار | WP-2.4 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-04 | R1 | الجدول المنشور | WP-2.2 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-05 | R1 | السياسات القانونية | WP-1.5 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-06 | R1 | التواصل | WP-1.5 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-07 | R1 | اللغتان وتحسين البحث | — | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-08 | R1 | قائمة الانتظار وجمع العملاء المحتملين | WP-1.5 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-09 | R1 | صفحة التوظيف | WP-2.6 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-10 | R1 | موافقة ملفات الارتباط | WP-1.5 | WP-4.7 | `02-public-site-and-onboarding.md` | — |
| PUB-11 | R2 | المدونة والمقالات | WP-6.5 | WP-6.5 | `02-public-site-and-onboarding.md` | — |
| PUB-12 | R2 | صفحات الإحالة | WP-6.5 | WP-6.5 | `02-public-site-and-onboarding.md` | — |
| RBAC-01 | R1 | صلاحيات بمفاتيح مسماة مثل `attendance.mark` و`payments.refund` تُجمع ف | WP-1.3 | WP-4.1 | `01-components-roles-rbac.md` | — |
| RBAC-02 | R1 | كل استعلام يمر بسياسة نطاق: ولي الأمر بمعرّف أسرته، والمعلمة بالشعب ال | WP-1.3 | — | `01-components-roles-rbac.md` | — |
| RBAC-03 | R1 | لا زر في الواجهة دون صلاحية مقابلة في الخادم، مع اختبار آلي لكل دور عل | WP-1.3 | — | `01-components-roles-rbac.md` | — |
| RBAC-04 | R1 | الأفعال الحساسة (رصيد يدوي، استرداد، تعديل حضور مقفل، تغيير سعر) تطلب  | WP-1.3 | WP-4.1 | `01-components-roles-rbac.md` | — |
| RBAC-05 | R1 | مراجعة ربع سنوية لصلاحيات الموظفين وإلغاء فوري عند انتهاء العمل | WP-1.3 | WP-4.1 | `01-components-roles-rbac.md` | — |
| RBAC-06 | R2 | موافقة شخص ثانٍ على الاسترداد أو الرصيد اليدوي فوق حد تحدده الإعدادات | WP-6.5 | WP-6.5 | `01-components-roles-rbac.md` | — |
| RBAC-07 | R2 | «العرض كمستخدم» للدعم، للقراءة فقط وبسبب مكتوب ومسجل، ولا يشمل حسابات  | WP-6.5 | WP-6.5 | `01-components-roles-rbac.md` | — |
| RPT-01 | R1 | مؤشرات أسبوعية لكل طالب | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| RPT-02 | R1 | ملخص أسبوعي للشعبة | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| RPT-03 | R1 | تقرير شهري مفصل | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| RPT-04 | R1 | مهام التقارير | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| RPT-05 | R1 | المراجعة بالعينة | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| RPT-06 | R1 | النشر والاطلاع | WP-4.6 | WP-4.6 | `07-content-assessment-homework-reports.md` | — |
| RPT-07 | R2 | تقرير المستوى | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| RPT-08 | R2 | قوالب قابلة للتعديل | WP-6.1 | WP-6.1 | `07-content-assessment-homework-reports.md` | — |
| RPT-09 | R3 | مسودة بمساعدة الذكاء الاصطناعي | WP-7.1 | WP-7.1 | `07-content-assessment-homework-reports.md` | — |
| SAFE-01 | R1 | زر «الإبلاغ عن مخاوف» لولي الأمر والمعلمة، يصل مسؤول الحماية خلال 24 س | WP-1.5 | WP-4.5 | `17-security-privacy-safeguarding.md` | — |
| SAFE-02 | R1 | سجل بلاغات مقيد الوصول لمسؤول الحماية ومدير النظام، بكل خطوة متخذة | WP-1.5 | WP-4.1 | `17-security-privacy-safeguarding.md` | — |
| SAFE-03 | R1 | لا رسائل خاصة غير مراقبة بين بالغ وطفل، ولا مشاركة لبيانات الاتصال | WP-3.1 | WP-3.4 | `17-security-privacy-safeguarding.md` | — |
| SAFE-04 | R1 | المعلمة لا تُفعّل قبل رفع شهادة عدم المحكومية وإكمال تدريب حماية الطفل | WP-2.6 | WP-4.1 | `17-security-privacy-safeguarding.md` | — |
| SAFE-05 | R1 | زيارات إشرافية ظاهرة للحصص، ووصول المشرف للتسجيلات عند البلاغ فقط مع ت | WP-3.1 | WP-3.4 | `17-security-privacy-safeguarding.md` | — |
| SAFE-06 | R1 | إيقاف فوري لحساب المعلمة وحجب دخولها للحصص عند بلاغ جدي، مع إعادة إسنا | WP-2.6 | WP-4.1 | `17-security-privacy-safeguarding.md` | — |
| SCH-01 | R1 | كيان الشعبة | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-02 | R1 | حالات الشعبة | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-03 | R1 | الجدول المنشور | WP-2.2 | WP-4.7 | `08-scheduling-and-timezones.md` | — |
| SCH-04 | R1 | قائمة الانتظار | WP-2.2 | WP-4.7 | `08-scheduling-and-timezones.md` | — |
| SCH-05 | R1 | توليد الجلسات | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-06 | R1 | التوقيت الصيفي | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-07 | R1 | منع الخلط | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-08 | R1 | كشف التعارضات | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-09 | R1 | التأجيل والتغيير | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-10 | R1 | الإلغاء بسبب | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-11 | R1 | البحث عن بديلة | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-12 | R1 | تقويم العطل | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-13 | R1 | التقويمات | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-14 | R1 | النقل بين الشعب | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SCH-15 | R2 | اقتراح المطابقة | WP-6.4 | WP-6.4 | `08-scheduling-and-timezones.md` | — |
| SCH-16 | R1 | مدة الحصة حسب العمر | WP-2.2 | WP-4.1 | `08-scheduling-and-timezones.md` | — |
| SEC-01 | R1 | تخزين كلمات المرور بخوارزمية Argon2id، وحد أدنى 10 أحرف، ومنع كلمات ال | WP-1.2 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-02 | R1 | تحقق ثنائي (TOTP) إلزامي لكل أدوار الموظفين والمعلمات، واختياري لأوليا | WP-1.2 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-03 | R1 | جلسات بملفات ارتباط آمنة، تدوير عند الدخول، انتهاء بعد 30 دقيقة خمول ل | WP-1.2 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-04 | R1 | رمز PIN للطفل من 4 إلى 6 أرقام مخزن مشفراً، يعمل على جهاز فوّضه ولي ال | WP-1.2 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-05 | R1 | فحص الصلاحية ونطاق البيانات في الخادم لكل طلب، مع اختبارات آلية لكل دو | WP-1.3 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-06 | R1 | حماية من حقن SQL وXSS وCSRF وSSRF، وسياسة أمان محتوى (CSP) صارمة | WP-1.1 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-07 | R1 | حدود استخدام على الدخول ورموز التحقق والتسجيل واستعادة كلمة المرور وال | WP-1.2 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-08 | R1 | TLS 1.2 فأعلى مع HSTS، وتشفير البيانات المخزنة، وتشفير إضافي للحقول ال | WP-1.8 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-09 | R1 | الملفات المرفوعة: قائمة امتدادات مسموحة، حد للحجم، فحص فيروسات، حذف بي | WP-1.4 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-10 | R1 | التسجيلات: تخزين خاص، روابط عرض صالحة 10 دقائق، تعطيل التنزيل للأسر، ت | WP-3.3 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-11 | R1 | سجل تدقيق للإضافة فقط: الفاعل، الفعل، الكيان، القيمة قبل وبعد، العنوان | WP-1.3 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-12 | R1 | لا بيانات شخصية في السجلات: إخفاء البريد والهاتف، ولا أسماء أطفال في أ | WP-1.4 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-13 | R1 | فحص الاعتماديات آلياً، واختبار اختراق خارجي قبل الإطلاق ثم سنوياً | WP-5.1 | WP-5.1 | `17-security-privacy-safeguarding.md` | — |
| SEC-14 | R1 | خطة استجابة للحوادث: إيقاف حساب أو كل الجلسات فوراً، ومهل الإبلاغ حسب  | WP-5.5 | WP-5.5 | `17-security-privacy-safeguarding.md` | — |
| STU-01 | R1 | الدخول | WP-1.2 | WP-4.5 | `04-student-portal.md` | — |
| STU-02 | R1 | الشاشة الرئيسية | WP-4.5 | WP-4.5 | `04-student-portal.md` | — |
| STU-03 | R1 | فحص الجهاز | WP-3.4 | WP-3.4 | `04-student-portal.md` | — |
| STU-04 | R1 | دخول الحصة | WP-3.1 | WP-3.4 | `04-student-portal.md` | — |
| STU-05 | R1 | الدروس والمحتوى | WP-2.1 | WP-4.5 | `04-student-portal.md` | — |
| STU-06 | R1 | الواجبات | WP-4.6 | WP-4.5 | `04-student-portal.md` | — |
| STU-07 | R1 | لوحة مفاتيح عربية على الشاشة | — | WP-4.5 | `04-student-portal.md` | — |
| STU-08 | R1 | التسجيلات | WP-3.3 | WP-4.5 | `04-student-portal.md` | — |
| STU-09 | R1 | التقدم | WP-4.6 | WP-4.5 | `04-student-portal.md` | — |
| STU-10 | R1 | الإعلانات | WP-4.8 | WP-4.5 | `04-student-portal.md` | — |
| STU-11 | R1 | إمكانية الاستخدام للصغار | — | WP-4.5 | `04-student-portal.md` | — |
| STU-12 | R2 | الاختبارات القصيرة | WP-6.2 | WP-6.2 | `04-student-portal.md` | — |
| STU-13 | R2 | لوحة كتابة بخط اليد | WP-6.2 | WP-6.2 | `04-student-portal.md` | — |
| STU-14 | R3 | ألعاب ومكافآت متقدمة | WP-7.1 | WP-7.1 | `04-student-portal.md` | — |
| TCH-01 | R1 | لوحة اليوم | WP-4.2 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-02 | R1 | الجدول | WP-2.2 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-03 | R1 | صفحة الشعبة | WP-2.2 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-04 | R1 | مساحة الحصة | WP-3.2 | WP-3.4 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-05 | R1 | تأكيد الحضور | WP-2.3 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-06 | R1 | تقدم المنهج | WP-2.1 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-07 | R1 | الواجبات والتصحيح | WP-4.6 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-08 | R1 | التقارير المتدرجة | WP-4.6 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-09 | R1 | التوفر والإجازات | WP-2.2 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-10 | R1 | الإبلاغ عن غياب طارئ | WP-2.2 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-11 | R1 | طلب تغيير موعد | WP-2.2 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-12 | R1 | كشف المستحقات | WP-2.6 | WP-4.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-13 | R2 | ملاحظة صوتية على الواجب | WP-6.2 | WP-6.2 | `06-teacher-portal-ops-compensation.md` | — |
| TCH-14 | R2 | مكتبة التدريب | WP-6.5 | WP-6.5 | `06-teacher-portal-ops-compensation.md` | — |
| TOP-01 | R1 سجلات، R2 سير عمل كامل (جزء R2 في WP-6.4) | مسار التأهيل | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| TOP-02 | R1 | ملف المعلمة | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| TOP-03 | R1 | الإقرارات | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |
| TOP-04 | R2 | الجودة | WP-6.4 | WP-6.4 | `06-teacher-portal-ops-compensation.md` | — |
| TOP-05 | R1 | الإيقاف والإنهاء | WP-2.6 | WP-4.1 | `06-teacher-portal-ops-compensation.md` | — |

## قواعد العمل

| القاعدة | الحزمة |
| --- | --- |
| BR-001 | WP-2.2 |
| BR-002 | WP-2.3, WP-2.4 |
| BR-003 | WP-2.5 |
| BR-004 | WP-2.7 |
| BR-005 | WP-2.3 |
| BR-006 | WP-2.3 |
| BR-007 | WP-2.3 |
| BR-008 | WP-2.3, WP-2.2 |
| BR-009 | WP-2.3 |
| BR-010 | WP-2.3 |
| BR-011 | WP-2.5 |
| BR-012 | WP-1.5, WP-2.4 |
| BR-013 | WP-2.2 |
| BR-014 | WP-3.3 |
| BR-015 | WP-2.6 |
| BR-016 | WP-1.3 |
| BR-017 | WP-2.7 |
| BR-018 | WP-2.3 |

## متطلبات مقترحة (CR-002، غير معتمدة)

| المعرّف | الأولوية المقترحة | الميزة | الحزمة | الحالة |
| --- | --- | --- | --- | --- |
| OUT-01 | R1 (بيانات، D-26) | كتالوج نتاجات التعلم | WP-2.1 | Proposed |
| OUT-02 | R1 (بيانات، D-26) | وسم المحتوى بالنتاجات | WP-2.1 | Proposed |
| OUT-03 | R2 | تقدير الإتقان | WP-6.2 | Proposed |
| SLF-01 | R2 | مهمة اليوم | WP-6.2 | Proposed |
| SLF-02 | R2 | التكرار المتباعد | WP-6.2 | Proposed |
| SLF-03 | R2 | بطاقة النشاط العائلي | WP-6.2 | Proposed |
| FBK-01 | R2 | مكتبة الأخطاء المتوقعة | WP-6.2 | Proposed |
| FBK-02 | R2 | سُلّم التلميحات | WP-6.2 | Proposed |
| FBK-03 | R2 | تقييم الكتابة بالتتبع | WP-6.2 | Proposed |
| GAM-01 | R2 | قوالب الأنشطة | WP-6.2 | Proposed |
| RPT-10 | R2 | تقرير مبني على النتاجات | WP-6.2 | Proposed |
| AIT-01 | R2 | مساعد المعلمة الذكي | WP-6.7 | Proposed |
| AIC-01 | R3 | الزميل الذكي | WP-7.1 | Proposed |
| PRN-01 | R3 | تقييم النطق آلياً | WP-7.1 | Proposed |
