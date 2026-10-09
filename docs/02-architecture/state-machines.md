# آلات الحالة

كل انتقال حالة يُنفَّذ بفعل (Action) واحد في وحدته، داخل معاملة، ويكتب حدث صادر وسجل تدقيق. الانتقال غير الموجود في هذه الجداول مرفوض باستثناء (`InvalidTransition`). المصدر لكل جدول مذكور؛ ما أضافته الحزمة مذكور بمعرّف قراره.

## الاشتراك (Subscription) — 9 حالات

المصدر: مخطط «دورة حياة الاشتراك» في المواصفات (منقول في `spec/14-data-model-and-states.md`) + BIL-06 إلى BIL-09، BIL-14، BR-011.

| # | من | الحدث | الحارس (Guard) | إلى | الآثار |
| --- | --- | --- | --- | --- | --- |
| S1 | draft | `PlanSelected` | التسكين موجود؛ الشعبة المستهدفة فيها مقعد أو حجز مقعد ساري (D-30) | pending_payment | إنشاء جلسة دفع لدى البوابة؛ حجز مقعد 15 دقيقة؛ إشعار رابط الدفع |
| S2 | pending_payment | `PaymentSucceeded` (webhook موثق) | المبلغ = المتوقع؛ الدفعة غير معالجة سابقاً | active | قيد `CREDIT_PURCHASE +sessions_per_cycle` ودفعة رصيد (D-05)؛ تفعيل التسجيل؛ فاتورة (BIL-11)؛ إشعار نجاح الدفع |
| S3 | active | `Freeze` | ضمن قواعد D-27؛ سبب مكتوب (للعمليات) | paused | إيقاف التجديد؛ إيقاف ساعة الصلاحية (D-05)؛ الطالب خارج عدد الأجر (D-03) |
| S4 | paused | `Resume` (يدوي أو بلوغ `paused_until`) | — | active | تمديد `expires_at` للدفعات بمدة التجميد |
| S5 | active | `CreditsExhausted` | الرصيد المدفوع = 0 ولا تجديد معلّق | completed | لا دخول لحصص جديدة؛ إشعار |
| S6 | active | `RenewalPaymentFailed` | — | grace | الحصص مستمرة `billing.grace_period_days`؛ محاولات التحصيل `billing.dunning_retry_days`؛ إشعار الأسرة والمالية |
| S7 | grace | `PaymentCollected` | — | active | `CREDIT_PURCHASE` ودفعة رصيد جديدة |
| S8 | grace | `GraceExpired` (مهمة يومية) | انقضاء المهلة دون تحصيل | suspended | منع الدخول للحصص الجديدة (BR-011)؛ إشعار |
| S9 | suspended | `LatePaymentSucceeded` | — | active | كما في S7 |
| S10 | active / paused / suspended | `Cancel` | إلغاء ذاتي (BIL-14) أو من الإدارة بسبب | cancelled | لا حصص جديدة؛ إن كان جزئي الاستخدام: `CONVERSION_TO_WALLET` وقيد محفظة لثلاثة أشهر (D-28، CR-004)؛ تحرير المقعد |
| S11 | cancelled | `RefundApproved` | طلب استرداد معتمد وفق قواعد الاسترداد (BIL-12) | refunded | تنفيذ الاسترداد عبر البوابة أو المحفظة؛ قيد عكسي لما لم يُستهلك؛ إشعار دائن |

ملاحظات:
- `started_at` يُضبط عند أول حصة فعلية (المواصفات)، لا عند الدفع.
- قاعدة الاسترداد لاشتراك جديد لم تُستخدم منه حصة: استرداد كامل (S10 ثم S11 مباشرة).

## الشعبة (Class)

المصدر: SCH-02.

| من | الحدث | إلى | الحارس |
| --- | --- | --- | --- |
| draft | `OpenForEnrollment` | open | معلمة مسندة؛ قاعدة جدول صالحة؛ لا تقاطع مع نافذة الصيانة (D-24) |
| open | `Activate` | active | الطلاب المؤكدون ≥ `class.capacity_min` |
| active | `Suspend` | suspended | سبب مكتوب |
| suspended | `Resume` | active | — |
| open / active / suspended | `Complete` | completed | لا جلسات مستقبلية مجدولة |

## الجلسة (Session) والنتيجة

المصدر: `spec/14` (حالات الكيانات) و`spec/09` (نتائج الجلسة).

| من | الحدث | إلى |
| --- | --- | --- |
| scheduled | `Start` (أول دخول للمعلمة) | in_progress |
| in_progress | `End` | completed (مع outcome) |
| scheduled | `Cancel` | cancelled (مع outcome) |
| scheduled | `Postpone` | postponed (وتُنشأ جلسة بديلة) |

النتيجة عند الإقفال: `HELD`، `HELD_NO_SHOW`، `CANCELLED_TEACHER`، `CANCELLED_HOLIDAY`، `POSTPONED_GROUP`، `TECH_FAILURE`، وأثر كل منها على الرصيد والمستحقات كما في جدول «نتائج الجلسة».

## حضور الطالب

المصدر: جدول «حالات حضور الطالب» + ATT-01 إلى ATT-09.

| الحالة | الشرط | أثر الرصيد |
| --- | --- | --- |
| PRESENT | دخل خلال أول `attendance.late_after_min` وحضر ≥ `attendance.partial_below_pct` | `CONSUMPTION_ATTENDANCE −1` |
| LATE | دخل بعد `late_after_min` | `CONSUMPTION_ATTENDANCE −1` (BR-007) |
| PARTIAL | حضر أقل من النسبة | `CONSUMPTION_ATTENDANCE −1` (BR-007) |
| ABSENT_EXCUSED | طلب قبل `attendance.excuse_cutoff_hours` وضمن السقف (D-06) | `EXTENSION_ABSENCE_EXCUSED 0` |
| ABSENT_UNEXCUSED | بلا طلب، أو بعد المهلة، أو فوق السقف | `ABSENCE_UNEXCUSED −1` (BR-006) |

الاقتراح الآلي من أحداث الدخول والخروج (CLS-09)، والتأكيد من المعلمة خلال `attendance.teacher_confirm_hours`، ثم الاعتماد الآلي للمقترح، ثم الإقفال؛ التعديل بعده بصلاحية وسبب وقيد عكسي (ATT-05).

## طلب الغياب

مُرسل → مقبول آلياً (قبل المهلة وضمن السقف) أو بانتظار المراجعة → مقبول أو مرفوض.

## التقرير

مهمة → مسودة → مُرسل → (معاد للتعديل) → منشور → مُطَّلع عليه.

## الدفعة والاسترداد

- الدفعة: initiated → succeeded | failed → (refunded_partially | refunded).
- الاسترداد: requested → approved | rejected → executed.

## المعلمة

متقدمة → مقبولة في الفرز → مُقابَلة → في التدريب → تحت المراقبة → نشطة → موقوفة أو منتهية. التفعيل يتطلب شهادة عدم المحكومية وتدريب الحماية والإقرار بالمدونة (SAFE-04).

## التجربة

مؤهلة → مجدولة → حضر أو لم يحضر → اشترك أو «ليس الآن». أهلية واحدة لكل طفل (ONB-09، D-09)، والثانية بقرار العمليات (D-29).

## التسجيل في الشعبة (Enrollment)

pending (حجز مقعد) → active → paused (بالتجميد) → transferred | ended. كل انتقال يُكتب في `enrollment_history`.
