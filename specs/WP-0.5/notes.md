# Notes — WP-0.5

## Approval
- 2026-10-13, design and tasks approved via the approval page (https://claude.ai/artifact/1FXNBHetbZJhHTYnyHjobK). Answers: 1 yes · 2 (a) the owner borrows a Chromebook for one session · 3 (a) Claude runs an Android emulator for the screenshots · 4 (b) iPad video under the headers too, using the **existing WP-0.4 LiveKit Cloud key** (not revoked yet; the spike server reads it from `spikes/video/token-server/.env` at runtime, Claude never opens the file) · 5 tools and spike dependencies approved · 6 gate change approved.

## Decisions applied
- D-22 (Default, needs CR-003): measured against splash LCP ≤ 2.5 s, interactive ≤ 5 s first load / ≤ 2 s later loads; final numbers proposed to the owner.
- ADR-010 (Proposed): every initial setting exercised; status changes only with the owner's explicit approval.

## Deviations / surprises
- (none)

## Verification evidence
```
(paste verify --all summary)
```
| Acceptance criterion | Evidence |
| --- | --- |
| ADR-010 Accepted with final settings | |
| D-22 numbers proposed to the owner (CR-003 approve or amend) | |
| Written decision on the isolation headers | |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
1. …
