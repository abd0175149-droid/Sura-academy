# Notes — WP-0.2

## Decisions applied
- D-33: development in WSL; `server-environment.md` §4 controls apply only to sessions on `mafia-prod`.
- Owner authorization (2026-10-11, remote): run WP-0.2 in the same session as WP-0.1 without `/clear`; Claude runs all commands on the machine.

## Deviations / surprises
- Design (2026-10-11): the first draft of `design.md` quoted AWS's public example access key verbatim; the Write was blocked by `pre_tool_guard.php` ("the content looks like an AWS access key"). Unplanned live evidence for G4; the design now describes the key without spelling it out.

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
