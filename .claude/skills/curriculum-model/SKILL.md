---
name: curriculum-model
description: Curriculum and content model for Sura Academy — program/stage/level/unit/lesson hierarchy, content types and visibility, lesson plans, versioning and archival, can-do level frameworks, learning outcomes codes (CR-002), variety field, assessments and reports linkage. Use for any work in Curriculum, Learning (homework/reports), placement, or content admin.
paths:
  - "apps/api/app/Modules/Curriculum/**"
  - "apps/api/app/Modules/Learning/**"
---

# Curriculum model

Sources: `spec/07-content-assessment-homework-reports.md`, CR-002, D-18, D-26, glossary.

## Hierarchy (CNT-01)
Program → Stage → Level → CurriculumUnit (spec "module") → Lesson → ContentItem. Programs: Arabic, Islamic culture, Values.

## Content
- Types (CNT-02): vocalized text page, video, pronunciation audio, PDF worksheet, image, external link.
- Visibility (CNT-03): student, teacher, parent, public.
- Lesson plan (CNT-04): objectives, vocabulary, timed activities split Arabic / cultural parts, materials, linked homework.
- Teacher resources (CNT-05): teacher-only visibility.
- Lifecycle (CNT-06): draft → published → archived. Content referenced by past lessons/reports is archived, never deleted.
- Media library (CNT-07): source, license type, license expiry with alert.
- Levels framework (CNT-09): "يستطيع أن…" statements per level; used by assessment and reports. Store ACTFL and CEFR equivalents as reference fields.
- `variety` field on levels/outcomes/content: `msa` only in R1 (D-18).
- Translations: short labels `*_ar/*_en`; long content in `content_translations`.

## Learning outcomes (only if D-26 is applied)
`learning_outcomes`: `code` (`^[A-Z]{2,4}-L\d{1,2}-[A-Z]{1,3}-\d{2}$`, e.g. `AR-L2-R-03`), `statement_ar/en`, `level_id`, `skill` (R reading, W writing, L listening, S speaking, V vocabulary, C culture, VAL values-observed), `actfl`, `cefr`, `variety`, `prerequisites` (outcome codes). `outcome_alignments`: `alignable_type` (lesson, content_item, lesson_plan), `alignable_id`, `outcome_code`, `weight`. No UI in R1.

## Assessment and reports linkage
- Placement (ASM-01/02/03) recommends a level; the academic supervisor decides (BR-017).
- Weekly indicators (RPT-01) and monthly reports (RPT-03) reference level statements; values are observed, never scored with points.
