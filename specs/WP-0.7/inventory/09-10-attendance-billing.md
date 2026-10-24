<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory
Cite prefixes: S09 = /home/abdul/sura-academy/docs/01-product/spec/09-attendance-ledger-rules.md, S10 = .../10-billing-and-payments.md, S14 = .../14-data-model-and-states.md, S21 = .../21-appendices.md. `*` = column not listed in S14 "critical entities".
S09 has 9 R1 IDs (ATT-01..09). S10 has 16 R1 IDs (BIL-01..16).

| ID | Feature | Data | Tables | Columns/fields | States / enums | Settings | Cite |
|---|---|---|---|---|---|---|---|
| ATT-01 | Auto-suggest attendance, teacher confirms in 12h | Y | session_attendance, participant_events, attendance_exceptions | `*status_suggested` (inferred), `*status_final` (inferred), `*confirmed_by`/`*confirmed_at` (inferred), `*auto_approved` flag (inferred), `*flagged_for_supervisor` (inferred) | PRESENT, LATE, PARTIAL, ABSENT_EXCUSED, ABSENT_UNEXCUSED (S09:8-14) | attendance.teacher_confirm_hours, attendance.late_after_min, attendance.partial_below_pct | S09:45 |
| ATT-02 | Excuse deadline computed in UTC | Y | absence_requests, sessions | `*requested_at` (inferred), sessions.starts_at_utc; the cutoff is requested_at vs starts_at_utc (exactly 24h counts as in time) | request: مُرسل→مقبول آلياً/بانتظار المراجعة→مقبول/مرفوض (S14:60) | attendance.excuse_cutoff_hours | S09:46 |
| ATT-03 | Excused-absence cap 2 per 8 | Y | absence_requests, session_attendance | `*excused_count` is derived or stored (undefined) | ABSENT_EXCUSED vs ABSENT_UNEXCUSED when over cap | attendance.max_excused_per_8 | S09:47 |
| ATT-04 | Ledger entry stores rule version | Y | credit_ledger_entries, rule_versions | rule_version_id (already listed) | — | all attendance.* and credits.* | S09:48 |
| ATT-05 | Post-lock edits via reversal, supervisor + reason | Y | session_attendance, credit_ledger_entries, audit_logs | `*locked_at` (inferred), reverses_entry_id, reason, `*actor` (inferred) | type REVERSAL | — | S09:49 |
| ATT-06 | Ops exceptions list | Y | attendance_exceptions | `*kind` (inferred) | kinds stated: نزاعات، بيانات متعارضة، تأكيد متأخر; no status values stated | — | S09:50 |
| ATT-07 | Balance = sum of entries + snapshot | Y | credit_ledger_entries, credit_balance_snapshots | student_id, pool, type, qty; snapshot columns not specified | pool: مدفوع/تجريبي (S14:43) | — | S09:51 |
| ATT-08 | Process each session once | Y | sessions, credit_ledger_entries (unique student+session+type), outbox_events | `*processed_at`/idempotency marker for the session-ended event (inferred) | — | — | S09:52 |
| ATT-09 | Trial session type separate from paid pool | Y | sessions, credit_ledger_entries, compensation_lines | sessions.type = تجريبية, pool = تجريبي; trial sessions excluded from cycle dues | session type: عادية، تجريبية، بديلة (S14:42) | trial.per_child | S09:53 |
| BIL-01 | Plans | Y | plans | `*name`, `*sessions_count` (8), `*cycle_weeks` (4), `*student_coverage_rule`, `*validity_period` | — | subscription.sessions_per_cycle, subscription.cycle_weeks | S10:8 |
| BIL-02 | Price history per plan and currency | Y | prices, subscriptions | plan_id, `*currency`, `*amount_minor` (inferred), `*valid_from`, `*valid_to`; subscriptions.price_id; new price applies to new subscriptions only | — | billing.currency | S10:9 |
| BIL-03 | Family discount 12.5% from 2nd child | Y | discount_rules, invoice_lines | `*pct`, `*applies_from_child_index` (inferred), family basket level, family_id | — | billing.family_discount_pct | S10:10 |
| BIL-04 | Coupons | Y | coupons, coupon_redemptions | `*discount_type` (نسبة/مبلغ ثابت), `*value`, `*usage_limit`, `*expires_at`, `*first_month_only`, `*referral_code` | — | — | S10:11 |
| BIL-05 | Payment page, 3DS, wallets, saved token | Y | payments, payment_methods | method, provider, provider_ref, `*token` (provider token only), `*wallet_type` (Apple/Google Pay), `*three_ds_result` (inferred) | — | — | S10:12 |
| BIL-06 | Subscription states | Y | subscriptions, subscription_events | status; every change goes to subscription_events | draft, pending_payment, active, grace, suspended, paused, completed, cancelled, refunded (S14:64-82) | billing.grace_period_days | S10:13 |
| BIL-07 | Auto-renew at low balance | Y | subscriptions, notifications | auto_renew, renew_trigger_balance; reminder 3 days before; renewal can be switched off | — | subscription.renew_trigger_balance | S10:14 |
| BIL-08 | Failed payment dunning | Y | payments, subscription_events, notifications | `*attempt_no`/`*next_retry_at` (inferred); retries day 1, 3, 5; grace 5 days | active→grace→suspended; grace→active on collection (S14:75-77) | billing.dunning_retry_days, billing.grace_period_days | S10:15 |
| BIL-09 | Manual bank transfer | Y | payments, files | `*proof_file_id` (inferred), `*reviewed_by`/`*reviewed_at` (inferred), method=manual (inferred) | payment: معلقة→ناجحة | — | S10:16 |
| BIL-10 | Split one payment across subscriptions | Y | payment_allocations | payment_id, subscription_id, `*amount_minor` (inferred); same family only | — | — | S10:17 |
| BIL-11 | Invoices and receipts | Y | invoices, invoice_lines, files | `*invoice_number` (sequential), legal-entity data, lines, discounts, `*tax_lines` (when required), PDF in ar and en | — | — | S10:18 |
| BIL-12 | Refunds | Y | refunds, payments, wallet_entries | `*eligibility_result`, `*market`, `*amount_minor`, `*full_or_partial`, `*channel` (gateway/manual/wallet) | requested→approved/rejected→executed (S14:57); payment: refunded partially or fully (S14:56) | refund.cooling_off_days | S10:19 |
| BIL-13 | Family wallet balance | Y | wallet_entries | `*amount` (USD), `*source`, `*expires_at` (3 months from cancellation), `*used_on_payment` | — | billing.wallet_freeze_months, billing.currency | S10:20 |
| BIL-14 | Self-service cancel and freeze | Y | subscriptions, subscription_events | cancelled_at, paused_until; freeze keeps balance "for a set period" (period unspecified) | active↔paused; →cancelled | — | S10:21 |
| BIL-15 | Payment abstraction layer | N | payments (provider) | interface only: create session, charge saved card, refund, read event, fetch settlements | — | — | S10:22 |
| BIL-16 | Gateway events | Y | webhook_events | `*signature_valid`, `*raw_payload`, `*unique_event_key`, `*processing_status`/`*retry_count` (inferred) | — | — | S10:23 |

## Gaps
- Referral codes (BIL-04 "رموز الإحالة", S10:11): no referral table. coupons may host them, but the referrer and reward are not defined. Candidate `NEW:referral_codes`. Analytics events `referral_shared` and `referral_redeemed` exist (S21:71).
- Which students a subscription covers (BIL-01 "قاعدة الطلاب المشمولين", S10:8): S14:44 lists no subscription-to-student link. Ledger has student_id and subscription_id (S14:43).
- Cycle end and expiry date: CREDIT_EXPIRY is "بعد 60 يوماً من نهاية الدورة" (S09:38), but subscriptions has only started_at (S14:44), with no cycle_end.
- Sequential invoice number: S10:18 "ترقيم متسلسل". No counter or sequence table, and no legal-entity table. Legal-entity data is not a settings key in S21.
- Customer billing details on invoices (name, country, address): not in S14 for families/guardians. Tax (BIL-18) is R2.
- Dunning attempt tracking (BIL-08, S10:15): no table for retry schedule or attempts. subscription_events and notifications could carry it, but this is not stated.
- Payment-to-subscription link: payments has family_id only (S14:45). Allocation is via payment_allocations (BIL-10).
- Manual transfer proof (BIL-09, S10:16): "رفع إثبات". files exists (S14:36); no linkage is stated.
- Explicit consent for starting service during the 14-day withdrawal period (S10:34 "موافقة صريحة عند الدفع"): consents has purpose (S14:46), but no purpose value is stated.
- Refund eligibility depends on "السوق" (S10:19). No market or country field is listed for families.
- HELD_NO_SHOW 15-minute wait (S09:21) and TECH_FAILURE half-duration rule (S09:25) have no key in S21:8-39.
- Ledger consumption entry for the trial pool: CONSUMPTION_ATTENDANCE is "−1" with no pool stated (S09:33), and CREDIT_TRIAL is "+1 في رصيد منفصل" (S09:32).

## Personal data and retention
- Card data never passes through platform servers: "بيانات البطاقات لا تمر بخوادم المنصة إطلاقاً" (S10:4). Only a gateway token is saved (S10:12 "حفظ البطاقة كرمز للتجديد").
- Proof-of-transfer upload (S10:16): a file that may carry personal or financial data. No retention is stated.
- Invoice legal-entity and customer data (S10:18). No retention is stated.
- consents.ip, user_agent (S14:46). No retention is stated.
- Financial, attendance and report records are never permanently deleted. They are archived or corrected by reversal (S14:4). Permanent deletion only via privacy requests and the retention schedule (S14:13).
- Retention and expiry periods stated: credit expiry 60 days after cycle end (S09:38; S21:21); wallet 3 months from cancellation (S10:20; S21:27).
- Analytics events use pseudonymous IDs with no names or contact data (S21:71), including payment_succeeded, payment_failed and subscription_*.
- Attendance and participant data (entry/exit events, S09:45) concern children. No retention is stated in these files.

## Append-only, audit, money, time
- Balance is not a stored editable number. It is computed from entries that are never edited or deleted (S09:4). Ledger: "لا تعديل ولا حذف"; unique (student, session, type) blocks double deduction (S14:43).
- Reversal corrects without modifying the original and points to it (S09:39; reverses_entry_id, S14:43).
- ATT-04: every entry stores rule_version_id (S09:48). Settings, rules, legal documents and templates are versioned (S14:15).
- ATT-05: post-lock edits need a supervisor and a reason, executed as a new reversal entry (S09:49). ADJUSTMENT_MANUAL needs "سبب مكتوب وصلاحية" (S09:37).
- ATT-08: one processing per session even if the end event arrives twice (S09:52).
- ATT-02: UTC deadline (S09:46). General rules: timestamptz in UTC; class pattern as local hour plus IANA zone (S14:11).
- Money is an integer in minor units plus currency code: USD cents, JOD fils with 3 decimals (S14:12). Price history has start and end dates (S10:9).
- Payments: idempotency_key; provider_ref unique per gateway; no double processing (S14:45). Gateway events: unique key against duplicates, raw event stored (S10:23).
- Every subscription state change is written to subscription_events (S14:44).
- `created_by` and `updated_by` on every table, plus a separate append-only audit log (S14:16).
- Invoices have sequential numbering (S10:18). S14:10 says UUIDv7 IDs with no sequential numbers in URLs.
- Wallet balance is in USD (S10:20); `billing.currency` default USD (S21:28).
- enrollment_history is written with every student lifecycle transition (S14:47).

## Ambiguities
- BR-011 label vs BIL-08: S21:86 "BR-011 تعليق الوصول بعد انتهاء الرصيد → BIL-08", but BIL-08 (S10:15) concerns failed payment and a 5-day grace, not balance exhaustion. S14:74 handles balance exhaustion as "نفاد الرصيد → مكتمل (completed)".
- BR-003 label vs R1 text: S21:79 "الاستحقاق من أول حصة → BIL-07، والتعديل إلى رصيد متجدد في قسم القرارات". BIL-07 says the renewal is triggered by balance (S10:14). Meanwhile S14:44 has started_at "أول حصة فعلية".
- Session vs outcome vocabulary: session states are "مجدولة → مباشرة → منعقدة، أو ملغاة، أو مؤجلة" (S14:54). Outcomes are HELD, HELD_NO_SHOW, CANCELLED_TEACHER, CANCELLED_HOLIDAY, POSTPONED_GROUP, TECH_FAILURE (S09:20-25). sessions has both status and outcome (S14:42). The mapping between them is not stated.
- Cap window: ATT-03 "حصتان لكل 8 حصص" (S09:47) and `attendance.max_excused_per_8` = 2 (S21:18). The "per 8" window (per cycle, rolling, or per subscription) is not defined. S09:13 says "ضمن السقف".
- TECH_FAILURE: "لا خصم إذا لم تنعقد نصف المدة" (S09:25). The deduction when half the duration was held is implied but not stated. Teacher effect "قرار العمليات".
- Trial pool: CREDIT_TRIAL +1 on trial approval (S09:32). The ledger types list no consumption type for the trial pool (S09:33 is generic). ATT-09 says trial sessions do not enter the paid pool (S09:53). S14:42: "النوع التجريبي لا يمس الرصيد المدفوع".
- Uniqueness vs reversals: unique (student_id, session_id, type) (S14:43) while REVERSAL is a type (S09:39). Whether multiple reversals or a reversal per session are allowed under that key is not stated.
- Plan hard-codes vs settings: "+8 (حسب الخطة)" (S09:31) and BIL-01 "عدد الحصص (8)" (S10:8) vs `subscription.sessions_per_cycle` = 8 (S21:22).
- Thresholds in text vs settings: S09:9-12 gives 10 minutes and half-duration in the table. S21:15-16 gives `attendance.late_after_min` = 10 and `attendance.partial_below_pct` = 50%. The 15-minute HELD_NO_SHOW wait (S09:21) has no key.
- Wallet currency: BIL-13 "رصيد بالدولار" (S10:20) vs "عدد صحيح بأصغر وحدة مع رمز العملة" for money (S14:12). `billing.currency` USD (S21:28), while BIL-19 multi-currency is R3 (S10:26).
- "رصيد" overload: session credit (S09:4) vs financial wallet (S10:20). Also S10:33: "يُجمّد المتبقي رصيداً مالياً لثلاثة أشهر من اعتماد الإلغاء". BIL-13 says "3 أشهر من الإلغاء" (S10:20), S21:27 says "من اعتماد الإلغاء". What is converted (remaining sessions or an amount) is not stated.
- Europe: S10:28 "تبقى أوروبا خارج الموجة الأولى حتى يجهز BIL-18" (R2), but `refund.cooling_off_days` has an EU/UK rule of 14 days (S21:29) and S10:34 lists EU/UK withdrawal rules.
- Dunning anchor: BIL-08 "اليوم 1 و3 و5" (S10:15) vs grace "5 أيام" (S14:75, 77). The day-count origin is unspecified, and day 5 coincides with grace expiry.
- Payment state values: S14:56 gives "معلقة → ناجحة أو فاشلة → مستردة جزئياً أو كلياً". BIL-09 says "تحويل الدفعة إلى «مدفوعة»" (S10:16). The wording differs (ناجحة vs مدفوعة).
- Freeze period: BIL-14 "لفترة محددة" (S10:21) with no setting key; paused_until exists (S14:44).
- Invoice numbering (sequential, S10:18) vs UUIDv7 IDs (S14:10). Scope (per entity or per year) is not stated.

## Business rules (BR-0xx, verbatim)
Only the mapping table exists in the repo. It lists the rule name and the features that implement it. No BR text beyond the name. README says "القواعد BR-001…BR-018 مربوطة بالميزات في `21-appendices.md`" (/home/abdul/sura-academy/docs/01-product/spec/README.md:33).
S21:77 | BR-001 حجم الشعبة | SCH-01، SCH-08 |
S21:78 | BR-002 الاشتراك رصيد 8 حصص | BIL-01، ATT-07 |
S21:79 | BR-003 الاستحقاق من أول حصة | BIL-07، والتعديل إلى رصيد متجدد في قسم القرارات |
S21:80 | BR-004 تجربة واحدة | ONB-09 |
S21:81 | BR-005 الغياب المبرر قبل 24 ساعة | ATT-02، ATT-03، PAR-05 |
S21:82 | BR-006 الغياب غير المبلغ | حالات الحضور، ABSENCE_UNEXCUSED |
S21:83 | BR-007 التأخر يُحتسب | حالتا LATE وPARTIAL |
S21:84 | BR-008 غياب المعلمة والعطل | نتائج الجلسة، SCH-10، SCH-12 |
S21:85 | BR-009 و010 غياب أكثر من طالب | POSTPONED_GROUP، HELD_NO_SHOW |
S21:86 | BR-011 تعليق الوصول بعد انتهاء الرصيد | BIL-08 |
S21:87 | BR-012 أكثر من طفل في الحساب | PAR-02، BIL-03 |
S21:88 | BR-013 التوقيت | SCH-05، SCH-06، قواعد البيانات العامة |
S21:89 | BR-014 خصوصية التسجيلات | CLS-10، SEC-10 |
S21:90 | BR-015 أجر المعلمة | PAY-01 إلى PAY-07 |
S21:91 | BR-016 حدود صلاحيات المعلمة | RBAC-02، جدول الأدوار |
S21:92 | BR-017 قرار التسكين للمشرف | ASM-03 |
S21:93 | BR-018 نوع مستقل للحصص التجريبية | ATT-09، CREDIT_TRIAL |
Inline BR references: S09:11 (BR-007 LATE), S09:12 (BR-007 PARTIAL), S09:14 (BR-006), S09:21 (BR-010), S09:22 and S09:23 (BR-008), S09:24 (BR-009), S09:32 (BR-018), S10:15 (BR-011).

Student attendance states (S09:8-14, verbatim)
| PRESENT | دخل خلال أول 10 دقائق وحضر نصف المدة على الأقل | خصم حصة |
| LATE | دخل بعد 10 دقائق من البدء | خصم حصة (BR-007) |
| PARTIAL | حضر أقل من نصف المدة | خصم حصة (BR-007) |
| ABSENT_EXCUSED | طلب غياب قبل 24 ساعة أو أكثر من البدء، ضمن السقف | لا خصم، وقيد صفري للتتبع |
| ABSENT_UNEXCUSED | لا طلب، أو طلب بعد المهلة، أو تجاوز السقف | خصم حصة (BR-006) |

Session outcomes: outcome → student/credit effect → teacher pay effect (S09:19-25, verbatim)
| HELD | حسب حالة كل طالب | حصة منفذة |
| HELD_NO_SHOW | لم يحضر أحد بعد انتظار 15 دقيقة: خصم للجميع (BR-010) | حصة منفذة |
| CANCELLED_TEACHER | لا خصم، وتُضاف جلسة بديلة (BR-008) | لا تُحتسب |
| CANCELLED_HOLIDAY | لا خصم، وتُضاف جلسة بديلة (BR-008) | تُحتسب عند انعقاد البديلة |
| POSTPONED_GROUP | أكثر من نصف الشعبة بعذر: لا خصم لأحد وجلسة بديلة (BR-009) | تُحتسب عند انعقاد البديلة |
| TECH_FAILURE | لا خصم إذا لم تنعقد نصف المدة | قرار العمليات |

Ledger entry types: type → qty → when (S09:29-39, verbatim)
| CREDIT_PURCHASE | +8 (حسب الخطة) | نجاح دفعة الاشتراك |
| CREDIT_TRIAL | +1 في رصيد منفصل | اعتماد التجربة؛ لا يختلط بالمدفوع (BR-018) |
| CONSUMPTION_ATTENDANCE | −1 | جلسة منعقدة والطالب حاضر أو متأخر أو جزئي |
| ABSENCE_UNEXCUSED | −1 | غياب غير مبرر |
| EXTENSION_ABSENCE_EXCUSED | 0 | غياب مبرر ضمن السقف، للتتبع فقط |
| COMPENSATION_ACADEMY | +1 | جلسة خُصمت ثم ثبت خلل من جهة الأكاديمية |
| ADJUSTMENT_MANUAL | ±N | قرار إداري بسبب مكتوب وصلاحية |
| CREDIT_EXPIRY | −N | انتهاء صلاحية رصيد بعد 60 يوماً من نهاية الدورة (إعداد) |
| REVERSAL | عكس قيد سابق | تصحيح خطأ، ويشير إلى القيد الأصلي دون تعديله |

Default refund rules (S10:30-35):
- S10:31 اشتراك جديد لم تُستخدم منه أي حصة: استرداد كامل.
- S10:32 اشتراك استُخدم جزء منه: لا استرداد نقدي، ويُجمّد المتبقي رصيداً مالياً لثلاثة أشهر من اعتماد الإلغاء.
- S10:33 أسر الاتحاد الأوروبي وبريطانيا: مهلة انسحاب 14 يوماً، مع موافقة صريحة عند الدفع على بدء الخدمة خلالها.
- S10:34 خلل من جهة الأكاديمية: استرداد أو رصيد حسب اختيار الأسرة.
(Line numbers for the four refund bullets are S10:31-34 in the file view. S10:30 is the heading line "قواعد الاسترداد الافتراضية". The line numbers in the Ambiguities and Personal-data sections above that cite S10:33/34 for the 3-month wallet and the EU/UK rule should be read as S10:32 and S10:33 respectively.)

Settings keys relevant to the assigned files (S21): attendance.late_after_min (S21:15), attendance.partial_below_pct (:16), attendance.excuse_cutoff_hours (:17), attendance.max_excused_per_8 (:18), attendance.teacher_confirm_hours (:19), attendance.group_postpone_threshold (:20), credits.expiry_days_after_cycle (:21), subscription.sessions_per_cycle / cycle_weeks (:22), subscription.renew_trigger_balance (:23), billing.grace_period_days (:24), billing.dunning_retry_days (:25), billing.family_discount_pct (:26), billing.wallet_freeze_months (:27), billing.currency (:28), refund.cooling_off_days (:29), trial.per_child (:14).
Related automated notifications (S21:41-67): payment success and receipt (:50), payment failure to parent and finance (:51), upcoming renewal or low balance, 3 days before / at 2 sessions (:52), subscription suspension (:53), absence request decision (:58).

## R2/R3 rows with data impact (one line each)
BIL-17 — settlement import and reconciliation (R2) — settlements, payments, webhook_events
BIL-18 — tax by customer country (R2) — invoices, invoice_lines, families (country evidence: two proofs, no field listed)
BIL-19 — multi-currency display and payment (R3) — prices, payments, invoices, wallet_entries
