# Notes — WP-0.3

## Decisions applied
- No D-xx is listed for this WP.
- Owner approval 2026-10-12 (artifact https://claude.ai/artifact/H2CD4uhRQvaetqEBFuNMF8): Q1 design + tasks approved as written; Q2 CI runs the checker in full mode (`verify.sh --all` → no `--quick`).

## Deviations / surprises
- T3: the approved design said the comparison ignores figure captions `> **شكل (منقول نصاً …)**`. Wrong: the preflight probe compared line *sets* and misread which line in file 13 was extra. The three captions are in the source too, so they are compared like any line (a test proves it). The real extra line is the package note `> **تنبيه الحزمة:** …` at the end of `13-architecture-original.md` (Vue/Inertia row replaced by ADR-003), which `spec/README.md` documents under «ملاحظات النقل». The checker ignores `> **تنبيه الحزمة:**` lines and `<!-- … -->` headers, and counts package notes in its summary line so they stay visible. No D-xx: the difference is already documented by the owner and decided by ADR-003. Owner told in chat; the approval page was corrected.

## Verification evidence
`php scripts/check-traceability.php` (full mode) on the repository, 2026-10-12:
```
requirements: 253 (R1 210 · R2 38 · R3 5) · source: spec-rev24.md (22 sections · 1 package note) · mapped: 253 · work packages: 50 · test files: 15 · ids referenced by tests: 0
✔ traceability OK
```
`php scripts/test-traceability.php`:
```
traceability tests: 42/42 as expected
```
`bash scripts/verify.sh --all`:
```
──────── verify summary (--all) ────────  (2026-10-12, at 29e4243)
✅ api:pint
✅ api:phpstan
✅ api:tests+coverage(80)
✅ dart:format
✅ dart:analyze
✅ flutter:test:packages/sura_core
✅ flutter:test:apps/family
✅ flutter:test:apps/teacher
✅ web:build:apps/family
✅ web:no-third-party-requests:apps/family
✅ web:build:apps/teacher
✅ web:no-third-party-requests:apps/teacher
✅ guards
✅ traceability
✅ traceability:selftest
```

| Acceptance criterion / deliverable | Evidence |
| --- | --- |
| Script output pasted in notes.md | Full-mode output above |
| 1. Full mode passes: 253 IDs (210 / 38 / 5) | Summary line above. Totals are now checked against `spec/README.md` and the `traceability.md` header (cases `N2 …`, `N3 …`) and per file against the README index (`N4 …`) |
| 1. Each ID assigned | Check N6 `… is not assigned to any work package`; repo has 0 such rows |
| 1. Every referenced WP is in status.md | Existing check (`trace wp missing from status`) plus BR-table WPs (`N8 br table names unknown wp`); every status WP has a real definition (`N7 …`) |
| 2. Traceability step in CI | CI job `traceability` runs `bash scripts/verify.sh --all --only traceability`, which now runs full mode + `traceability:selftest` (commit 29e4243) |
| 3. Split vs source differences → Open decision, no hand fix | Check N5 compares the 22 split files with `source/spec-rev24.md` line by line; failures say `record an Open D-xx …; do not edit the spec by hand`. Result today: 0 differences beyond the provenance headers and the one documented package note in file 13, so no D-xx was opened |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
1. من جذر المستودع شغّل `php scripts/check-traceability.php`. يجب أن يبدأ السطر الأول بـ `requirements: 253 (R1 210 · R2 38 · R3 5)` وأن يظهر بعده `✔ traceability OK`.
2. شغّل `php scripts/test-traceability.php`. النتيجة المتوقعة `traceability tests: 42/42 as expected`. كل حالة فيها تُفسد نسخة تجريبية من الملفات في مجلد مؤقت، وتتأكد أن السكربت يلتقط الخطأ.
3. لترى فحصاً يفشل دون لمس المواصفات: افتح `docs/03-plan/traceability.md` وغيّر في صف `ADM-16` الأولوية من `R2` إلى `R1`، ثم شغّل السكربت. سيفشل بسطر واحد: `✖ ADM-16: traceability priority «R1» does not match the spec (R2)`. أعد الملف كما كان بالأمر `git checkout docs/03-plan/traceability.md`.
4. شغّل `bash scripts/verify.sh --all --only traceability`. يجب أن يظهر في الملخص `✅ traceability` و`✅ traceability:selftest`.
5. في صفحة الـ PR على GitHub تأكد أن فحص `traceability` في CI أخضر.
