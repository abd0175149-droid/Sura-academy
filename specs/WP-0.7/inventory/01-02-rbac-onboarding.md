<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory

Component rows (01:8-14) have no IDs. They are covered in the "Roles and scope" block below the table. `*` marks a column not listed in spec/14 "critical entities". Neither file names a settings key, so the Settings column is "none" on every row.

| ID | Feature | Data | Tables | Columns/fields | States/enums | Settings | Cite |
|---|---|---|---|---|---|---|---|
| RBAC-01 | Named permissions grouped into roles | Y | roles, permissions, role_permissions, user_roles | *permission key (e.g. `attendance.mark`, `payments.refund`); user may hold many roles (user_roles many-to-many) | none | none | 01:31 |
| RBAC-02 | Scope policy on every query | Y | all scoped tables (spec/14:14) | `family_id`, `class_id`, `teacher_id`; student scoped by own `student_id` (inferred) | none | none | 01:32 |
| RBAC-03 | No UI button without server permission; per-role tests | N | none | none | none | none | 01:33 |
| RBAC-04 | Written reason for sensitive actions, audited | Y | audit_logs, credit_ledger_entries (`reason` already listed) | *reason, *actor_id, *action, *entity (all inferred). Sensitive actions: manual credit, refund, locked-attendance edit, price change | none | none | 01:34 |
| RBAC-05 | Quarterly access review; immediate revocation | Y | user_roles, audit_logs; NEW:access_reviews (inferred) | *revoked_at (inferred), *review date/reviewer/outcome (inferred) | none | none | 01:35 |
| PUB-01 | Home page content | N | none | none | none | none | 02:10 |
| PUB-02 | Program and stage pages | Y | programs, stages, levels, level_outcomes | *age range per level (inferred, "الأعمار"), localized names (inferred) | none | none | 02:11 |
| PUB-03 | Pricing page from pricing engine | Y | plans, prices, discount_rules | family bundles; *approximate local-currency display (inferred); *FX source (inferred, no home) | none | none | 02:12 |
| PUB-04 | Published schedule with seats left | Y | classes, class_schedule_rules, class_enrollments | filters by region, age, level; remaining seats (derived); *published flag (inferred); no student data | class state "مفتوحة" (open) per spec/14:53 | none | 02:13 |
| PUB-05 | Versioned legal policies | Y | legal_documents | types: terms, privacy, children-privacy notice, market-specific refund, child-protection policy; version per document | none | none | 02:14 |
| PUB-06 | Contact form and WhatsApp link | Y | NEW:contact_messages (or leads, inferred) | *name/email/message (inferred); WhatsApp URL (config, inferred) | none | none | 02:15 |
| PUB-07 | Bilingual SEO | N | none (content_items language, inferred) | hreflang, sitemap, Open Graph are output only | none | none | 02:16 |
| PUB-08 | Waitlist and lead capture | Y | leads, lead_events, waitlist_entries | *utm_source/medium/campaign (inferred, "UTM"), *referral_code | none | none | 02:17 |
| PUB-09 | Recruiting page feeds onboarding | Y | teacher_applications | applicant fields not listed; status starts at "متقدمة" (applied) | teacher states (spec/14:58) | none | 02:18 |
| PUB-10 | Cookie consent | Y | consents (see Ambiguities) | *purpose (essential vs marketing), granted_at, withdrawn_at; visitor has no `user_id` | essential only by default; marketing needs explicit opt-in | none | 02:19 |
| ONB-01 | Guardian account | Y | users, families, guardians, consents | *name, *email, *phone (+country code), *country, *timezone (inferred then editable), *communication_language, *password hash (inferred), consent `legal_document_version_id` + `granted_at` | none | none | 02:27 |
| ONB-02 | Email and phone verification | Y | users; NEW:otp_challenges (inferred) | *email_verified_at, *phone_verified_at (inferred), OTP code hash/expiry, *attempt count, *resend time (all inferred) | none | none (resend timer and attempt limit values unnamed) | 02:28 |
| ONB-03 | Minimal child data | Y | students, student_profiles | *first_name, *birth_month, *birth_year (stated), *primary_language, *estimated_arabic_level, *reading/writing/speaking answers; no photo, no child email | none | none | 02:29 |
| ONB-04 | Notes field medical-data warning | Y | family_notes (or student_profiles notes, inferred) | *free-text notes; the warning itself is UI | none | none | 02:30 |
| ONB-05 | Placement quiz | Y | placement_quizzes, quiz_attempts, placements | *auto score (inferred), *suggested level range (inferred), duration 10-15 min | none | none | 02:31 |
| ONB-06 | Path after quiz | Y | placements, assessments, trials | *path choice: direct trial vs oral assessment (inferred) | none | none | 02:32 |
| ONB-07 | Book assessment interview | Y | assessment_slots, assessments, notifications | slot published by assessors, shown in family timezone; *calendar file, *meeting URL, reminders (inferred) | none | none | 02:33 |
| ONB-08 | Book trial seat or join waitlist | Y | trials, sessions (type trial), class_enrollments, waitlist_entries | seat in a real published class | trial: مؤهلة → مجدولة (eligible → scheduled) | none | 02:34 |
| ONB-09 | Trial eligibility and duplicate check | Y | trials, students, users, payment_methods | one trial per child; match on verified phone, name, birth date, *payment-method fingerprint (stated); unique rule (inferred) | trial states (spec/14:59) | none | 02:35 |
| ONB-10 | Post-trial offer and payment | Y | plans, prices, discount_rules, coupons, coupon_redemptions, subscriptions, invoices, payments | automatic family discount, coupon, then payment | subscription: draft → pending_payment (spec/14:70) | none | 02:36 |
| ONB-11 | Progress tracker | N | none (derived) | steps: account, child, level, trial, subscription | none | none | 02:37 |
| ONB-12 | Ops-assisted signup | Y | families, users, guardians; NEW:invitations (inferred) | invite token, expiry, status; consents and password completed later | none | none | 02:38 |
| ONB-13 | Schedule preferences | Y | student_availability (or waitlist_entries, inferred) | asked only for waitlist and special cases; stored in UTC (stated) | none | none | 02:39 |

### Roles and scope (01:17-27)
All roles come from a single table. Each row lists data scope, key permissions, forbidden items and 2FA.

| Role | Scope | Forbidden | 2FA | Cite |
|---|---|---|---|---|
| ولي الأمر (guardian) | own family only | other families' data, teacher contact data | optional | 01:19 |
| الطالب (student) | self only | any financial info or contact data | no, PIN | 01:20 |
| المعلمة (teacher) | assigned classes only | prices, payments, balances, family contact data | mandatory | 01:21 |
| المشرف الأكاديمي | all academic data | payments, technical settings | mandatory | 01:22 |
| العمليات وخدمة العملاء | families, students, classes | refunds, prices, sensitive settings | mandatory | 01:23 |
| المالية | financial data | editing level or attendance | mandatory | 01:24 |
| مدير المحتوى | curriculum, content, templates | family and financial data | mandatory | 01:25 |
| مدير النظام | all data | "لا شيء" (nothing), every action logged | mandatory | 01:26 |
| الزائر (visitor) | public pages | everything after login | — | 01:27 |

- Permissions per role (01:19-26):
  - Guardian: manage children, booking, payment, absence reports, reports, recordings, consents.
  - Student: join own classes, content, assignments, own class recordings.
  - Teacher: attendance, assignments, reports, availability, own earnings statement.
  - Academic supervisor: placement, report review, quality rating, edit locked attendance with a reason.
  - Ops and customer service: registration, scheduling, moving students between classes, tickets, exceptions.
  - Finance: invoices, refunds, financial balance, approve transfers and payouts.
  - Content manager: create, publish and archive content.
  - System admin: settings, integrations, roles, audit log.
  - Visitor: browsing, creating an account, waitlist.
- Scope rules (01:32):
  - Guardian by family id.
  - Teacher by assigned classes.
  - Student by own id.
  - Staff roles are scoped by data domain, not by id (01:22-26).
- Authorization is checked in the server on every request by role and data scope (01:4).
- Users may hold several roles (01:31).

## Gaps

- Access review record for RBAC-05: "مراجعة ربع سنوية لصلاحيات الموظفين وإلغاء فوري عند انتهاء العمل" (01:35). No review table and no revocation field in spec/14:22.
- OTP challenge storage for ONB-02: "رمز OTP للهاتف، مع مؤقت إعادة الإرسال وحد للمحاولات" (02:28). No table in spec/14:22.
- Email and phone verification state for ONB-02 and ONB-09: "الهاتف الموثق" (02:35). No verified-at fields are listed for users.
- Family invitation for ONB-12: "ترسل رابط دعوة لإكمال الموافقات وكلمة المرور" (02:38). No invitation or token table.
- Contact form submissions for PUB-06: "نموذج تواصل" (02:15). No table. leads (spec/14:25) is a possible fit.
- Local-currency approximation for PUB-03: "العملة المحلية التقريبية" (02:12). No FX or rate store, and no settings key named.
- Anonymous cookie consent for PUB-10 (02:19). consents requires `user_id` (14:46).
- Payment-method fingerprint for ONB-09 (02:35). payment_methods exists (14:32) but its fields are not listed.
- Child answers on reading, writing and speaking for ONB-03 (02:29). student_profiles exists (14:24) but its fields are not listed.
- Referral code for PUB-08 (02:17). No referral table in spec/14. PUB-12 (R2) also needs one.
- Evaluator and assessor role for ONB-07: "مواعيد ينشرها المقيّمون" (02:33). No role in the 01:19-27 table matches it. Related tables: assessment_slots, assessments.
- Staff 2FA flag per role: 2FA is mandatory or optional per role (01:19-26). Related tables: mfa_factors and student_pins. The mapping of role to 2FA requirement has no listed field.

## Personal data and retention

Neither file states any retention period.
- 01:13 lists "حذف البيانات المنتهية" as a background job. No periods are given.
- 14:13 says permanent deletion happens "فقط بطلبات الخصوصية وجدول الاحتفاظ", with no values.

| Data | Where | Cite |
|---|---|---|
| Guardian name, email, phone + country code, country, timezone, language, password | ONB-01 | 02:27 |
| Verified phone and email, OTP | ONB-02 | 02:28 |
| Consents with version and time (spec/14 adds ip, user_agent) | ONB-01, PUB-10; 14:46 | 02:27, 02:19 |
| Child first name, birth month and year, primary language, Arabic level, reading/writing/speaking answers | ONB-03 | 02:29 |
| Free-text notes (medical warning) | ONB-04 | 02:30 |
| Birth date, name, verified phone, payment-method fingerprint (duplicate detection) | ONB-09 | 02:35 |
| UTM source and referral code on leads | PUB-08 | 02:17 |
| Contact form content | PUB-06 | 02:15 |
| Teacher applicant data | PUB-09 | 02:18 |
| Student availability (UTC) | ONB-13 | 02:39 |
| Child recordings (viewed by guardian and student) | RBAC role table | 01:19-20 |
| Not stored | no child photo, no child email | 02:29 |
| Public data | published schedule shows no student data | 02:13 |
| Contact data hidden | teacher contact hidden from guardians (01:19); family contact hidden from teachers (01:21) | 01:19, 01:21 |
| Marketing tracking | public pages only, with explicit consent | 02:19 |

## Append-only, audit, money, time

- RBAC-04: sensitive actions require a written reason and are written to the audit log (01:34). The audit log is append-only (14:16).
- 01:26: the system admin is unrestricted, but "كل فعله مسجل" (every action is logged).
- 01:22: the academic supervisor can edit locked attendance with a reason. Attendance is never hard-deleted (14:4).
- 01:24: the finance role handles refunds and "الرصيد المالي". Money is integer minor units plus currency (14:12).
- PUB-03: prices come from the pricing engine and the page shows an approximate local currency (02:12). This is a display amount, so rounding and format rules are not stated.
- PUB-05: legal documents are versioned (02:14). 14:15 says every record points to the version it was applied to.
- ONB-01: consents are stored with version and time (02:27). 14:46: consents are never edited, and withdrawal is a new record.
- ONB-07: slots are shown in the family timezone (02:33). Instants are UTC `timestamptz` (14:11).
- ONB-13: preferences are stored in UTC (02:39). 14:11 stores class patterns as local time plus an IANA zone.
- ONB-09: one trial per child (02:35). Duplicate detection needs a uniqueness rule.
- ONB-10: coupon redemption and payment (02:36). Payments carry an `idempotency_key` (14:45).
- ONB-12: the invitation link is time-bound (inferred, 02:38).
- Components: background jobs cover reminders, renewal, collection, session generation and expired-data deletion (01:13). The integrations layer provides swappable adapters for video, payment and messaging (01:14).

## Ambiguities

1. Birth data. ONB-03 stores "الشهر والسنة للميلاد" (02:29). ONB-09 matches on "تاريخ الميلاد" (02:35). It is unclear whether a full date or only month and year is stored.
2. Consent subject. PUB-10 says cookie consent is for visitors on public pages (02:19). `consents` requires `user_id` (14:46). A visitor has no `user_id`.
3. Student identity. The student role has no email and logs in by PIN (01:20, 02:29). spec/14 lists `users` and also `student_pins` (14:22). It is unclear whether a student has a `users` row.
4. Assessor role. ONB-07 names "المقيّمون" (02:33). The role table has no assessor role. The academic supervisor "التسكين" (placement) is the closest (01:22). The text doesn't say who runs the oral assessment.
5. ONB-12 and ONB-01. ONB-01 stores consents at account creation (02:27). ONB-12 lets ops create the family first and complete consents later (02:38). The family state before consent is not stated.
6. Waitlist. PUB-08 is a short visitor form that saves UTM and referral (02:17). ONB-08 joins a waitlist from an account (02:34). 01:27 allows the visitor "قائمة الانتظار". Whether these are `waitlist_entries`, `leads`, or both is not stated.
7. Time storage. ONB-13 says preferences are saved in UTC (02:39). 14:11 stores class patterns as local time plus an IANA zone. A recurring weekly availability pattern is not covered by either statement.
8. Scope shape. RBAC-02 names only the guardian, teacher and student scopes (01:32). 01:22-26 scope staff by data domain. 14:14 lists only `family_id`, `class_id` and `teacher_id`. No scope key is stated for staff domains such as finance, content or curriculum.
9. Two-factor. 01:19 says optional for the guardian, 01:20 says "لا، رمز PIN" for the student, and the staff rows say mandatory. 14:22 lists `mfa_factors` and `student_pins` but no role-to-requirement field.
10. Field-level hiding. 01:19 and 01:21 forbid teacher contact data to guardians and family contact data to teachers. 14:14 scopes by row only. The text does not say whether this is a column-level rule.
11. PUB-04. It lists open classes with remaining seats (02:13). The class state "مفتوحة" exists (14:53). Capacity and seat-count fields are not in the spec/14 critical entities.
12. ONB-02. A phone number must be verified (02:28). ONB-01 collects the phone at signup (02:27). It is not stated whether unverified accounts persist or expire.

## R2/R3 rows with data impact

- RBAC-06 — second-person approval above a settings threshold (R2) — audit_logs, settings; NEW:approvals (inferred) (01:36)
- RBAC-07 — read-only "view as user" with a written reason (R2) — audit_logs, auth_sessions (01:37)
- PUB-11 — blog and articles (R2) — content_items, content_versions (02:20)
- PUB-12 — referral landing pages and rewards (R2) — leads; NEW:referral_codes, referral_rewards (inferred) (02:21)
- ONB-14 — Google and Apple login (R3) — users, auth_sessions; NEW:oauth_identities (inferred) (02:40)
- Native iOS/Android apps (R3, 01:15) — no data impact.

Files read: /home/abdul/sura-academy/docs/01-product/spec/14-data-model-and-states.md, /home/abdul/sura-academy/docs/01-product/spec/01-components-roles-rbac.md, /home/abdul/sura-academy/docs/01-product/spec/02-public-site-and-onboarding.md
