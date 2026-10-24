# ADR-007: دفتر الرصيد للإضافة فقط مع دفعات الرصيد

- الحالة: Accepted · المصدر: `spec/09`، BR-002، BR-005 إلى BR-010، BR-018، D-05، D-28

## القرار
1. `credit_ledger_entries` للإضافة فقط ومحمي في قاعدة البيانات (محفز + صلاحيات).
2. الحقول: `student_id`، `pool` (paid | trial)، `type`، `qty` (موجب أو سالب)، `session_id`، `subscription_id`، `lot_id`، `rule_version_id`، `reason`، `reverses_entry_id`، `idempotency_key`، `actor_id`، `created_at`.
3. الأنواع: CREDIT_PURCHASE، CREDIT_TRIAL، CONSUMPTION_ATTENDANCE، ABSENCE_UNEXCUSED، EXTENSION_ABSENCE_EXCUSED، COMPENSATION_ACADEMY، ADJUSTMENT_MANUAL، CREDIT_EXPIRY، REVERSAL، وCONVERSION_TO_WALLET (CR-004).
4. **الرصيد** = مجموع `qty` للطالب والوعاء؛ يُحسب من الدفتر دائماً، و`credit_balance_snapshots` كاش يُعاد بناؤه للتحقق.
5. **دفعات الرصيد** (`credit_lots`) تحدد الصلاحية والاستهلاك بالترتيب (D-05)، وتُشتق من الدفتر.
6. **منع الخصم المزدوج:** قيد فريد `(student_id, session_id, type)` + مفتاح عدم تكرار + معالجة نهاية الجلسة مرة واحدة (ATT-08).
   - تفسير WP-0.7 (ضمن التصميم الذي اعتمده صاحب المشروع في 2026-10-24، وراجعه `ledger-auditor`): القيد على أنواع قيود الحصص الأربعة فقط: `CONSUMPTION_ATTENDANCE`، `ABSENCE_UNEXCUSED`، `EXTENSION_ABSENCE_EXCUSED`، `COMPENSATION_ACADEMY` (الدستور م5 §2 «لقيود الحصص»). `REVERSAL` خارجه، ويحميه `UNIQUE (reverses_entry_id)`. وإعادة قيد سبق عكسه تكون بعكس قيد العكس. التفاصيل في `docs/02-architecture/erd/ledger.md`.
7. **التصحيح:** قيد `REVERSAL` يشير للأصل، ثم قيد صحيح جديد. لا تعديل.

## التحقق
- اختبار خاصية: لأي تسلسل أحداث، الرصيد = مجموع الدفتر، ولا رصيد سالب في الوعاء المدفوع دون قيد يدوي مسبب.
- مهمة يومية تقارن اللقطات بمجموع الدفتر وتنبه عند أي فرق.
