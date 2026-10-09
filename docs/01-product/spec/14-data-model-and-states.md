<!-- منقول آلياً من المصدر (المراجعة 24). لا يُعدَّل إلا عبر طلب تغيير CR وفق docs/00-governance/change-control.md -->
## نموذج البيانات وحالات الكيانات

قاعدة بيانات علائقية واحدة (PostgreSQL) بنحو 90 جدولاً في 15 مجالاً، بعد إضافة 14 كياناً تنقص وثيقة SRS. السجلات المالية والحضور والتقارير لا تُحذف نهائياً أبداً، بل تُؤرشف أو تُصحح بقيد عكسي.

### قواعد عامة

| القاعدة | التطبيق |
| --- | --- |
| المعرّفات | UUIDv7 مرتبة زمنياً، ولا تظهر أرقام متسلسلة في الروابط |
| الأوقات | `timestamptz` بتوقيت UTC؛ نمط الشعبة يُحفظ ساعةً محلية مع منطقة IANA المرجعية |
| المال | عدد صحيح بأصغر وحدة مع رمز العملة: الدولار بالسنت، والدينار الأردني بالفلس (ثلاث خانات عشرية) |
| الحذف | أرشفة بحقل `archived_at`؛ الحذف النهائي فقط بطلبات الخصوصية وجدول الاحتفاظ |
| النطاق | كل جدول يحمل `family_id` أو `class_id` أو `teacher_id` حيث ينطبق، لفرض سياسات الصلاحيات |
| الإصدارات | الإعدادات والقواعد والوثائق القانونية والقوالب بإصدارات، وكل سجل يشير للإصدار الذي طُبق عليه |
| التدقيق | `created_by` و`updated_by` في كل جدول، وسجل تدقيق منفصل للإضافة فقط |

### الجداول حسب المجال

| المجال | الجداول |
| --- | --- |
| الهوية والوصول | users، roles، permissions، role_permissions، user_roles، auth_sessions، mfa_factors، student_pins، device_authorizations |
| الموافقات والقانون | legal_documents، consents، data_requests |
| الأسر والطلاب | families، guardians، students، student_profiles، family_notes |
| العملاء المحتملون | leads، lead_events، follow_up_tasks، waitlist_entries |
| المنهج | programs، stages، levels، level_outcomes، modules، lessons، lesson_plans، content_items، content_versions، media_assets، qr_codes |
| التقييم | placement_quizzes، quiz_attempts، assessment_slots، assessments، assessment_scores، placements، tests، questions، test_attempts، test_answers |
| التجارب | trials |
| الجدولة | classes، class_schedule_rules، class_enrollments، enrollment_history، sessions، session_changes، calendar_exceptions، teacher_availability، teacher_leaves، change_requests، student_availability |
| الفصل الافتراضي | live_rooms، join_token_log، participant_events، recordings، recording_access_log |
| الحضور والرصيد | session_attendance، absence_requests، credit_ledger_entries، credit_balance_snapshots، rule_versions، attendance_exceptions |
| الفوترة | plans، prices، discount_rules، coupons، coupon_redemptions، subscriptions، subscription_events، payment_methods، payments، payment_allocations، invoices، invoice_lines، refunds، wallet_entries، webhook_events، settlements |
| التعلم | assignments، submissions، submission_files، feedback، reports، report_items، report_reviews |
| التواصل | message_templates، notifications، delivery_logs، support_threads، messages، announcements |
| المعلمات | teachers، teacher_applications، teacher_documents، trainings، training_completions، observations، compensation_rules، compensation_lines، compensation_statements، payouts |
| النظام | settings، audit_logs، files، feature_flags، outbox_events، safeguarding_incidents |

### الكيانات الحرجة وحقولها

| الكيان | الحقول الأساسية | قيود مهمة |
| --- | --- | --- |
| sessions | class_id، starts_at_utc، ends_at_utc، local_start، ref_timezone، type (عادية، تجريبية، بديلة)، status، outcome، actual_teacher_id، live_room_id | لا تداخل لمعلمة واحدة؛ النوع التجريبي لا يمس الرصيد المدفوع |
| credit_ledger_entries | student_id، pool (مدفوع، تجريبي)، type، qty، session_id، subscription_id، rule_version_id، reason، reverses_entry_id | لا تعديل ولا حذف؛ مفتاح فريد (طالب، جلسة، نوع) يمنع الخصم المزدوج |
| subscriptions | family_id، plan_id، price_id، status، started_at (أول حصة فعلية)، auto_renew، renew_trigger_balance، paused_until، cancelled_at | كل تغيير حالة يُكتب في subscription_events |
| payments | family_id، amount_minor، currency، method، provider، provider_ref، status، idempotency_key | provider_ref فريد لكل بوابة؛ لا معالجة مزدوجة |
| consents | user_id، student_id، purpose، legal_document_version_id، granted_at، withdrawn_at، ip، user_agent | الموافقة لا تُعدّل، والسحب سجل جديد |
| enrollment_history | student_id، from_state، to_state، actor_id، reason، occurred_at | يُكتب مع كل انتقال في دورة حياة الطالب |

### حالات الكيانات ذات المسار المستقيم

| الكيان | الحالات بالترتيب |
| --- | --- |
| الشعبة | مسودة → مفتوحة → نشطة → موقوفة → مكتملة |
| الجلسة | مجدولة → مباشرة → منعقدة، أو ملغاة، أو مؤجلة |
| التقرير | مهمة → مسودة → مُرسل → (معاد للتعديل) → منشور → مُطّلع عليه |
| الدفعة | معلقة → ناجحة أو فاشلة → مستردة جزئياً أو كلياً |
| الاسترداد | مطلوب → معتمد أو مرفوض → منفذ |
| المعلمة | متقدمة → مقبولة في الفرز → مُقابَلة → في التدريب → تحت المراقبة → نشطة → موقوفة أو منتهية |
| التجربة | مؤهلة → مجدولة → حضر أو لم يحضر → اشترك أو «ليس الآن» |
| طلب الغياب | مُرسل → مقبول آلياً أو بانتظار المراجعة → مقبول أو مرفوض |

دورة حياة الاشتراك تتفرع وتعود، ولذلك تُرسم أدناه.

> **شكل (منقول نصاً من مخطط الوثيقة): دورة حياة الاشتراك · 9 حالات**
>
> عنوان المخطط: «الاشتراك نشط ما دام مدفوعاً، والدفعة الفاشلة تمر بمهلة قبل الإيقاف».
>
> | من | الحدث | إلى | ملاحظة الحالة الهدف |
> | --- | --- | --- | --- |
> | مسودة (draft) | اختيار الخطة | بانتظار الدفع (pending_payment) | رابط الدفع والتذكير |
> | بانتظار الدفع | نجاح الدفع | نشط (active) | دخول الحصص والمحتوى |
> | نشط | تجميد | مجمّد (paused) | الرصيد محفوظ |
> | مجمّد | عودة | نشط | — |
> | نشط | نفاد الرصيد | مكتمل (completed) | انتهى الرصيد |
> | نشط | فشل التجديد | مهلة سماح (grace) | الحصص مستمرة 5 أيام |
> | مهلة سماح | تحصيل الدفعة | نشط | — |
> | مهلة سماح | بعد 5 أيام | موقوف (suspended) | لا دخول لحصص جديدة |
> | موقوف | دفع لاحق | نشط | — |
> | نشط | إلغاء | ملغى (cancelled) | لا حصص جديدة |
> | مجمّد | إلغاء | ملغى | — |
> | موقوف | إلغاء | ملغى | — |
> | ملغى | استرداد معتمد | مسترد (refunded) | أُعيد المبلغ |
>
> الشروط والآثار التفصيلية لكل انتقال (الحارس، القيد في الدفتر، الإشعار) محددة في `docs/02-architecture/state-machines.md`.

الدفعة الفاشلة لا توقف الحصص فوراً بل تمر بمهلة خمسة أيام، والدفع اللاحق يعيد الاشتراك نشطاً من المهلة أو من الإيقاف.
