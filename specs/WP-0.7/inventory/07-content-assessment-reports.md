<!-- spec-reader extract for WP-0.7 (2026-10-24). Raw input to docs/02-architecture/erd/r1-coverage.md; not a source of truth. -->

## R1 inventory

Source files: `docs/01-product/spec/07-content-assessment-homework-reports.md` (abbreviated `07`) and `docs/01-product/spec/22-learning-experience-proposal.md` (abbreviated `22`). Table names are from `14-data-model-and-states.md` (`14`). Columns marked `*` are not listed in 14's "critical entities" table. 14 lists critical-entity fields for only 6 entities, and none of them is a curriculum, assessment or learning table. So every column below carries `*`.

| ID | Feature | Data impact | Tables | Columns/fields implied | States / enum values stated | Settings keys | Cite |
|---|---|---|---|---|---|---|---|
| CNT-01 | Curriculum hierarchy | Y | programs, stages, levels, modules, lessons, content_items | *parent links program→stage→level→module→lesson→content_item (inferred) | none | none | 07:10 |
| CNT-02 | Content item types | Y | content_items, media_assets | *item_type; values: vocalized text page, video, pronunciation audio, PDF worksheet, image, external link | item_type (6 values, left) | none | 07:11 |
| CNT-03 | Content visibility audience | Y | content_items (inferred) | *visibility; values: student / teacher / guardian / public | visibility (4 values) | none | 07:12 |
| CNT-04 | Lesson plan | Y | lesson_plans, lessons | *objectives, *vocabulary, *activities with timing (Arabic and cultural part), *materials, *linked_homework (assignment link) | none | none | 07:13 |
| CNT-05 | Teacher-only resources | Y | content_items (inferred) or NEW:teacher_resources | *slides, *answer_keys, *teaching_guides; visibility = teachers only (inferred) | none | none | 07:14 |
| CNT-06 | Draft, publish, archive content | Y | content_items, content_versions (inferred), lessons | *status (draft/published/archived), `archived_at` (14:13 general rule); *no hard delete once used in lessons or reports | draft / published / archived | none | 07:15 |
| CNT-07 | Media library, tags, licence | Y | media_assets, files | *tags, *licence_source, *licence_type, *licence_expires_on; search over tags (tag storage has no 14 table) | none | none | 07:16 |
| CNT-08 | Arabic typography (fonts, size, tashkeel toggle) | N (UI/fonts). Possible user preference storage, unstated | none | font size and tashkeel on/off are display options; text must keep diacritics (stored text implication) | none | none | 07:17 |
| CNT-09 | Level "can do" framework | Y | level_outcomes | *can_do_statement per level; used by assessment and reports (link to assessments/reports unspecified) | none | none | 07:18 |
| ASM-01 | Placement quiz | Y | placement_quizzes, quiz_attempts | *questions with image/audio (no question table in the quiz domain except `questions`, which is R2-flavoured), *auto_score, *suggested_level_range | none | none | 07:29 |
| ASM-02 | Oral interview form | Y | assessments, assessment_scores | *criteria scores 1–5: pronunciation_articulation, listening, conversation, reading, writing, islamic_knowledge_by_age; *recommendation | score range 1–5; 6 criteria | none | 07:30 |
| ASM-03 | Placement decision | Y | placements, enrollment_history | *approved_by (academic supervisor role); *recorded in the student history | none | none | 07:31 |
| HW-01 | Homework builder | Y | assignments, files | *title, *instructions_ar, *instructions_en, *submission_type, *due_at (UTC) plus class IANA zone (due time "in class time"), *attachments, *lesson_id | none | none | 07:42 |
| HW-02 | Submission types | Y | submissions, submission_files, files | *submission_type values: Arabic-keyboard text, handwriting photo, voice recording up to 3 minutes, file; *audio_duration_limit 180 s | 4 submission types | none | 07:43 |
| HW-03 | Resubmission until close | Y | submissions | *every version kept (version_no, inferred); *allowed until closing (closing time, inferred from the due date or close date, not stated) | none | none | 07:44 |
| HW-04 | Grading | Y | feedback, submissions | *grade (score) or rubric criterion, *note_text, *published_at (visible to student and guardian only after publishing) | draft→published (inferred; "after publishing") | none | 07:45 |
| HW-05 | Late policy and reminder | Y | assignments (policy ref), settings, notifications | *late_policy configurable; reminder one day before the due date | none | none named in 07; no homework late-policy key found in 21-appendices | 07:46 |
| HW-06 | Strip image metadata | Y | files, submission_files | *no GPS/device EXIF kept on uploaded images (processing rule, no column) | none | none | 07:47 |
| RPT-01 | Weekly per-student indicators | Y | reports, report_items | *attendance (auto from session_attendance), *participation 1–5, *homework_status, *skill_of_week, *optional_note | participation 1–5 | none | 07:53 |
| RPT-02 | Weekly class summary | Y | reports, report_items | *topics_covered (from lesson plan), *homework, *next_week_goals; one per class (class-level, not per student) | none | none | 07:54 |
| RPT-03 | Detailed monthly report | Y | reports, report_items | *reading, *writing, *listening, *speaking notes, *values_and_islamic_culture_notes, *recommendations | none | none | 07:55 |
| RPT-04 | Report tasks | Y | reports (state "مهمة"), follow_up_tasks (not a clear fit), notifications | *due_at, *overdue alert to teacher and supervisor; tasks auto-created | report state: مهمة (task) | none | 07:56 |
| RPT-05 | Sample review | Y | report_reviews | *review_decision, *return_comment ("إعادة للتعديل" with a comment), *selected_for_review (full review for the first 3 months, then 20% random); *teacher start date (inferred from teachers) | معاد للتعديل | `reports.review_sample_pct` = 20% and `new_teacher_full_review_months` = 3 (21-appendices:33). 07 states the values but names no keys. | 07:57 |
| RPT-06 | Publish, read receipt, PDF | Y | reports, notifications | *published_at, *guardian_read_at (read time recorded), PDF download | منشور → مُطّلع عليه | none | 07:58 |
| OUT-01 (CR-002 proposed) | Learning-outcome catalogue | Y | NEW:learning_outcomes (22 line 45; vs level_outcomes in 14:26) | *outcome_code (e.g. `AR-L2-R-03`), *statement_ar, *statement_en, *actfl_level, *cefr_level, *observable_criterion, *variety (default "fusha"), *prerequisite network between outcomes (a link table is implied) | none | none | 22:14 |
| OUT-02 (CR-002 proposed) | Tag content with outcomes | Y | NEW:outcome_alignments (22:45) | *item_type, *item_id, *outcome_code, *weight; applies to lessons, content items, lesson plans | none | none | 22:15 |

## Gaps

- CNT-01 / CNT-02: `files` (14:36) does not say how the six item kinds are stored per item (text body with tashkeel, external URL). Text: "صفحة نصية مشكولة، فيديو، صوت نطق، ورقة عمل PDF، صورة، رابط خارجي" (07:11).
- CNT-03: the visibility field has no named home. Text: "للطالب أو المعلمة أو ولي الأمر أو عام" (07:12).
- CNT-05: no table is named for teacher-only resources (slides, answer keys, guides). Text: "الشرائح ومفاتيح الإجابة وأدلة التدريس، للمعلمات فقط" (07:14).
- CNT-06: `content_versions` is listed in 14:26, but versioning, "سجل نسخ مع إمكانية الرجوع", is R2 (CNT-10, 07:19). R1 publishing and archiving has no stated version row. "لا حذف لمحتوى استُخدم في دروس أو تقارير سابقة" (07:15) requires a usage reference from lessons and reports to content, with no table for it.
- CNT-07: no tag table (tags and search). Text: "وسوم وبحث" (07:16).
- ASM-01: the placement quiz needs questions with images and audio. The only question table in 14 is `questions` in the R2 test domain (ASM-04/07). Text: "أسئلة بالصور والصوت تناسب الصغار، تصحيح آلي، نطاق مستوى مقترح" (07:29).
- ASM-02: the interview criteria need a rubric definition. `assessment_scores` is the nearest table (14:27). "من 1 إلى 5 … ثم توصية" (07:30). `assessment_slots` (interview booking) is not mentioned in the text.
- ASM-03: "يُسجل في تاريخ الطالب" (07:31). 14:47 defines `enrollment_history` as "يُكتب مع كل انتقال في دورة حياة الطالب" (from_state/to_state), which is a different shape from a placement decision (level, approver).
- HW-01: there is no homework-to-lesson-plan link. 14 lists `assignments` (14:33) with no fields. "الدرس المرتبط" (07:42) and the lesson plan's "الواجب المرتبط" (07:13) both need it.
- HW-03: "حفظ كل نسخة" (07:44) needs versioned submissions. 14 lists `submissions` and `submission_files` with no versioning or close time.
- HW-05: "سياسة تأخير قابلة للإعداد" (07:46). No homework late-policy key appears in 21-appendices in my grep. Only `attendance.late_after_min` = 10 min (21:15) exists, and it is an attendance rule.
- RPT-02: the class-level summary is "مرة واحدة لكل الشعبة" (07:54). 14 lists `reports` / `report_items` only, with no stated per-class vs per-student scope.
- RPT-04: "تُنشأ تلقائياً بمواعيد استحقاق" (07:56). The state "مهمة" is in 14:55, so tasks are report rows. `follow_up_tasks` (14:25) is in the leads domain.
- RPT-06: the read receipt maps to the state "مُطّلع عليه" (14:55). No read-event table is listed. PDF generation and storage is not mentioned in 14 (files, 14:36, is the likely home but is not stated).
- OUT-01: 22 proposes `learning_outcomes`, while 14:26 already lists `level_outcomes`.

## Personal data and retention

- No retention periods are stated in 07. 14:13 states only "الحذف النهائي فقط بطلبات الخصوصية وجدول الاحتفاظ".
- 14:4: "السجلات المالية والحضور والتقارير لا تُحذف نهائياً أبداً، بل تُؤرشف أو تُصحح". This covers reports (RPT-*).
- HW-02 (07:43): the child's handwriting photo, voice recording up to 3 minutes, free text and files are child-authored content.
- HW-06 (07:47): "حذف بيانات الموقع والجهاز من الصور المرفوعة تلقائياً". This removes GPS and device metadata.
- HW-04 (07:45): free-text teacher note about the child, visible to the student and guardian after publishing.
- RPT-01 (07:53): "ملاحظة اختيارية" is a free-text note per student.
- RPT-03 (07:55): notes on values and Islamic culture, and recommendations. These are free text about the child.
- ASM-01 (07:29): the child's quiz answers and score. ASM-02 (07:30): interview scores and a recommendation.
- RPT-06 (07:58): the guardian's read time is recorded.
- CNT-07 (07:16): licence data (source, type, expiry) is not personal data.

## Append-only, audit, money, time

- HW-01 (07:42): "الموعد بتوقيت الشعبة". The due time is the class zone (UTC instant plus the class IANA zone, per 14:11).
- HW-03 (07:44): "حفظ كل نسخة". Submissions are never overwritten.
- ASM-03 (07:31): the placement decision is "يُسجل في تاريخ الطالب". History entries are written per 14:47.
- CNT-06 (07:15): archiving instead of deleting (`archived_at`, 14:13).
- RPT-04 (07:56): auto-generated tasks have due dates; overdue alert times are not specified.
- RPT-05 (07:57): "إعادة للتعديل بتعليق". Review decisions and comments form a history on `report_reviews` (14:33).
- RPT-06 (07:58): "تسجيل وقت الاطلاع" is a UTC instant.
- Money: no money fields in this file's R1 rows. Idempotency: none stated.
- General: 14:16 requires `created_by` / `updated_by` on every table, and 14:15 requires versions for templates (RPT-08 is R2).

## Ambiguities

- Report states vs rows: 14:55 gives "مهمة → مسودة → مُرسل → (معاد للتعديل) → منشور → مُطّلع عليه". RPT-05 (07:57) says only a sample of reports is reviewed, but the state list has the "مُرسل" (submitted) step before "منشور" for all reports. It is unclear whether unsampled reports skip review, and the text does not say.
- `learning_outcomes` (22:45) vs `level_outcomes` (14:26) vs CNT-09 (07:18). CNT-09 says "عبارات «يستطيع أن» لكل مستوى" with no code, ACTFL/CEFR or `variety`. OUT-01 (22:14) adds those fields. Whether these are one table or two is not stated.
- CNT-06 (07:15) requires archiving content, while CNT-10 (07:19) puts version history in R2. 14:26 lists `content_versions` with no R1/R2 marking.
- ASM-01 "نطاق مستوى مقترح" (07:29) vs ASM-03 "يعتمده المشرف الأكاديمي" (07:31). Where the suggested range is stored (`quiz_attempts` vs `assessments`) is not stated.
- HW-04 "درجة أو معيار تقييم" (07:45). Whether it is a numeric score or a rubric is "or", with no choice made and no scale given.
- HW-05 "سياسة تأخير قابلة للإعداد" (07:46) is configurable, but no settings key exists in 21-appendices (searched on homework, late, الواجب). The only late-related key is `attendance.late_after_min` (21:15).
- RPT-01 "الحضور تلقائياً" (07:53). Source tables are not named. 14:31 lists `session_attendance`. Whether it is copied or joined is not stated.
- RPT-05: "ثلاثة أشهر" (07:57) vs `new_teacher_full_review_months` = 3 (21:33). They agree. The start of the three months (hire date vs first report) is not stated.
- HW-02: "تسجيل صوتي حتى 3 دقائق" (07:43). No settings key is stated for the limit.

## R2/R3 rows with data impact

- CNT-10 — content version history with rollback — content_versions
- CNT-11 — fixed QR code per content item — qr_codes
- CNT-12 — interactive activity builder — NEW (no table in 14)
- CNT-13 — level workbook PDF export — files
- CNT-14 (R3) — Arabic text-to-speech for pronunciation material — media_assets
- ASM-04 — test builder with Arabic question types — tests, questions
- ASM-05 — attempts and grading — test_attempts, test_answers
- ASM-06 — result withholding until teacher publishes — test_attempts
- ASM-07 — question bank tags (level, skill, difficulty) — questions
- ASM-08 — level certificate PDF — files (inferred)
- RPT-07 — level report with promote/repeat decision — reports, placements
- RPT-08 — editable report templates — message_templates (inferred; 14:15 lists templates as versioned)
- RPT-09 (R3) — AI-assisted monthly report draft — reports

Relevant files:
- /home/abdul/sura-academy/docs/01-product/spec/07-content-assessment-homework-reports.md
- /home/abdul/sura-academy/docs/01-product/spec/14-data-model-and-states.md
- /home/abdul/sura-academy/docs/01-product/spec/22-learning-experience-proposal.md
- /home/abdul/sura-academy/docs/01-product/spec/21-appendices.md
