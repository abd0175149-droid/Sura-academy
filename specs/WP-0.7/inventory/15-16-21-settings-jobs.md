<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## Settings

Source for all rows is `21-appendices.md`, with the full path `/home/abdul/sura-academy/docs/01-product/spec/21-appendices.md`. Other files are cited as `00`, `13`, `14`, `15` and `16`, meaning `spec/<file>.md`. The table header says each value is editable from the admin panel and "كل تعديل يُسجل بإصداره" (21:4).

| key | default (verbatim) | unit/type as stated | cite |
|---|---|---|---|
| `class.capacity_min` / `class.capacity_max` | 2 / 4، والتجاوز بصلاحية المشرف | count of students; override by permission | 21:10 |
| `session.duration_by_age` | 5–7: 60 د؛ 8–10: 75 د؛ 11–14: 90 د | minutes, keyed by age band (structured) | 21:11 |
| `session.join_window_before_min` | 10 دقائق | minutes | 21:12 |
| `session.room_close_after_end_min` | 10 دقائق | minutes | 21:13 |
| `trial.per_child` | 1، مع حصة ثانية بقرار العمليات | count | 21:14 |
| `attendance.late_after_min` | 10 دقائق | minutes | 21:15 |
| `attendance.partial_below_pct` | 50% | percent | 21:16 |
| `attendance.excuse_cutoff_hours` | 24 ساعة | hours | 21:17 |
| `attendance.max_excused_per_8` | 2 | count per 8 sessions | 21:18 |
| `attendance.teacher_confirm_hours` | 12 ساعة | hours | 21:19 |
| `attendance.group_postpone_threshold` | أكثر من 50% من الشعبة | percent of class (no `_pct` suffix) | 21:20 |
| `credits.expiry_days_after_cycle` | 60 يوماً | days | 21:21 |
| `subscription.sessions_per_cycle` / `cycle_weeks` | 8 / 4 | sessions / weeks | 21:22 |
| `subscription.renew_trigger_balance` | 2 حصص | sessions | 21:23 |
| `billing.grace_period_days` | 5 أيام | days | 21:24 |
| `billing.dunning_retry_days` | 1، 3، 5 | list of days | 21:25 |
| `billing.family_discount_pct` | 12.5% من الطفل الثاني | percent (decimal) | 21:26 |
| `billing.wallet_freeze_months` | 3 أشهر من اعتماد الإلغاء | months | 21:27 |
| `billing.currency` | USD | ISO currency code | 21:28 |
| `refund.cooling_off_days` | الاتحاد الأوروبي وبريطانيا: 14؛ غيرهما: 0 | days, varies by region (structured) | 21:29 |
| `teacher.pay_base_jod` / `pay_per_student_jod` | 35 / 5 | JOD (suffix `_jod`); "د.أ" at 00:31 | 21:30 |
| `teacher.night_premium_pct` / `night_start` | 15% / 22:00 بتوقيت عمّان | percent / time in Amman zone | 21:31 |
| `teacher.substitute_per_session_jod` | 6.25 | JOD (suffix `_jod`); "د.أ" at 00:25 | 21:32 |
| `reports.review_sample_pct` / `new_teacher_full_review_months` | 20% / 3 | percent / months | 21:33 |
| `recordings.family_days` / `admin_days` / `link_ttl_min` | 30 / 90 / 10 | days / days / minutes | 21:34 |
| `notifications.quiet_hours` | 21:00–08:00 بتوقيت المستلم | time range in the recipient's zone | 21:35 |
| `scheduling.generate_weeks_ahead` | 8 أسابيع | weeks | 21:36 |
| `scheduling.dst_alert_days` | 21 يوماً | days | 21:37 |
| `waitlist.open_class_threshold` | 3 طلاب | students | 21:38 |
| `security.child_pin_attempts` / `lock_min` | 5 / 15 | attempts / minutes | 21:39 |

**Money keys:** `teacher.pay_base_jod`, `teacher.pay_per_student_jod` and `teacher.substitute_per_session_jod` are in JOD. 6.25 is stated as a fractional amount, and JOD uses fils with 3 decimals (14:12). `billing.currency` is USD and holds a currency code, not an amount. Prices are not settings keys: 72$ and 63$ come "من محرك التسعير لا من الكود" (00:19).

**Percentage keys:** `attendance.partial_below_pct`, `attendance.group_postpone_threshold`, `billing.family_discount_pct` (12.5), `teacher.night_premium_pct` and `reports.review_sample_pct`.

## Notifications

All rows are from `21-appendices.md`.

| event/trigger | recipients | channels | cite |
|---|---|---|---|
| account creation and verification | parent | email, SMS for the code | 21:45 |
| assessment or trial booked | parent | email with calendar file | 21:46 |
| reminder for assessment, trial or session (24h and 1h before) | parent; student in-app | email; WhatsApp in R2 | 21:47 |
| placement result | parent | email | 21:48 |
| post-trial follow-up with offer (after 24h) | parent | email | 21:49 |
| payment success and receipt | parent | email | 21:50 |
| payment failure (immediate and on every retry) | parent and finance | email and in-app | 21:51 |
| upcoming renewal or low balance (3 days before / at 2 sessions) | parent | email | 21:52 |
| subscription suspension | parent | email and in-app | 21:53 |
| assignment to a class | parent and teacher | email | 21:54 |
| session change or cancellation | parent and teacher | email and in-app | 21:55 |
| clock change due to DST (14 days before) | parent and teacher | email | 21:56 |
| teacher emergency absence (high priority) | operations | SMS and in-app | 21:57 |
| absence-request decision | parent | in-app | 21:58 |
| new assignment or due tomorrow | parent and student | in-app and email | 21:59 |
| assignment feedback published | parent | in-app | 21:60 |
| weekly indicators | parent | summary email, weekly | 21:61 |
| monthly report | parent | email and in-app, on publish | 21:62 |
| late report | teacher and supervisor | in-app and email | 21:63 |
| session recording available (if the parent opted in) | parent | in-app | 21:64 |
| support ticket reply | parent | email and in-app | 21:65 |
| payout statement ready (monthly) | teacher | email | 21:66 |
| child-protection report (top priority) | safeguarding officer | SMS and email | 21:67 |

Analytics events list (pseudonymous IDs, no names or contact data): 26 event names, 21:71.

## Webhooks

| source | events | storage/idempotency statements | cite |
|---|---|---|---|
| `/webhooks/payments/{provider}` and `/webhooks/video/{provider}` | event names not listed | The endpoints verify the signature ("تتحقق من التوقيع"). The event is saved raw in `webhook_events`, the endpoint replies within one second, and a background job then processes it. A duplicate, late or out-of-order event "لا يغير النتيجة". A failed event is retried with increasing delay, then enters a review queue. | 15:39 |
| general | every external event | "كل حدث خارجي يُعالج مرة واحدة فقط" | 15:4 |
| internal domain events (outbox) | `SessionEnded`, `AttendanceConfirmed`, `PaymentSucceeded` (examples) | Published through the outbox table "في المعاملة نفسها, فلا يضيع حدث". Table `outbox_events` is listed at 14:36. | 15:41 |
| `payments` entity | n/a | "provider_ref فريد لكل بوابة؛ لا معالجة مزدوجة" | 14:45 |

## Scheduled jobs

Idempotency: the spec gives no per-job idempotency statement. Only the general statements at 15:4 and 15:39 exist. The spec text names no tables for any job. I list only the nouns the text uses.

| job | frequency | what the text says it reads/writes | cite |
|---|---|---|---|
| session generation | daily | extends sessions of each active class to 8 weeks ahead | 15:47 |
| DST check | daily | detects sessions affected within 21 days and creates notifications | 15:48 |
| session reminders | every 5 min | 24h and 1h before, per family preferences | 15:49 |
| end-of-session processing | on event | proposes attendance from the join/leave log and creates a confirmation task | 15:50 |
| late attendance approval | hourly | approves the proposal after 12h and flags it to the supervisor | 15:51 |
| renewal | hourly | charges the saved card when the balance threshold is reached | 15:52 |
| failed-payment collection | daily | retries on days 1, 3 and 5, then suspends after the grace period | 15:53 |
| credit expiry | daily | writes expiry entries ("قيود الانتهاء") and warns the family a week before | 15:54 |
| report tasks | weekly and monthly | creates tasks and alerts on lateness | 15:55 |
| expired recordings deletion | daily | deletes from storage and "تسجل الحذف" | 15:56 |
| data retention | weekly | applies the retention schedule to files, leads and records | 15:57 |
| teacher payouts | 1st of each month | computes statements and sends them to finance for approval | 15:58 |
| waitlist matching | daily | alerts operations when 3 requests match | 15:59 |
| financial settlement | daily | imports gateway reports and reconciles them (R2) | 15:60 |

## API conventions with data impact

- **Idempotency-Key:** mandatory for creating a payment, a subscription, a refund and manual entries (15:12). The header intro says everything that changes money or balance accepts it (15:4). `payments.idempotency_key` is the only such field listed in 14:45.
- **IDs:** UUIDv7, time-ordered, "ولا تظهر أرقام متسلسلة في الروابط" (14:10).
- **Timestamps:** every response time is ISO 8601 UTC (15:17). Instants are `timestamptz` in UTC, and a class pattern is a local hour plus an IANA reference zone (14:11).
- **Pagination:** cursor-based, with filters and ordering defined per resource (15:14).
- **Error format:** one object with a stable `code`, an Arabic message and an English message, and field details (15:13).
- **Request id:** present in every response and log, and shown in error messages (15:16).
- **Authentication:** cookie sessions (HttpOnly, Secure, SameSite=Lax) plus a CSRF token for web, and a short-lived token for child mode (15:11).
- **Versioning:** `/api/v1`, with no breaking change within a version (15:10).
- **Rate limits:** per IP and per user, stricter on login, verification codes and public forms (15:15).

## External services holding our data

| service | data it receives/stores | region statements | cite |
|---|---|---|---|
| Video provider (Zoom Video SDK candidate) | sessions, recording, join/leave events | none stated | 16:8 |
| PayTabs Jordan | cards, recurring billing, 3DS, "تسوية بالعملة المحلية" | Jordan (by name) | 16:10 |
| Twilio Verify | OTP by SMS | none stated | 16:12 |
| Amazon SES | notifications and invoices | none stated | 16:13 |
| WhatsApp Business Platform (R2) | reminders and reports from approved templates | none stated | 16:14 |
| AWS hosting | the whole platform | "AWS في فرانكفورت", "منطقة أوروبية تسهل الامتثال لـ GDPR" | 16:15 |
| Managed PostgreSQL | all data | none stated; "نسخ يومية واستعادة لأي نقطة زمنية" | 16:16 |
| Managed Redis | background jobs and rate limits | none stated | 16:17 |
| Amazon S3, private buckets | assignments, recordings, media | none stated; signed links, automatic deletion per retention | 16:18 |
| Cloudflare Stream | recorded lesson video | none stated | 16:19 |
| Cloudflare | CDN, WAF, DNS, Turnstile | none stated | 16:20 |
| Gotenberg | invoices, reports, certificates (PDF) | none stated | 16:21 |
| ClamAV | every uploaded file, scanned before it is made available | none stated | 16:22 |
| Sentry | UI and server errors, "مع إخفاء البيانات الشخصية" | none stated | 16:23 |
| Better Stack | uptime, logs, alerts | none stated | 16:24 |
| PostHog | funnel and usage, "دون أي تتبع في واجهات الأطفال" | "على سحابته الأوروبية" | 16:25 |
| Secrets manager | keys and service passwords | none stated | 16:26 |
| Bank transfer file or CliQ | teacher payouts in Jordan, manual at first | none stated | 16:33 |
| Accounting software (CSV export) | monthly revenue, refund and payout entries | none stated | 16:34 |
| TTS Azure or Google (R3) | none stated | none stated | 16:35 |

## Data-model statements outside spec/14

- 13:4: "كل وحدة تملك جداولها، ولا تكتب في جداول وحدة أخرى إلا عبر خدماتها أو أحداثها."
- 13:10: the 12 modules are listed (identity and permissions, families and students, curriculum and assessment, scheduling and time, virtual classroom, attendance and credit ledger, billing and payments, assignments and reports, notifications and messages, teachers and payouts, settings and audit, jobs and queues).
- 13:11: "PostgreSQL مُدارة مع استعادة لحظية".
- 13:25: PostgreSQL chosen for "معاملات قوية، بحث نصي، حقول JSON".
- 13:28: search uses PostgreSQL full-text with Arabic letter normalization, "حذف التشكيل وتوحيد الهمزات والياء والتاء المربوطة في الفهرس".
- 13:29: localization uses ICU-format translation files.
- 00:4: "أي ميزة غير مذكورة هنا خارج النطاق حتى يُعتمد طلب تغيير موثق".
- 00:14: "تغيير أي منها بعد تصميم قاعدة البيانات يعني إعادة عمل، فيجب تأكيدها قبل نهاية الأسبوع السادس."
- 00:23: recordings are kept "30 يوماً للأسر و90 يوماً للإدارة، ثم حذف آلي".
- 00:28: "حساب يديره ولي الأمر ودخول برمز PIN، دون بريد إلكتروني للطفل".
- 00:30: credit model is "رصيد متجدد مستقل عن دورة الشعبة، يتجدد تلقائياً عند انخفاضه إلى حصتين".
- 15:39: raw events are stored in `webhook_events`.
- 15:41: domain events go through the outbox in the same transaction.
- 15:56 and 15:57: recordings are deleted from storage and the deletion is logged. A weekly retention job applies the retention schedule to files, leads and records.
- 16:16: daily backups and point-in-time restore.
- 16:18: private buckets, signed links, automatic deletion per retention.
- 21:4: every settings change is logged "بإصداره".
- 21:88: BR-013 (time) maps to "قواعد البيانات العامة".

## Ambiguities

1. **Table count.** 14:4 says "بنحو 90 جدولاً في 15 مجالاً". The domain table at 14:22-36 lists 15 domains and 110 table names, by my count.
2. **`created_by`/`updated_by` vs immutable tables.** 14:16 says "`created_by` و`updated_by` في كل جدول". 14:43 says `credit_ledger_entries` has "لا تعديل ولا حذف", and 14:46 says consents "لا تُعدّل". The spec does not say whether append-only tables carry `updated_by`.
3. **Idempotency fields.**
   - 15:12 makes `Idempotency-Key` mandatory for payment, subscription and refund creation and for manual ledger entries.
   - 14:45 gives only `payments` an `idempotency_key` field. The `subscriptions` row (14:44) has no such field, and `refunds` and manual ledger entries have none listed (14:43).
4. **Ledger unique key and nullable session.** 14:43 says "مفتاح فريد (طالب، جلسة، نوع)". `session_id` and `subscription_id` appear without any required/nullable marker. 15:54 says expiry writes entries, and 15:12 mentions manual entries. The spec does not say how those relate to the session-based unique key.
5. **Ledger and attendance enums not defined in 14.**
   - 14:43 gives `type` and `pool` (مدفوع، تجريبي) but lists no type values. 14:42 gives `status` and `outcome` without values.
   - Names appear only in 21:82-85 and 21:93 (`ABSENCE_UNEXCUSED`, `LATE`, `PARTIAL`, `POSTPONED_GROUP`, `HELD_NO_SHOW`, `CREDIT_TRIAL`).
   - 14:54 lists session states "مؤجلة" and "ملغاة", which overlap with `POSTPONED_GROUP` in 21:85.
6. **Subscription `started_at` vs renewing credit.**
   - 14:44: `started_at (أول حصة فعلية)`.
   - 21:79: "BR-003 الاستحقاق من أول حصة ... والتعديل إلى رصيد متجدد في قسم القرارات".
   - 00:30: "رصيد متجدد مستقل عن دورة الشعبة".
   - 14:74 has "نفاد الرصيد → مكتمل (completed)", while 21:23 and 15:52 describe auto-renew at 2 sessions.
7. **Settings values with no key.**
   - 14 days for the DST notification (21:56) has no key, while `scheduling.dst_alert_days` is 21 (21:37, 15:48).
   - 3 days before renewal (21:52), one week before credit expiry (15:54) and the 24h trial follow-up (21:49) have no key.
   - 14:77 hardcodes "بعد 5 أيام" while `billing.grace_period_days` is 5 (21:24). The values agree, but the diagram text is not key-based.
8. **Tables the text implies but 14 does not list.**
   - 15:33 and 15:49 refer to notification and reminder preferences ("التفضيلات", "حسب تفضيلات كل أسرة"). 14:34 has no preferences table.
   - 21:71 has `referral_shared` and `referral_redeemed`, and 14 has no referral table.
   - 14:13 and 15:57 refer to a retention schedule. No table for it is listed.
   - 15:35 mentions export under Admin. No table is listed.
   - 21:64 mentions a parent opt-in for recording-ready notices. No field is named.
9. **`webhook_events` domain.** 14:32 lists `webhook_events` under Billing (الفوترة). 15:39 uses the same table for `/webhooks/video/{provider}` events. `participant_events` is listed under the virtual classroom (14:30).
10. **`settlements` and R-level.** 15:60 says the settlement job is R2. 14:32 lists `settlements` in the table list with no release marking.
11. **Currency.**
    - 00:27: "الدولار فقط عند الإطلاق، والمحاسبة الداخلية بالدولار". 21:28: `billing.currency` = USD.
    - 16:10 says PayTabs offers "تسوية بالعملة المحلية".
    - 14:12 and 21:30-32 hold teacher pay in JOD.
12. **Video provider.** 16:8 recommends Zoom Video SDK. 00:22 says it is "يُحسم بعد تجربة تقنية لأسبوعين". 13:32 says it is decided by ADR-005. 13:12 and 13:15 name it as "المرشح".
13. **Deletion model.** 14:4 says finance, attendance and reports are "لا تُحذف نهائياً أبداً". 14:13 allows permanent deletion "فقط بطلبات الخصوصية وجدول الاحتفاظ". 15:57 applies retention to "الملفات والعملاء المحتملين والسجلات", and the spec does not say which records.
14. **Trial setting.** 21:14 gives `trial.per_child` as "1، مع حصة ثانية بقرار العمليات". The default is a count of 1, and the second session is not a numeric value.
