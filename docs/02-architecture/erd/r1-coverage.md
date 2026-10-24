# تغطية متطلبات R1 بالبيانات

كل متطلب R1 في `traceability.md` له صف واحد هنا:
- `Y`: له أثر في البيانات، ومعه الأعمدة التي تخدمه.
- `N`: لا يحتاج بيانات محفوظة، ومعه السبب.

المصدر استخراج وكلاء `spec-reader` لكل ملف مواصفات في `specs/WP-0.7/inventory/`. الفاحص يتحقق أن كل معرّف موجود مرة واحدة، وأن كل عمود مذكور موجود في ملفات الوحدات. أُضيف OUT-01 وOUT-02 لأن D-26 يعتمد أساسهما البياني في R1.

| ID | Data | Columns | Note |
| --- | --- | --- | --- |
| ADM-01 | Y | `class_sessions.status`, `class_sessions.actual_started_at`, `participant_events.event_type`, `attendance_exceptions.status`, `subscriptions.status`, `payments.status`, `trials.status` | live dashboard reads these; no own table |
| ADM-02 | Y | `families.owner_user_id`, `family_notes.body`, `taggables.tag_id`, `follow_up_tasks.status`, `leads.stage`, `lead_events.kind` | timeline is the union of these rows |
| ADM-03 | Y | `students.suspended_at`, `students.archived_at`, `placements.status`, `enrollment_history.to_state` | suspend without delete |
| ADM-04 | Y | `teachers.status`, `teacher_availability.weekday`, `teacher_documents.expires_on`, `compensation_statements.status` | quality indicators are R2 (TOP-04) |
| ADM-05 | Y | `classes.status`, `class_schedule_rules.local_start`, `class_sessions.starts_at`, `waitlist_entries.status`, `calendar_exceptions.status` | |
| ADM-06 | Y | `assessment_slots.status`, `assessments.status`, `assessment_scores.score`, `placements.status`, `trials.status` | conversion is derived |
| ADM-07 | Y | `content_items.status`, `media_assets.licence_expires_on`, `lesson_plans.status` | |
| ADM-08 | Y | `payments.reviewed_by`, `payments.transfer_proof_file_id`, `invoices.total_minor`, `refunds.status`, `wallet_entries.amount_minor`, `audit_logs.action` | exports are logged as `export.*` audit actions |
| ADM-09 | Y | `credit_ledger_entries.type`, `credit_ledger_entries.reason_code`, `record_notes.body`, `attendance_exceptions.kind` | manual entries carry a reason |
| ADM-10 | Y | `reports.selected_for_review`, `report_reviews.decision`, `report_reviews.comment` | |
| ADM-11 | Y | `message_templates.key`, `message_template_versions.body_ar`, `announcements.target_type`, `delivery_logs.status` | |
| ADM-12 | Y | `settings.value`, `setting_versions.version_no`, `setting_versions.changed_by`, `setting_versions.effective_at` | |
| ADM-13 | Y | `audit_logs.actor_id`, `audit_logs.action`, `audit_logs.entity_type`, `audit_logs.entity_id`, `audit_logs.occurred_at` | read-only search |
| ADM-14 | Y | `safeguarding_incidents.description`, `safeguarding_incidents.status`, `safeguarding_incident_actions.action_taken` | access restricted by role |
| ADM-15 | Y | `features.name`, `features.scope`, `features.value` | Pennant |
| ASM-01 | Y | `placement_quiz_items.prompt`, `placement_quiz_items.media_asset_id`, `quiz_attempts.answers`, `quiz_attempts.score`, `quiz_attempts.suggested_level_min_id`, `quiz_attempts.suggested_level_max_id` | |
| ASM-02 | Y | `assessment_scores.criterion`, `assessment_scores.score`, `assessments.recommendation` | six criteria, 1–5 |
| ASM-03 | Y | `placements.level_id`, `placements.status`, `placements.approved_by`, `placements.approved_at` | BR-017 |
| ATT-01 | Y | `session_attendance.suggested_status`, `session_attendance.status`, `session_attendance.confirmed_by`, `session_attendance.confirmed_at`, `session_attendance.auto_approved_at` | |
| ATT-02 | Y | `absence_requests.requested_at`, `absence_requests.within_cutoff`, `class_sessions.starts_at` | compared in UTC |
| ATT-03 | Y | `absence_requests.within_cap`, `session_attendance.status` | D-06 sliding window of 8 |
| ATT-04 | Y | `credit_ledger_entries.rule_version_id`, `rule_versions.snapshot` | |
| ATT-05 | Y | `session_attendance.locked_at`, `session_attendance.ledger_entry_id`, `credit_ledger_entries.reverses_entry_id`, `credit_ledger_entries.reason_code`, `record_notes.body` | |
| ATT-06 | Y | `attendance_exceptions.kind`, `attendance_exceptions.status` | |
| ATT-07 | Y | `credit_ledger_entries.qty`, `credit_ledger_entries.pool`, `credit_balance_snapshots.balance` | balance = ledger sum |
| ATT-08 | Y | `class_sessions.ledger_processed_at`, `credit_ledger_entries.idempotency_key`, `outbox_events.id` | plus the session-entry unique key |
| ATT-09 | Y | `class_enrollments.kind`, `credit_ledger_entries.pool`, `compensation_lines.paid_student_count` | D-08: trial is a seat, not a session |
| BIL-01 | Y | `plans.sessions_per_cycle`, `plans.cycle_weeks`, `subscriptions.student_id` | one student per subscription (D-01) |
| BIL-02 | Y | `prices.amount_minor`, `prices.currency`, `prices.valid_from_at`, `prices.valid_to_at`, `subscriptions.price_id` | |
| BIL-03 | Y | `discount_rules.percent_bps`, `discount_rules.applies_from_child_index`, `invoice_lines.discount_rule_id` | D-01 |
| BIL-04 | Y | `coupons.kind`, `coupons.discount_type`, `coupons.max_redemptions`, `coupons.first_cycle_only`, `coupons.expires_at`, `coupon_redemptions.coupon_id` | referral codes are `kind = referral` |
| BIL-05 | Y | `payment_methods.provider_token_ref`, `payment_methods.wallet_type`, `payments.three_ds_result`, `payments.method` | card data never stored |
| BIL-06 | Y | `subscriptions.status`, `subscription_events.event`, `subscription_events.to_status` | nine states |
| BIL-07 | Y | `subscriptions.auto_renew`, `subscriptions.renew_trigger_balance`, `subscriptions.renewal_reminded_at`, `subscriptions.renewal_not_before_at` | D-07 |
| BIL-08 | Y | `payments.attempt_no`, `payments.purpose`, `subscriptions.grace_ends_at`, `subscriptions.suspended_at` | |
| BIL-09 | Y | `payments.method`, `payments.transfer_proof_file_id`, `payments.reviewed_by`, `payments.reviewed_at` | field-trial payment path (D-36) |
| BIL-10 | Y | `payment_allocations.payment_id`, `payment_allocations.subscription_id`, `payment_allocations.amount_minor` | |
| BIL-11 | Y | `invoices.number`, `invoices.issuer_snapshot`, `invoice_lines.amount_minor`, `invoices.pdf_ar_file_id`, `invoices.pdf_en_file_id` | |
| BIL-12 | Y | `refunds.kind`, `refunds.channel`, `refunds.reason_code`, `refunds.market`, `refunds.status` | |
| BIL-13 | Y | `wallet_entries.amount_minor`, `wallet_entries.currency`, `wallet_entries.expires_at`, `wallet_entries.source_ledger_entry_id` | D-28 |
| BIL-14 | Y | `subscriptions.cancelled_at`, `subscriptions.paused_until_at`, `subscription_events.event`, `credit_lot_extensions.resumed_at` | D-27 freeze through operations |
| BIL-15 | Y | `payments.provider`, `payment_methods.provider` | interface; `fake` until WP-2.5 (D-36) |
| BIL-16 | Y | `webhook_calls.payload`, `webhook_calls.event_ref`, `webhook_calls.processed_at`, `webhook_calls.attempts` | |
| CLS-01 | Y | `live_rooms.provider`, `live_rooms.provider_room_ref` | |
| CLS-02 | Y | `live_rooms.opens_at`, `live_rooms.closes_at` | |
| CLS-03 | Y | `join_token_log.token_hash`, `join_token_log.expires_at` | |
| CLS-04 | Y | `join_token_log.role`, `live_rooms.chat_enabled` | |
| CLS-05 | Y | `class_enrollments.status`, `class_sessions.actual_teacher_id`, `join_token_log.participant_id` | tokens only for allowed people |
| CLS-06 | Y | `participant_events.event_type` | screen share events |
| CLS-07 | Y | `session_chat_messages.body`, `live_rooms.chat_enabled` | public only |
| CLS-08 | N | — | hand raise, reactions and self-mute are live room signals; nothing stored |
| CLS-09 | Y | `participant_events.occurred_at`, `session_attendance.attended_minutes`, `session_attendance.suggested_status` | |
| CLS-10 | Y | `recordings.file_id`, `recordings.started_at`, `recording_access_log.signed_url_expires_at`, `consents.purpose` | D-11 |
| CLS-11 | Y | `class_sessions.teacher_notes`, `lesson_plans.activities`, `session_attendance.status` | |
| CLS-12 | Y | `safeguarding_incidents.channel`, `safeguarding_incidents.session_id`, `support_threads.category` | technical reports go to support |
| CLS-13 | Y | `join_token_log.role`, `class_sessions.actual_teacher_id` | |
| CLS-14 | N | — | adaptive video quality in the client |
| CLS-15 | Y | `live_rooms.fallback_url`, `live_rooms.fallback_reason`, `live_rooms.fallback_created_by` | |
| CNT-01 | Y | `stages.program_id`, `levels.stage_id`, `curriculum_units.level_id`, `lessons.curriculum_unit_id`, `content_items.lesson_id` | |
| CNT-02 | Y | `content_items.item_type`, `content_items.body`, `content_items.media_asset_id`, `content_items.external_url` | |
| CNT-03 | Y | `content_items.audience` | |
| CNT-04 | Y | `lesson_plans.objectives`, `lesson_plans.vocabulary`, `lesson_plans.activities`, `lesson_plans.materials`, `lesson_plans.suggested_homework` | |
| CNT-05 | Y | `content_items.audience` | `teacher` audience |
| CNT-06 | Y | `content_items.status`, `content_items.archived_at`, `lessons.status` | archive, never delete |
| CNT-07 | Y | `media_assets.licence_source`, `media_assets.licence_type`, `media_assets.licence_expires_on`, `taggables.tag_id` | |
| CNT-08 | N | — | fonts and diacritics toggle are display settings; text is stored verbatim with diacritics |
| CNT-09 | Y | `learning_outcomes.statement_ar`, `learning_outcomes.level_id` | |
| HW-01 | Y | `assignments.instructions_ar`, `assignments.submission_type`, `assignments.due_at`, `assignments.lesson_id` | |
| HW-02 | Y | `submissions.submission_type`, `submissions.text_body`, `submissions.audio_seconds`, `submission_files.file_id` | |
| HW-03 | Y | `submissions.version_no`, `assignments.closes_at` | |
| HW-04 | Y | `feedback.score`, `feedback.rubric`, `feedback.note`, `feedback.published_at` | |
| HW-05 | Y | `assignments.closes_at`, `submissions.is_late`, `assignments.reminder_sent_at` | |
| HW-06 | Y | `files.metadata_stripped_at` | |
| MSG-01 | Y | `support_threads.category`, `support_threads.assignee_id`, `support_messages.is_internal`, `support_threads.sla_due_at`, `support_threads.csat_score` | |
| MSG-02 | Y | `support_threads.priority`, `support_threads.sla_due_at`, `support_threads.first_response_at` | |
| MSG-03 | Y | `announcements.audience`, `announcements.body_ar` | |
| NOT-01 | Y | `message_templates.key`, `users.locale`, `users.timezone`, `delivery_logs.deferred_until_at`, `notification_preferences.quiet_hours_start` | |
| NOT-02 | Y | `users.email`, `delivery_logs.channel`, `suppressed_recipients.address_hash` | |
| NOT-03 | Y | `notifications.data`, `notifications.read_at` | |
| NOT-04 | Y | `users.phone_e164`, `message_templates.priority`, `delivery_logs.channel` | |
| NOT-07 | Y | `message_template_versions.version_no`, `message_template_versions.body_ar`, `message_template_versions.body_en`, `message_template_versions.variables` | |
| NOT-08 | Y | `announcements.target_type`, `announcements.class_id`, `announcements.level_id`, `announcements.country_code`, `delivery_logs.announcement_id` | |
| NOT-09 | Y | `delivery_logs.status`, `delivery_logs.attempt_no`, `suppressed_recipients.reason` | |
| ONB-01 | Y | `users.name`, `users.email`, `users.phone_e164`, `users.timezone`, `users.locale`, `families.country_code`, `consents.legal_document_version_id` | |
| ONB-02 | Y | `users.email_verified_at`, `users.phone_verified_at` | OTP codes live at Twilio Verify |
| ONB-03 | Y | `students.first_name`, `students.birth_month`, `students.birth_year`, `student_profiles.primary_language`, `student_profiles.arabic_level_estimate`, `student_profiles.self_assessment` | no photo, no child email |
| ONB-04 | Y | `student_profiles.notes` | warning is in the form |
| ONB-05 | Y | `quiz_attempts.score`, `quiz_attempts.suggested_level_min_id` | |
| ONB-06 | Y | `placements.source`, `assessments.status` | |
| ONB-07 | Y | `assessment_slots.starts_at`, `assessment_slots.meeting_ref`, `assessments.assessment_slot_id` | |
| ONB-08 | Y | `trials.class_enrollment_id`, `trials.session_id`, `class_enrollments.kind`, `waitlist_entries.class_id` | D-08 |
| ONB-09 | Y | `trials.attempt_no`, `trials.duplicate_review_status`, `students.first_name_normalized`, `users.phone_verified_at`, `payment_methods.fingerprint` | D-09 |
| ONB-10 | Y | `subscriptions.status`, `coupon_redemptions.coupon_id`, `invoice_lines.discount_rule_id` | |
| ONB-11 | N | — | progress tracker is derived from existing rows (account, child, placement, trial, subscription) |
| ONB-12 | Y | `family_invitations.token_hash`, `family_invitations.accepted_at`, `families.ops_assisted`, `users.password` | |
| ONB-13 | Y | `student_availability.weekday`, `student_availability.local_start`, `student_availability.local_end`, `student_availability.timezone` | D-37 |
| OUT-01 | Y | `learning_outcomes.code`, `learning_outcomes.statement_ar`, `learning_outcomes.variety`, `learning_outcomes.actfl_level`, `learning_outcomes.cefr_level` | CR-002 data basis (D-26) |
| OUT-02 | Y | `outcome_alignments.learning_outcome_id`, `outcome_alignments.alignable_type`, `outcome_alignments.weight` | CR-002 data basis (D-26) |
| PAR-01 | Y | `class_enrollments.status`, `class_sessions.starts_at`, `credit_balance_snapshots.balance`, `notifications.read_at` | reads only |
| PAR-02 | Y | `students.family_id`, `students.first_name`, `student_profiles.notes` | |
| PAR-03 | Y | `placements.status`, `session_attendance.status`, `reports.published_at`, `feedback.published_at`, `recordings.family_visible_until_at` | |
| PAR-04 | Y | `class_sessions.starts_at`, `class_sessions.ref_timezone`, `users.timezone` | shown in the family's zone |
| PAR-05 | Y | `absence_requests.status`, `absence_requests.within_cutoff`, `absence_requests.within_cap` | |
| PAR-06 | Y | `change_requests.request_type`, `change_requests.status` | |
| PAR-07 | Y | `subscriptions.status`, `subscriptions.auto_renew`, `credit_balance_snapshots.balance` | |
| PAR-08 | Y | `payments.status`, `payments.transfer_proof_file_id`, `invoices.pdf_ar_file_id`, `payment_methods.last4` | |
| PAR-09 | Y | `subscriptions.cancelled_at`, `subscriptions.cancel_reason`, `refunds.status` | |
| PAR-10 | Y | `reports.kind`, `reports.acknowledged_at`, `reports.pdf_file_id` | |
| PAR-11 | Y | `support_messages.body`, `notifications.read_at` | |
| PAR-12 | Y | `notification_preferences.event_category`, `notification_preferences.channel`, `notification_preferences.enabled`, `notification_preferences.quiet_hours_start`, `notification_preferences.timezone` | |
| PAR-13 | Y | `consents.purpose`, `consents.withdrawn_at`, `data_requests.request_type`, `data_requests.status` | |
| PAR-14 | Y | `student_pins.pin_hash`, `device_authorizations.device_key_hash`, `personal_access_tokens.expires_at` | exit uses the guardian's password |
| PAY-01 | Y | `compensation_lines.session_id`, `compensation_lines.class_monthly_pay_minor`, `compensation_lines.is_substitute`, `class_sessions.actual_teacher_id` | D-02 |
| PAY-02 | Y | `compensation_rules.base_minor`, `compensation_rules.per_student_minor`, `compensation_lines.paid_student_count` | D-03 |
| PAY-03 | Y | `compensation_lines.kind`, `compensation_lines.rule_version_id` | night premium line from settings |
| PAY-04 | Y | `compensation_lines.kind` | `urgent_cover`; amount rule open for WP-2.6 |
| PAY-05 | Y | `compensation_lines.reason`, `compensation_lines.approved_by`, `audit_logs.action` | |
| PAY-06 | Y | `compensation_statements.status`, `compensation_statements.period_month_on`, `payouts.export_file_id`, `payouts.payment_ref` | D-04 |
| PAY-07 | Y | `compensation_rules.scope_type`, `compensation_rules.teacher_id`, `compensation_rules.class_id`, `compensation_rules.reason` | |
| PRV-01 | Y | `legal_documents.document_type`, `consents.legal_document_version_id` | children's privacy notice |
| PRV-02 | Y | `consents.verification_method`, `consents.granted_at` | method open in D-13 |
| PRV-03 | Y | `consents.purpose` | `third_party_sharing` |
| PRV-04 | Y | `students.first_name`, `students.birth_month`, `students.birth_year`, `placements.level_id` | no other child fields exist |
| PRV-05 | N | — | written security programme is a process document (WP-5.2) |
| PRV-06 | Y | `students.departed_at`, `files.delete_after_at`, `recordings.delete_after_at` | schedule in pii-registry.md |
| PRV-07 | Y | `data_requests.request_type`, `data_requests.due_at`, `data_requests.status`, `data_requests.export_file_id` | |
| PRV-08 | N | — | processing agreements are legal documents (WP-0.8, WP-5.2), not platform data |
| PRV-09 | N | — | impact assessment is a document; privacy-by-default is met by the absence of location and profiling fields |
| PRV-10 | Y | `cookie_consents.choices` | marketing opt-in only |
| PUB-01 | N | — | static home page |
| PUB-02 | Y | `programs.summary_ar`, `programs.published_at`, `stages.age_min`, `stages.age_max` | |
| PUB-03 | Y | `prices.amount_minor`, `discount_rules.percent_bps`, `families.country_code` | approximate local currency is display only |
| PUB-04 | Y | `classes.published_at`, `classes.capacity`, `classes.status`, `class_enrollments.status` | seats left derived; no student data |
| PUB-05 | Y | `legal_documents.document_type`, `legal_documents.market`, `legal_documents.version` | |
| PUB-06 | Y | `leads.source`, `leads.message` | contact form becomes a lead |
| PUB-07 | N | — | SEO output (hreflang, sitemap, Open Graph) |
| PUB-08 | Y | `leads.utm_source`, `leads.referral_code`, `waitlist_entries.lead_id` | |
| PUB-09 | Y | `teacher_applications.applicant_name`, `teacher_applications.cv_file_id`, `teacher_applications.status` | |
| PUB-10 | Y | `cookie_consents.choices`, `cookie_consents.legal_document_version_id` | |
| RBAC-01 | Y | `permissions.name`, `role_has_permissions.role_id`, `model_has_roles.model_id` | |
| RBAC-02 | Y | `guardians.family_id`, `classes.teacher_id`, `class_enrollments.student_id` | scope columns on every scoped table |
| RBAC-03 | N | — | UI/server parity enforced by tests |
| RBAC-04 | Y | `audit_logs.reason_code`, `record_notes.body`, `credit_ledger_entries.reason_code` | written reason in `record_notes` |
| RBAC-05 | Y | `model_has_roles.role_id`, `users.status`, `audit_logs.action` | review recorded as audit actions |
| RPT-01 | Y | `report_items.item_key`, `report_items.score`, `report_items.body` | |
| RPT-02 | Y | `reports.kind`, `reports.student_id` | class summary has no student |
| RPT-03 | Y | `reports.kind`, `report_items.body` | |
| RPT-04 | Y | `reports.status`, `reports.due_at` | |
| RPT-05 | Y | `reports.selected_for_review`, `report_reviews.decision`, `report_reviews.comment` | |
| RPT-06 | Y | `reports.published_at`, `reports.acknowledged_at`, `reports.pdf_file_id` | |
| SAFE-01 | Y | `safeguarding_incidents.reporter_id`, `safeguarding_incidents.reporter_role`, `safeguarding_incidents.officer_notified_at` | |
| SAFE-02 | Y | `safeguarding_incident_actions.action_taken`, `safeguarding_incident_actions.actor_id` | |
| SAFE-03 | Y | `session_chat_messages.session_id`, `support_threads.family_id` | no private adult–child table exists |
| SAFE-04 | Y | `teacher_documents.document_type`, `training_completions.training_id`, `consents.purpose`, `teachers.activated_at` | |
| SAFE-05 | Y | `observations.kind`, `recording_access_log.purpose`, `recording_access_log.safeguarding_incident_id` | |
| SAFE-06 | Y | `teachers.suspended_at`, `teachers.status`, `safeguarding_incidents.subject_teacher_id` | |
| SCH-01 | Y | `classes.level_id`, `classes.age_min`, `classes.capacity`, `classes.capacity_override_reason`, `classes.teacher_id`, `class_schedule_rules.local_start`, `class_schedule_rules.ref_timezone` | |
| SCH-02 | Y | `classes.status` | |
| SCH-03 | Y | `classes.published_at`, `classes.capacity`, `class_enrollments.status` | |
| SCH-04 | Y | `waitlist_entries.status`, `waitlist_entries.level_id`, `waitlist_entries.seat_hold_id`, `seat_holds.expires_at` | |
| SCH-05 | Y | `class_sessions.local_start_on`, `class_sessions.starts_at`, `class_schedule_rules.generated_through_on` | |
| SCH-06 | Y | `class_sessions.dst_notice_sent_at` | |
| SCH-07 | Y | `class_enrollments.dst_mismatch_confirmed_by`, `class_enrollments.dst_mismatch_confirmed_at` | |
| SCH-08 | Y | `class_sessions.teacher_id`, `teacher_availability.local_start`, `student_availability.local_start` | teacher overlap excluded in the database |
| SCH-09 | Y | `session_changes.scope`, `session_changes.old_starts_at`, `session_changes.new_starts_at`, `class_sessions.replaces_session_id` | |
| SCH-10 | Y | `session_changes.reason_code`, `class_sessions.outcome` | |
| SCH-11 | Y | `teacher_levels.level_id`, `teacher_leaves.status`, `teacher_availability.weekday` | |
| SCH-12 | Y | `calendar_exceptions.kind`, `calendar_exceptions.status`, `session_changes.calendar_exception_id` | D-12 |
| SCH-13 | N | — | calendar views over `class_sessions`; nothing stored |
| SCH-14 | Y | `class_enrollments.transferred_to_enrollment_id`, `enrollment_history.to_state` | history kept |
| SCH-16 | Y | `classes.session_minutes` | |
| SEC-01 | Y | `users.password` | Argon2id hash |
| SEC-02 | Y | `users.two_factor_secret`, `users.two_factor_confirmed_at`, `roles.requires_mfa` | |
| SEC-03 | Y | `auth_sessions.last_active_at`, `auth_sessions.revoked_at`, `auth_sessions.device_label` | |
| SEC-04 | Y | `student_pins.pin_hash`, `student_pins.failed_attempts`, `student_pins.locked_until_at`, `device_authorizations.device_key_hash` | |
| SEC-05 | Y | `guardians.family_id`, `classes.teacher_id` | same scope columns as RBAC-02 |
| SEC-06 | N | — | injection, XSS, CSRF, SSRF and CSP are request handling |
| SEC-07 | N | — | rate limits live in Redis |
| SEC-08 | Y | `teacher_bank_accounts.iban`, `users.two_factor_secret` | encrypted columns |
| SEC-09 | Y | `files.mime_type`, `files.size_bytes`, `files.scan_status`, `files.metadata_stripped_at` | |
| SEC-10 | Y | `recordings.family_visible_until_at`, `recordings.delete_after_at`, `recording_access_log.accessed_at` | |
| SEC-11 | Y | `audit_logs.actor_id`, `audit_logs.changes`, `audit_log_personal_data.ip`, `audit_logs.occurred_at` | |
| SEC-12 | Y | `delivery_logs.recipient_masked` | no personal data in technical logs |
| SEC-13 | N | — | dependency scanning and penetration tests are CI and process |
| SEC-14 | Y | `users.suspended_at`, `auth_sessions.revoked_at` | |
| STU-01 | Y | `student_login_codes.method`, `student_login_codes.code_hash`, `student_pins.pin_hash` | |
| STU-02 | Y | `class_sessions.starts_at`, `live_rooms.opens_at`, `assignments.due_at` | reads only |
| STU-03 | N | — | device check runs in the browser |
| STU-04 | Y | `join_token_log.role`, `participant_events.event_type` | |
| STU-05 | Y | `content_items.audience`, `content_items.status` | |
| STU-06 | Y | `submissions.submission_type`, `feedback.published_at` | |
| STU-07 | N | — | on-screen keyboard is UI |
| STU-08 | Y | `recordings.family_visible_until_at`, `recording_access_log.viewer_id`, `consents.purpose` | own class, consented, no download |
| STU-09 | Y | `session_attendance.status`, `submissions.submitted_at` | stars and levels are derived from attendance and homework; no table |
| STU-10 | Y | `announcements.audience` | academy only |
| STU-11 | N | — | usability for young children is UI |
| TCH-01 | Y | `class_sessions.starts_at`, `reports.due_at`, `feedback.status` | reads only, shown in Amman time |
| TCH-02 | Y | `class_sessions.status` | |
| TCH-03 | Y | `students.first_name`, `placements.level_id`, `session_attendance.status`, `reports.status` | teachers see first name, level, attendance and their own reports; never birth month/year, profile answers or guardian notes |
| TCH-04 | Y | `class_sessions.teacher_notes`, `lesson_plans.activities` | |
| TCH-05 | Y | `session_attendance.confirmed_at`, `session_attendance.locked_at` | |
| TCH-06 | Y | `class_sessions.lesson_id` | |
| TCH-07 | Y | `assignments.teacher_id`, `feedback.score`, `feedback.note` | |
| TCH-08 | Y | `reports.kind`, `report_items.item_key`, `reports.status` | |
| TCH-09 | Y | `teacher_availability.weekday`, `teacher_availability.local_start`, `teacher_leaves.status` | |
| TCH-10 | Y | `teacher_leaves.kind` | `emergency` alerts operations |
| TCH-11 | Y | `change_requests.requester_role`, `change_requests.status` | |
| TCH-12 | Y | `compensation_statements.total_minor`, `compensation_lines.amount_minor`, `support_threads.category` | dispute opens a thread |
| TOP-01 | Y | `teacher_applications.status`, `teacher_interviews.scores`, `teacher_documents.document_type`, `training_completions.completed_at`, `observations.outcome`, `teachers.activated_at` | records in R1; full workflow R2 |
| TOP-02 | Y | `teachers.languages`, `teachers.qualifications`, `teacher_levels.level_id`, `teachers.max_classes`, `teacher_bank_accounts.iban`, `teacher_documents.expires_on` | |
| TOP-03 | Y | `consents.purpose`, `legal_documents.document_type` | contract, code of conduct, child-protection policy with versions |
| TOP-05 | Y | `teachers.suspended_at`, `teachers.terminated_at`, `teachers.status_reason_code` | |
