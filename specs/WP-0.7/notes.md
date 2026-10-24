# Notes — WP-0.7

## Decisions applied
- D-01, D-02, D-03, D-04, D-05, D-06, D-07, D-08, D-09, D-10, D-11, D-12, D-14, D-26, D-28, D-30 (Default): applied as in design.md §1.
- Also consulted (Default): D-15, D-16, D-18, D-24, D-27, D-29. D-13 (Open) only needs a nullable `consents.verification_method`, as D-13 itself prescribes.
- D-37 (Open, added 2026-10-24): student availability storage. Waiting for the owner.

## Deviations / surprises
- Reads beyond "اقرأ فقط": `architecture.md` §3 (module → table ownership, referenced by ADR-001) and constitution Art. 5 §2 (exact ledger unique-key wording). Needed to define "per module" and the ledger index.
- spec/14 names ≈ 110 tables while its intro says "نحو 90".
- The literal unique key `(student_id, session_id, type)` on every ledger type would block a second REVERSAL in one session. The constitution limits it to "قيود الحصص"; design §3.3 applies it to the four session-linked types only.

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
