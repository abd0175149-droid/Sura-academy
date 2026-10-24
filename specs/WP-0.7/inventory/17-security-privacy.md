<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory
Source: `docs/01-product/spec/17-security-privacy-safeguarding.md` (F17). spec/14 = `docs/01-product/spec/14-data-model-and-states.md` (F14). spec/21 = `docs/01-product/spec/21-appendices.md`. All 30 IDs below are R1. The file has no R2/R3 rows.

| ID | Feature | Data impact | Tables | Columns/fields implied | States / enums | Settings keys | Cite |
|---|---|---|---|---|---|---|---|
| SEC-01 | Argon2id passwords, min 10, breached list | Y | users | *password_hash (inferred); min length 10 and the breached-password check are rules, not columns | - | none (10 is not a settings key) | F17:10 |
| SEC-02 | TOTP MFA: mandatory for staff and teachers, optional for parents | Y | mfa_factors, user_roles | *factor_type=TOTP (inferred); *secret (encrypted) (inferred); *confirmed_at (inferred) | - | none | F17:11 |
| SEC-03 | Secure sessions, device list, global logout | Y | auth_sessions, device_authorizations | *session_id; *user_id; *last_activity_at (idle expiry 30 min for staff); *device label; *revoked_at (inferred) | - | none (30 min idle is not a settings key) | F17:12 |
| SEC-04 | Child PIN 4-6 digits, hashed, authorized device, lockout | Y | student_pins, device_authorizations | *pin_hash (inferred, "مخزن مشفراً"); *failed_attempts (inferred); *locked_until (inferred); *authorized_by_guardian (inferred) | - | `security.child_pin_attempts` / `lock_min` = 5 / 15 (F21:39) | F17:13 |
| SEC-05 | Server-side permission and data scope per request | Y | roles, permissions, role_permissions, user_roles | scope columns `family_id`/`class_id`/`teacher_id` (F14:14) | - | none | F17:14 |
| SEC-06 | SQLi/XSS/CSRF/SSRF protection, CSP | N | - | - | - | - | F17:15 |
| SEC-07 | Rate limits on login, OTP, signup, reset, forms | N (counters are storage-level, not stated) | - | - | - | none | F17:16 |
| SEC-08 | TLS 1.2+, HSTS, encryption at rest, field encryption (teacher bank data) | Y | teachers or payouts (not stated), files | *encrypted bank fields (not named in F14) | - | none | F17:17 |
| SEC-09 | Uploads: extension allow-list, size cap, AV scan, EXIF strip, signed URLs | Y | files, media_assets, submission_files, teacher_documents | *extension/mime (inferred); *size_bytes (inferred); *scan_status (inferred) | - | none (size cap not a key) | F17:18 |
| SEC-10 | Recordings: private storage, 10-min links, no family download, access log, auto-delete | Y | recordings, recording_access_log | *expires_at/delete_at (inferred); access-log actor, time (inferred) | - | `recordings.family_days` / `admin_days` / `link_ttl_min` = 30 / 90 / 10 (F21:34) | F17:19 |
| SEC-11 | Append-only audit log, kept 2 years minimum | Y | audit_logs | actor, action, entity, value before, value after, address (IP), time (all stated) | - | none | F17:20 |
| SEC-12 | No PII in logs; mask email/phone; no child names in external tools | Y (rule on delivery_logs/system logs) | delivery_logs | masked email/phone (rule) | - | none | F17:21 |
| SEC-13 | Dependency scan, pen test pre-launch then yearly | N | - | - | - | - | F17:22 |
| SEC-14 | Incident response: suspend account or all sessions, breach deadlines (72h GDPR) | Y | users, auth_sessions | *suspended_at (inferred); *revoked_at (inferred) | - | none (72h not a key) | F17:23 |
| PRV-01 | Direct notice to parent before child data collection, naming third parties and purposes | Y | legal_documents, consents | notice version link via `legal_document_version_id` (F14:46) | - | none | F17:29 |
| PRV-02 | Verifiable parental consent (card transaction or SMS + extra step) | Y | consents | *verification_method (inferred); `granted_at`, `ip`, `user_agent` (F14:46) | - | none | F17:30 |
| PRV-03 | Separate consent for non-essential third-party sharing; no ads sharing | Y | consents | `purpose` (separate purpose value) | - | none | F17:31 |
| PRV-04 | Child data minimum (see section Personal data) | Y | students, student_profiles | first_name, birth_month, birth_year, level info (all stated); no photo/email/address | - | none | F17:32 |
| PRV-05 | Written infosec program, coordinator, annual risk assessment | N | - | - | - | - | F17:33 |
| PRV-06 | Published retention policy per child data type | Y | retention job over several tables | see retention table | - | none | F17:34 |
| PRV-07 | Guardian right to access, correct, delete, stop use; portal request; 30-day response | Y | data_requests | *request_type {access, correct, delete, stop-use} (inferred from text); *requested_at; *due_at = 30 days (inferred); *status (inferred); *completed_at (inferred); *actor (inferred); F14 lists no fields | - | none (30 days not a key) | F17:35 |
| PRV-08 | DPA with every provider; EU/UK transfer mechanism | Y (register) | NEW:data_processors (or legal_documents; unclear) | provider, purpose, DPA ref, transfer mechanism (inferred) | - | none | F17:36 |
| PRV-09 | DPIA pre-launch; UK Children's Code defaults: high privacy, no geolocation, no profiling | Y (defaults) | settings, student_profiles | no location fields (rule); privacy defaults (inferred) | - | none | F17:37 |
| PRV-10 | No ads or marketing trackers on child-visible pages; public pages by consent only | Y (consent) | consents | cookie/marketing `purpose` (inferred) | - | none | F17:38 |
| SAFE-01 | "Report a concern" button for parent and teacher; reaches safeguarding officer within 24h | Y | safeguarding_incidents | *reporter_id (inferred); *reporter_role (inferred); *reported_at; *delivered_at (inferred) | - | none (24h not a key) | F17:56 |
| SAFE-02 | Restricted incident log with every step taken | Y | safeguarding_incidents, audit_logs | steps: *action_taken; *by; *at (inferred; F14 has no step table, see Gaps) | - | none | F17:57 |
| SAFE-03 | No unmonitored private adult-child messages; no contact data sharing | Y (constraint) | messages, support_threads | no child-adult DM column or table (rule) | - | none | F17:58 |
| SAFE-04 | Teacher not activated before: police certificate, child-protection training, code-of-conduct acknowledgment | Y | teachers, teacher_documents, trainings, training_completions | certificate file, training completion, *conduct_acknowledged_at (inferred) | teacher states in F14:58 | none | F17:59 |
| SAFE-05 | Visible supervisory visits; supervisor recording access only on a report, logged | Y | observations, recordings, recording_access_log | access reason / linked incident (inferred) | - | none | F17:60 |
| SAFE-06 | Instant teacher suspension, block from sessions, reassign her classes | Y | teachers, classes, sessions | teacher state `موقوفة` (F14:58); *suspended_at (inferred) | teacher: ...→ موقوفة أو منتهية | none | F17:61 |

## Gaps
- `data_requests` field list: F14 names the table only (F14:24); PRV-07 states types and a 30-day deadline only (F17:35). No status enum is stated anywhere.
- `safeguarding_incidents` has no stated fields or states. SAFE-02 requires "بكل خطوة متخذة" (F17:57). No steps table in F14:36, so incident steps need a child table or a JSON field. Both are unspecified.
- Teacher code-of-conduct acknowledgment (SAFE-04, F17:59): no table. Candidates: `consents` (which has `student_id`, F14:46), `training_completions`, or NEW. Not stated.
- Data-processor/DPA register (PRV-08, F17:36): no table in F14.
- Encrypted teacher bank data (SEC-08, F17:17): F14:35 has teachers, payouts, compensation_* but names no bank-account table.
- Safeguarding officer role/permission and restricted access (SAFE-02, F17:57): F14:22 lists roles/permissions generically; no role is named.
- Recording expiry/deletion tracking (SEC-10, F17:19): `recordings` fields are not listed in F14.
- Retention scheduling needs per-type clocks: "بعد مغادرة الطالب" (F17:45-46) needs a stored departure date/state. Only `enrollment_history.to_state` exists (F14:47), and the state name is not defined.
- Sessions idle timeout 30 min, password min 10, 72h breach deadline, SAFE-01 24h, PRV-07 30 days: none have a settings key. F21:39 has only the PIN keys.

## Personal data and retention
### Retention table (verbatim, F17:42-50)
| نوع البيانات | مدة الاحتفاظ | بعدها | Cite |
|---|---|---|---|
| تسجيلات الحصص | 30 يوماً للأسر، 90 يوماً للإدارة | حذف نهائي من التخزين | F17:44 |
| ملفات الواجبات والتسجيلات الصوتية | 12 شهراً بعد مغادرة الطالب | حذف نهائي | F17:45 |
| التقارير والدرجات | 3 سنوات بعد مغادرة الطالب | حذف أو إخفاء الهوية | F17:46 |
| السجلات المالية | حسب القانون الأردني ومتطلبات المحاسبة | أرشيف مقيد الوصول | F17:47 |
| العملاء المحتملون دون اشتراك | 12 شهراً | حذف | F17:48 |
| سجلات النظام التقنية | 12 شهراً | حذف | F17:49 |
| سجل التدقيق | سنتان على الأقل | أرشيف | F17:50 |

### Retention defaults for types missing from the table (D-15, status Default, needs legal review; `docs/00-governance/decisions.md:108-109`)
Quote: "دردشة الحصة 12 شهراً؛ أحداث الدخول والخروج 12 شهراً؛ سجلات الموافقة طوال عمر الحساب + 3 سنوات؛ بلاغات الحماية 7 سنوات؛ محادثات الدعم 12 شهراً بعد الإغلاق؛ العملاء المحتملون بلا تسجيل 12 شهراً (موجود في الجدول)."

### PRV-04 allowed child fields (F17:32)
Exactly: الاسم الأول (first name), الشهر والسنة للميلاد (birth month and year), معلومات المستوى (level information). Explicitly excluded: صورة (photo), بريد (email), عنوان (address). Full text: "الحد الأدنى من بيانات الطفل: الاسم الأول، الشهر والسنة للميلاد، معلومات المستوى؛ لا صورة ولا بريد ولا عنوان".

### Other personal-data mentions in F17
- Adult identifiers: email and phone, masked in logs (SEC-12, F17:21). Audit log stores address/IP (SEC-11, F17:20). Consent record stores `ip` and `user_agent` (F14:46).
- Child PIN, stored hashed (SEC-04, F17:13). Recordings of child video and voice (F17:4, SEC-10, F17:19). Uploaded files and photos, with location data stripped (SEC-09, F17:18).
- Teacher bank data with extra field encryption (SEC-08, F17:17). Teacher police certificate (SAFE-04, F17:59).
- Child names must not reach external tools (SEC-12, F17:21). No contact-data sharing (SAFE-03, F17:58).
- Deletion approach, D-14 (Default; `docs/00-governance/decisions.md:105-106`): personal data in tables separate from financial/ledger records; a deletion request erases or pseudonymizes personal data; financial entries and ledger keep opaque IDs; files and recordings are permanently deleted; the audit log keeps the actor ID only.
- Related defaults: D-11 (recording only if every present student has valid consent; `decisions.md:95`). D-16 (copy to private S3 `eu-central-1` within 1h, deleted from the provider within 24h; `decisions.md:112`).
- Related open item: D-13 (Open): verifiable parental consent method before payment is undecided; `VpcMethod` interface planned (`decisions.md:100-103`).
- F14 deletion rule: "الحذف النهائي فقط بطلبات الخصوصية وجدول الاحتفاظ" (F14:13).

### Mandatory items requested, by topic (all citations above)
- MFA: TOTP mandatory for staff/teachers, optional for parents (F17:11); table `mfa_factors` (F14:22).
- Sessions/devices: secure cookies, rotation at login, 30-min staff idle, device list, sign out everywhere (F17:12); tables `auth_sessions`, `device_authorizations` (F14:22).
- PIN: 4-6 digits, stored encrypted, works only on a guardian-authorized device, locks 15 min after 5 attempts (F17:13, F21:39).
- Consent: purposes and records in `consents` (F14:46); notice (F17:29), verifiable parental consent (F17:30), separate third-party sharing consent (F17:31). No list of purpose enum values is stated.
- Data requests: access/correct/delete/stop-use, via portal, 30-day deadline (F17:35).
- Safeguarding incident: button, 24h to officer, restricted log accessible only to the safeguarding officer and system admin, every step logged (F17:56-57). Notification "بلاغ حماية طفل" goes to the safeguarding officer by SMS and email, "فوري، أولوية قصوى" (F21:67). It bypasses quiet hours (D-10, `decisions.md:92`).
- Encryption at rest: "تشفير البيانات المخزنة" plus extra field encryption (F17:17).
- Audit: append-only; actor, action, entity, before/after value, address, time; keep 2 years minimum (F17:20). General rule: `created_by`/`updated_by` in every table plus a separate append-only log (F14:16).

## Append-only, audit, money, time
- SEC-11 `audit_logs` append-only, 2-year minimum retention (F17:20, F17:50). F14:16 and F14:36.
- `consents` are never edited and withdrawal is a new record (F14:46). Consents are also versioned via `legal_document_version_id`.
- SAFE-02 incident log records every step taken (F17:57). SEC-10 logs every recording access (F17:19). SAFE-05 logs supervisor recording access (F17:60).
- Financial records: restricted-access archive per Jordanian law (F17:47). Money rules in F14:12 (integer minor units plus ISO currency). No money fields in F17 itself.
- Time: all instants UTC `timestamptz` (F14:11). Fields carrying time in F17: lockout 15 min (F17:13), idle timeout 30 min (F17:12), link TTL 10 min (F17:19), 24h and 72h and 30-day deadlines (F17:56, F17:23, F17:35).
- No idempotency requirement in F17.

## Ambiguities
1. Audit content vs D-14:
   - SEC-11 logs "القيمة قبل وبعد" and the address (F17:20).
   - D-14 says "سجل التدقيق يحتفظ بمعرّف الفاعل فقط" (`decisions.md:106`).
   - Not stated whether before/after values and IPs are erased or pseudonymized on a deletion request.
2. Audit retention: "سنتان على الأقل ... أرشيف" (F17:50) vs SEC-11 "يُحفظ سنتين على الأقل" (F17:20). Neither gives a maximum or says how long the archive is kept.
3. Never-delete vs delete:
   - F14:4: "السجلات المالية والحضور والتقارير لا تُحذف نهائياً أبداً".
   - F17:46: reports and grades after 3 years are "حذف أو إخفاء الهوية".
   - F14:13: "الحذف النهائي فقط بطلبات الخصوصية وجدول الاحتفاظ".
   - Attendance has no retention row in F17:44-50.
4. Recording retention:
   - F17:44 gives 30 days for families and 90 for administration, then "حذف نهائي".
   - D-16 deletes from the provider within 24h and copies to S3 (`decisions.md:112`).
   - D-11 says earlier recordings "تبقى حتى مدة الاحتفاظ" (`decisions.md:95`).
   - How the 30/90 split maps to one stored file is not stated.
5. "مغادرة الطالب" (F17:45-46) is not defined. Elsewhere only enrollment states exist (F14:47).
6. Missing retention rows: the F17 table omits class chat, login events, consents, safeguarding reports and support threads. D-15 supplies Default values pending legal review (`decisions.md:108-109`). Financial records have no numeric period (F17:47).
7. SAFE-01 "خلال 24 ساعة كحد أقصى" (F17:56) vs F21:67 notification timing "فوري، أولوية قصوى". Not stated which governs.
8. PRV-02 verifiable consent method is undecided (D-13 Open, `decisions.md:100-103`). "تسجيل مرئي وصوتي" consent timing is also open there.
9. SEC-02: "إلزامي لكل أدوار الموظفين والمعلمات" (F17:11). Which roles count as staff is not enumerated in the file.
10. SEC-04: "يُقفل 15 دقيقة بعد 5 محاولات" (F17:13) matches F21:39. SEC-03's 30-min idle (F17:12) has no settings key, although F14:15 says settings are versioned and CLAUDE.md says numbers come from settings.
11. PRV-04 lists "معلومات المستوى" for children. F14:24 has `student_profiles` but its fields are not listed in F14:38-47. The mapping of first name/birth month/year to `students` vs `student_profiles` is unstated.
12. SAFE-02 restricts incident access to "مسؤول الحماية ومدير النظام" (F17:57), but SAFE-05 gives the supervisor recording access on a report (F17:60). Whether supervisors can see incident records is not stated.

## R2/R3 rows with data impact
None. All SEC-*, PRV-* and SAFE-* rows in this file are R1 (F17:10-23, F17:29-38, F17:56-61).
