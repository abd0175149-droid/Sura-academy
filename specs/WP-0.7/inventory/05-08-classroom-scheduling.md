<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory

Notes that apply to the whole table:
- Spec/14 lists column names only for `sessions`, `credit_ledger_entries`, `subscriptions`, `payments`, `consents` and `enrollment_history` (14:42-47). Every column I list for other tables therefore carries `*`.
- None of the settings the text refers to has a key name in these two files. I write "(setting, key not named)" where the text says "إعداد", and "(number in text, not marked as setting)" for the other numbers.

| ID | Feature | Data | Tables | Columns/fields | States / enums | Settings | Cite |
|---|---|---|---|---|---|---|---|
| CLS-01 | Embedded browser video and audio | N | (live_rooms: provider session ref, inferred) | `*provider_room_ref` (inferred) | none | none | 05:8 |
| CLS-02 | Join window around the session | Y | sessions, live_rooms | `starts_at_utc`, `ends_at_utc` (14:42); `*join_opens_at` (inferred, derived) | none | join-window minutes, default 10 (setting, key not named) | 05:9 |
| CLS-03 | Server-issued short-lived join token | Y | join_token_log | `*user_id`, `*session_id`, `*role`, `*issued_at`, `*expires_at`, `*token_hash` (inferred) | none | token lifetime (not stated) | 05:10 |
| CLS-04 | In-room roles and host controls | Y | participant_events, live_rooms | `*role` (host/participant); `*locked` (inferred, "قفل الغرفة"); `*screen_share_allowed` (inferred) | roles: host (teacher), participant (student) | none | 05:11 |
| CLS-05 | Block outsiders | Y | class_enrollments, sessions, join_token_log | `class_id`, `actual_teacher_id` (14:42); the check allows only enrolled students, the teacher, the assigned substitute and the supervisor | none | none | 05:12 |
| CLS-06 | Screen and audio sharing | Y | participant_events | `*event_type` = screen share start/stop (inferred from 05:33) | none | none | 05:13 |
| CLS-07 | In-session public chat | Y | NEW:session_chat_messages (spec/14 `messages` sits in the communication domain; see Gaps) | `*session_id`, `*sender_user_id`, `*body`, `*sent_at`; `*chat_enabled` on live_rooms (inferred) | chat on/off | none | 05:14 |
| CLS-08 | Hand raise, reactions, self-mute | N | (participant_events if logged, not stated) | none | none | none | 05:15 |
| CLS-09 | Automatic attendance from provider events | Y | participant_events, session_attendance, webhook_events (?) | `*joined_at`, `*left_at`, `*attendance_minutes`, `*suggested_status`, `*confirmed_by`, `*confirmed_at` (inferred) | the teacher confirms the suggested status; status values not stated here | none | 05:16 |
| CLS-10 | Cloud recording with signed links | Y | recordings, recording_access_log, consents, files | `*session_id`, `*storage_key` (private), `*started_at`, `*consent_basis`; log: `*viewer_user_id`, `*accessed_at`, `*signed_url_expires_at` | recording starts when all class families consent | signed-link lifetime (not stated) | 05:17 |
| CLS-11 | Teacher workspace beside video | Y | session_attendance, lesson_plans, reports/report_items | `*session_notes` (teacher notes, no home; see Gaps); `*end_of_session_checklist` (inferred) | none | none | 05:18 |
| CLS-12 | Emergency report button | Y | safeguarding_incidents | `*reporter_teacher_id`, `*session_id`, `*category` (technical issue / child-protection concern), `*created_at`, `*routed_to` (inferred) | category: technical, safeguarding | none | 05:19 |
| CLS-13 | Substitute and supervisor entry | Y | join_token_log, sessions | token scoped to the specific session; `sessions.actual_teacher_id`, `type`; supervisor display name «مشرفة» (`*display_label`, inferred) | session type includes بديلة (14:42) | none | 05:20 |
| CLS-14 | Weak-connection mode | N | none | none | none | none | 05:21 |
| CLS-15 | Fallback link when embed fails | Y | live_rooms | `*fallback_url`, `*fallback_reason`, `*fallback_created_by`, `*fallback_created_at` | none | none | 05:22 |
| SCH-01 | Class entity | Y | classes, class_schedule_rules | `*code`, `*program_id`, `*level_id`, `*age_min`, `*age_max`, `*capacity`, `*capacity_override_reason`, `*capacity_override_by`, `*teacher_id`, `*start_date`, `*status`; rule: `*weekday`, `*local_time`, `*ref_timezone` | capacity 2 to 4, override needs supervisor permission and a reason | capacity bounds 2/4 (numbers in text, not marked as setting) | 08:8 |
| SCH-02 | Class states | Y | classes | `status` | مسودة → مفتوحة للتسجيل → نشطة → موقوفة → مكتملة | none | 08:9 |
| SCH-03 | Published schedule | Y | classes, class_enrollments, families/students | query: open classes, remaining seats (`capacity` minus enrolled), eligibility by level, age and zone (`*family_timezone` inferred) | none | none | 08:10 |
| SCH-04 | Waitlist | Y | waitlist_entries | `*family_id`, `*student_id`, `*level_id`, `*age` (or birth-derived), `*time_window`, `*status` (inferred); ops alert at 3 compatible students | none stated | match threshold 3 (number in text) | 08:11 |
| SCH-05 | Rolling session generation, 8 weeks | Y | sessions, class_schedule_rules | `local_start`, `ref_timezone`, `starts_at_utc`, `ends_at_utc` (14:42); `*generated_through` (inferred); idempotent generation (inferred) | none | horizon 8 weeks (number in text, not marked as setting) | 08:12 |
| SCH-06 | DST check | Y | sessions, notifications | daily query: sessions whose Amman time changes within 21 days; `*dst_notice_sent_at` (inferred) | none | 21 days, 2 weeks (numbers in text) | 08:13 |
| SCH-07 | Prevent DST-zone mixing | Y | class_enrollments, classes | `classes.ref_timezone`, family zone; `*dst_mismatch_confirmed_by`, `*dst_mismatch_confirmed_at` (inferred, "تأكيد صريح") | none | none | 08:14 |
| SCH-08 | Conflict detection | Y | sessions, teacher_availability, classes, student_availability, guardians/students | no teacher overlap (14:42); outside availability; over capacity; sibling overlap on one device (no device field in scope) | none | none | 08:15 |
| SCH-09 | Reschedule and change | Y | session_changes, change_requests, sessions, class_schedule_rules | `*session_id`, `*change_scope` (one session / pattern going forward), `*old_starts_at_utc`, `*new_starts_at_utc`, `*actor_id`, `*reason`, `*impact_preview` (inferred) | session status مؤجلة (14:54) | none | 08:16 |
| SCH-10 | Cancellation with reason | Y | sessions, session_changes, credit_ledger_entries | `*cancel_reason`; per-reason credit effect | reasons: teacher absence, holiday, technical fault, mass postponement | none | 08:17 |
| SCH-11 | Find a substitute | Y | teachers, teacher_availability, teacher_leaves, sessions | query: available, qualified for the level, no conflict; `*teacher_level_qualification` (no home; see Gaps) | none | none | 08:18 |
| SCH-12 | Holiday calendar | Y | calendar_exceptions, sessions, session_changes | `*date_from`, `*date_to`, `*kind`, `*name`, `*scope` (inferred); mass cancel and automatic compensation | kinds: Jordan official holiday, religious feast, academy leave | none | 08:19 |
| SCH-13 | Calendars per teacher, class, student, ops | N | sessions (read view) | filters only; no new storage | none | none | 08:20 |
| SCH-14 | Transfer between classes | Y | class_enrollments, enrollment_history, session_attendance, credit_ledger_entries | `*effective_date`, `*ended_at` / `*closed_at` on old membership, new membership row; `enrollment_history`: `student_id`, `from_state`, `to_state`, `actor_id`, `reason`, `occurred_at` (14:47) | none | none | 08:21 |
| SCH-16 | Session length by age | Y | settings, classes/sessions | `ends_at_utc` minus `starts_at_utc` = duration; `*duration_minutes` per age range (inferred) | 60, 75 or 90 minutes | age-range duration setting (key not named) | 08:23 |

## Gaps

- **Chat storage (CLS-07).** Quote: "عامة فقط … ومحفوظة في السجل" (05:14). The spec/14 table list has no per-session chat table. `messages` sits with `support_threads` in the communication domain (14:34).
- **Teacher session notes (CLS-11).** Quote: "قائمة الطلاب مع أزرار الحضور، خطة الدرس، الملاحظات، مؤشرات التقرير السريعة، قائمة إنهاء الحصة" (05:18). There is no table or field for session notes or the end-of-session checklist.
- **Provider events (CLS-09).** Quote: "أحداث الدخول والخروج من المزود" (05:16). `webhook_events` is listed under billing (14:32), not the virtual classroom. No table for video-provider webhooks or event idempotency.
- **Attendance minutes and suggested status (CLS-09).** Quote: "تُحسب دقائق الحضور وتقترح الحالة، والمعلمة تؤكدها" (05:16). `session_attendance` has no listed columns, so no home for minutes, suggested status or confirmation is defined.
- **Emergency report routing (CLS-12).** Quote: "يصل فوراً للعمليات أو مسؤول الحماية" (05:19). `safeguarding_incidents` exists (14:36) but has no listed fields for session link or category.
- **Teacher qualification per level (SCH-11).** Quote: "المعلمات المتاحات والمؤهلات للمستوى" (08:18). No table or field maps teachers to levels.
- **Capacity override (SCH-01).** Quote: "تجاوزها بصلاحية المشرف وسبب" (08:8). There is no listed field for override reason or by whom.
- **Device identity (SCH-08).** Quote: "تداخل حصص الإخوة على جهاز واحد" (08:15). `device_authorizations` is in the identity domain (14:22). Whether it can drive this check is not stated.
- **Family time zone (SCH-03, SCH-07).** Eligibility "حسب … المنطقة" (08:10) and the DST-mixing rule (08:14) need a family or student zone. `families` has no listed fields.
- **Waitlist time window (SCH-04).** Quote: "مستوى وعمر ونافذة زمنية" (08:11). `waitlist_entries` has no listed fields. `student_availability` (14:29) may overlap with it, which the text does not say.
- **Seat reservation (SCH-04 flow).** Quote: "دعوة بمقعد محجوز لمدة 48 ساعة" (08:32). No table for the reservation or its expiry.
- **Compensation sessions (SCH-12).** Quote: "إلغاء جماعي وتعويض تلقائي" (08:19). Nothing links a compensation session to the cancelled one.
- **Participant events (05:33).** Quote: "كل دخول وخروج ومشاركة شاشة وتسجيل يُحفظ في سجل الحصة". `participant_events` has no listed columns or event-type list.

## Personal data and retention

No retention period is stated in either file. Short lifetimes are stated only for tokens, signed links, the seat reservation and the room.

- Student and teacher identity in join tokens (`user_id`, session, role): "رمز قصير العمر" (05:10). Lifetime value not stated.
- Join and leave events, screen shares and recording events per participant, which are child behavioural data: "يُحفظ في سجل الحصة" (05:33), 05:16. No retention stated.
- Cloud recordings of children's video and audio: "يُخزن خاصاً، ويُعرض بروابط موقعة قصيرة العمر" (05:17). Retention not stated. Consent of every family in the class is required to start recording.
- Public chat content by children: "محفوظة في السجل" (05:14). Retention not stated.
- Teacher notes about students (05:18). Retention not stated.
- Emergency and child-protection reports (05:19). Retention not stated.
- Fallback link reason ("مع تسجيل السبب", 05:22). Whether it can contain personal data is not stated.
- Waitlist: child level, age and availability window (08:11). Retention not stated.
- Student, family and class membership with attendance and credit history preserved on transfer (08:21). Retention not stated.
- Supervisor shown by role name «مشرفة», not hidden (05:20).
- Short lifetimes the text does state:
  - Room stays open 10 minutes after the end (05:34).
  - Join opens 10 minutes before the start (05:9).
  - Seat reservation lasts 48 hours (08:32).

## Append-only, audit, money, time

- Session instants in UTC and the pattern as local time plus reference zone: 08:4, 08:12, 08:25; 14:11, 14:42.
- No hardcoded offsets, and the IANA tz database is kept updated: 08:25. Tests run across the 13 listed zones (08:25).
- Amman is fixed at UTC+3 all year: 08:4.
- Session history changes: SCH-09 "تغيير جلسة واحدة أو النمط للجلسات القادمة" (08:16), with `session_changes` and `change_requests` (14:29).
- Cancellation reasons carry specific credit effects (08:17). Credit effects must be ledger entries, never edits (14:43: "لا تعديل ولا حذف").
- Unique `(student, session, type)` ledger key and no teacher overlap: 14:42-43. SCH-10, SCH-12 and SCH-14 depend on them.
- A trial-type session must not touch the paid balance: 14:42.
- Transfers write `enrollment_history` with every state transition: 08:21, 14:47. Attendance, credit and history are preserved.
- Audit trail of room events (join, leave, screen share, recording): 05:33.
- Recording access is logged in `recording_access_log` (14:30). The text states no requirement for it.
- Join tokens are logged in `join_token_log` (14:30).
- Capacity override and DST-mix confirmation need an actor and reason: 08:8, 08:14. Consistent with `created_by`/`updated_by` and the append-only `audit_logs` (14:16).
- No money amounts appear in these two files. SCH-10 credits are quantities, not money.

## Ambiguities

- **Class state name.** SCH-02 says "مفتوحة للتسجيل" (08:9). Spec/14 says "مفتوحة" (14:53). The enum value is unclear.
- **Session lifecycle.** Spec/14 lists "مجدولة → مباشرة → منعقدة، أو ملغاة، أو مؤجلة" (14:54) and separate `status` and `outcome` fields (14:42). The `outcome` values are not stated. Which of these states is a status and which is an outcome is unclear.
- **Postponed session.** SCH-09 says "تغيير جلسة واحدة" (08:16) and the session state is "مؤجلة" (14:54). The text does not say whether a postponement edits the session or creates a new session row linked to the old one.
- **Seat counts.**
  - Capacity is "2 إلى 4" (08:8).
  - Waitlist alerts at 3 compatible students (08:11).
  - The class is published once 2 students confirm (08:33).
  - It is unclear whether 2 is the minimum viable size or only the publish threshold.
- **Business numbers as constants or settings.**
  - Settings are explicit only for the join window ("إعداد", 05:9) and the duration by age range (08:23).
  - These are stated as plain numbers: 8 weeks (08:12), 21 days and 2 weeks (08:13), threshold 3 (08:11), 48 hours (08:32), teacher lateness 5 minutes (05:31), room close 10 minutes (05:34).
  - The text does not say whether these must also be settings.
- **Room closing vs join window.**
  - "تُغلق بعد نهايتها" (05:9) and "عند انتهاء الوقت تبقى الغرفة مفتوحة عشر دقائق ثم تُغلق تلقائياً" (05:34) are consistent.
  - Only the pre-start 10 minutes is flagged as a setting. Whether the post-end 10 minutes is the same setting is unstated.
- **Chat history.** CLS-07 says "محفوظة في السجل" (05:14) and 05:33 lists join, leave, screen share and recording in "سجل الحصة". Chat is not in the 05:33 list, so it is unclear whether chat lives in the same log.
- **Teacher lateness.** "تأخر المعلمة خمس دقائق ينبه العمليات" (05:31) needs a stored actual start. `sessions` has no such field, and `actual_teacher_id` and `status` exist (14:42).
- **Recording consent.** "يبدأ تلقائياً عند موافقة كل أسر الشعبة" (05:17). `consents` is per `user_id`/`student_id` with `purpose` (14:46). The purpose name and the per-class aggregation are not stated. What happens when a family withdraws consent mid-term is also not stated.
- **Waitlist eligibility.** SCH-03 filters by "المستوى والعمر والمنطقة" (08:10). SCH-07 forbids mixing "نظام توقيت صيفي مختلف" (08:14). It is unclear whether "المنطقة" means the exact IANA zone or the DST regime.
- **Table count.** Spec/14 says about 90 tables (14:4). These two files add several without a table: chat, session notes and the seat reservation.

## R2/R3 rows with data impact (one line each)

- CLS-16 — whiteboard and writing over shared content, with real-time sync — NEW:whiteboard_state (inferred; no spec/14 table)
- CLS-19 — in-session stars shown in student progress — NEW:star_awards (inferred; no spec/14 table)
- CLS-20 — watermark on recordings at display time — recordings, recording_access_log
- SCH-15 — automatic matching suggestion for waitlist students — waitlist_entries

CLS-17 and CLS-18 (internal slide viewer, activity timer) have no data impact.

Files read (all absolute):
- /home/abdul/sura-academy/docs/01-product/spec/14-data-model-and-states.md
- /home/abdul/sura-academy/docs/01-product/spec/05-live-classroom.md
- /home/abdul/sura-academy/docs/01-product/spec/08-scheduling-and-timezones.md
