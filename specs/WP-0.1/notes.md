# Notes — WP-0.1

## Decisions applied
- Design approved by the owner on 2026-10-10, including the two proposals in design §8:
  - Q1: app identifiers `--org academy.sura` → `academy.sura.family`, `academy.sura.teacher`.
  - Q2: one compose file; host ports from `SURA_*_PORT` variables defaulting to the server table (25432, 26379, 21025, 28025, 29000, 29001, 23000, 23310). `.env.example` uses the same values.
- D-32/D-33: compose follows `server-environment.md` §6 so the same file runs on WSL and on `mafia-prod`.
- ADR-001: 11 module directories (`Jobs (بنية)` is infrastructure, not a module directory).

## Deviations / surprises
- (none)

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
