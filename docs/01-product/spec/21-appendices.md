<!-- منقول آلياً من المصدر (المراجعة 24). لا يُعدَّل إلا عبر طلب تغيير CR وفق docs/00-governance/change-control.md -->
## الملاحق

هذه القوائم مرجع للمطورين ولفريق التشغيل: كل قيمة في جدول الإعدادات تُعدّل من لوحة الإدارة دون تعديل الكود، وكل تعديل يُسجل بإصداره.

### الإعدادات القابلة للتعديل وقيمها الافتراضية

| المفتاح | القيمة الافتراضية |
| --- | --- |
| `class.capacity_min` / `class.capacity_max` | 2 / 4، والتجاوز بصلاحية المشرف |
| `session.duration_by_age` | 5–7: 60 د؛ 8–10: 75 د؛ 11–14: 90 د |
| `session.join_window_before_min` | 10 دقائق |
| `session.room_close_after_end_min` | 10 دقائق |
| `trial.per_child` | 1، مع حصة ثانية بقرار العمليات |
| `attendance.late_after_min` | 10 دقائق |
| `attendance.partial_below_pct` | 50% |
| `attendance.excuse_cutoff_hours` | 24 ساعة |
| `attendance.max_excused_per_8` | 2 |
| `attendance.teacher_confirm_hours` | 12 ساعة |
| `attendance.group_postpone_threshold` | أكثر من 50% من الشعبة |
| `credits.expiry_days_after_cycle` | 60 يوماً |
| `subscription.sessions_per_cycle` / `cycle_weeks` | 8 / 4 |
| `subscription.renew_trigger_balance` | 2 حصص |
| `billing.grace_period_days` | 5 أيام |
| `billing.dunning_retry_days` | 1، 3، 5 |
| `billing.family_discount_pct` | 12.5% من الطفل الثاني |
| `billing.wallet_freeze_months` | 3 أشهر من اعتماد الإلغاء |
| `billing.currency` | USD |
| `refund.cooling_off_days` | الاتحاد الأوروبي وبريطانيا: 14؛ غيرهما: 0 |
| `teacher.pay_base_jod` / `pay_per_student_jod` | 35 / 5 |
| `teacher.night_premium_pct` / `night_start` | 15% / 22:00 بتوقيت عمّان |
| `teacher.substitute_per_session_jod` | 6.25 |
| `reports.review_sample_pct` / `new_teacher_full_review_months` | 20% / 3 |
| `recordings.family_days` / `admin_days` / `link_ttl_min` | 30 / 90 / 10 |
| `notifications.quiet_hours` | 21:00–08:00 بتوقيت المستلم |
| `scheduling.generate_weeks_ahead` | 8 أسابيع |
| `scheduling.dst_alert_days` | 21 يوماً |
| `waitlist.open_class_threshold` | 3 طلاب |
| `security.child_pin_attempts` / `lock_min` | 5 / 15 |

### الإشعارات الآلية

| المحفز | المستلم | القناة | التوقيت |
| --- | --- | --- | --- |
| إنشاء الحساب والتحقق | ولي الأمر | بريد، رسالة نصية للرمز | فوري |
| حجز التقييم أو التجربة | ولي الأمر | بريد مع ملف تقويم | فوري |
| تذكير التقييم أو التجربة أو الحصة | ولي الأمر، والطالب داخل التطبيق | بريد، واتساب في R2 | قبل 24 ساعة وقبل ساعة |
| نتيجة التسكين | ولي الأمر | بريد | فوري |
| متابعة بعد التجربة مع العرض | ولي الأمر | بريد | بعد 24 ساعة |
| نجاح الدفع والإيصال | ولي الأمر | بريد | فوري |
| فشل الدفع | ولي الأمر والمالية | بريد وداخل التطبيق | فوري ومع كل إعادة محاولة |
| تجديد قادم أو رصيد منخفض | ولي الأمر | بريد | قبل 3 أيام / عند حصتين |
| إيقاف الاشتراك | ولي الأمر | بريد وداخل التطبيق | فوري |
| الإسناد لشعبة | ولي الأمر والمعلمة | بريد | فوري |
| تغيير أو إلغاء حصة | ولي الأمر والمعلمة | بريد وداخل التطبيق | فوري |
| تغيير الساعة بسبب التوقيت الصيفي | ولي الأمر والمعلمة | بريد | قبل 14 يوماً |
| غياب طارئ للمعلمة | العمليات | رسالة نصية وداخل التطبيق | فوري، أولوية عالية |
| قرار طلب الغياب | ولي الأمر | داخل التطبيق | فوري |
| واجب جديد أو موعده غداً | ولي الأمر والطالب | داخل التطبيق وبريد | عند النشر وقبل يوم |
| ملاحظات الواجب منشورة | ولي الأمر | داخل التطبيق | فوري |
| المؤشرات الأسبوعية | ولي الأمر | بريد ملخص | أسبوعياً |
| التقرير الشهري | ولي الأمر | بريد وداخل التطبيق | عند النشر |
| تقرير متأخر | المعلمة والمشرف | داخل التطبيق وبريد | عند التأخر |
| تسجيل الحصة متاح | ولي الأمر (إن اختار ذلك) | داخل التطبيق | عند الجاهزية |
| رد على تذكرة دعم | ولي الأمر | بريد وداخل التطبيق | فوري |
| كشف المستحقات جاهز | المعلمة | بريد | شهرياً |
| بلاغ حماية طفل | مسؤول الحماية | رسالة نصية وبريد | فوري، أولوية قصوى |

### أحداث التحليلات

تُرسل بمعرّفات مستعارة ودون أسماء أو بيانات اتصال: `lead_created`، `signup_started`، `signup_completed`، `email_verified`، `child_added`، `placement_quiz_completed`، `assessment_booked`، `assessment_completed`، `placement_assigned`، `trial_booked`، `trial_attended`، `trial_no_show`، `checkout_started`، `payment_succeeded`، `payment_failed`، `subscription_activated`، `subscription_renewed`، `subscription_paused`، `subscription_cancelled`، `session_joined`، `session_left`، `homework_submitted`، `report_viewed`، `support_ticket_created`، `referral_shared`، `referral_redeemed`.

### ربط قواعد العمل بالميزات

| القاعدة في SRS | الميزات التي تنفذها |
| --- | --- |
| BR-001 حجم الشعبة | SCH-01، SCH-08 |
| BR-002 الاشتراك رصيد 8 حصص | BIL-01، ATT-07 |
| BR-003 الاستحقاق من أول حصة | BIL-07، والتعديل إلى رصيد متجدد في قسم القرارات |
| BR-004 تجربة واحدة | ONB-09 |
| BR-005 الغياب المبرر قبل 24 ساعة | ATT-02، ATT-03، PAR-05 |
| BR-006 الغياب غير المبلغ | حالات الحضور، ABSENCE_UNEXCUSED |
| BR-007 التأخر يُحتسب | حالتا LATE وPARTIAL |
| BR-008 غياب المعلمة والعطل | نتائج الجلسة، SCH-10، SCH-12 |
| BR-009 و010 غياب أكثر من طالب | POSTPONED_GROUP، HELD_NO_SHOW |
| BR-011 تعليق الوصول بعد انتهاء الرصيد | BIL-08 |
| BR-012 أكثر من طفل في الحساب | PAR-02، BIL-03 |
| BR-013 التوقيت | SCH-05، SCH-06، قواعد البيانات العامة |
| BR-014 خصوصية التسجيلات | CLS-10، SEC-10 |
| BR-015 أجر المعلمة | PAY-01 إلى PAY-07 |
| BR-016 حدود صلاحيات المعلمة | RBAC-02، جدول الأدوار |
| BR-017 قرار التسكين للمشرف | ASM-03 |
| BR-018 نوع مستقل للحصص التجريبية | ATT-09، CREDIT_TRIAL |
