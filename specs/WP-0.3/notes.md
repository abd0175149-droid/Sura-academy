# Notes — WP-0.3

## Decisions applied
- No D-xx is listed for this WP.
- Owner approval 2026-10-12 (artifact https://claude.ai/artifact/H2CD4uhRQvaetqEBFuNMF8): Q1 design + tasks approved as written; Q2 CI runs the checker in full mode (`verify.sh --all` → no `--quick`).

## Deviations / surprises
- T3: the approved design said the comparison ignores figure captions `> **شكل (منقول نصاً …)**`. Wrong: the preflight probe compared line *sets* and misread which line in file 13 was extra. The three captions are in the source too, so they are compared like any line (a test proves it). The real extra line is the package note `> **تنبيه الحزمة:** …` at the end of `13-architecture-original.md` (Vue/Inertia row replaced by ADR-003), which `spec/README.md` documents under «ملاحظات النقل». The checker ignores `> **تنبيه الحزمة:**` lines and `<!-- … -->` headers, and counts package notes in its summary line so they stay visible. No D-xx: the difference is already documented by the owner and decided by ADR-003. Owner told in chat; the approval page was corrected.

## Verification evidence
```
(paste verify --all summary)
```
| Acceptance criterion | Evidence |
| --- | --- |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
1. …
