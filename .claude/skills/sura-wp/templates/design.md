# Design — {{WP-id}} {{name}}

## 1. Scope
- In scope (IDs): …
- Explicitly out of scope: …
- Decisions applied: D-xx (Default/Approved) — how it shapes the design

## 2. Traceability
| Req ID | Behavior (one line) | Code location (planned) | Test name(s) (planned) |
| --- | --- | --- | --- |

## 3. Data
Tables/columns added or changed (follow data-model-conventions.md), constraints, indexes, append-only, PII registry entries.

## 4. API contract changes
| Method | Path | Request | Response | Errors (codes) | Idempotency |
| --- | --- | --- | --- | --- | --- |

## 5. Domain logic
Actions, state transitions (reference state-machines.md), events (outbox), jobs (idempotency key), settings keys read.

## 6. UI (if any)
Screens, states (loading/empty/error), RTL notes, goldens planned, semantics labels.

## 7. Security & privacy
Permissions, scopes, audit entries, child-data impact, retention.

## 8. Risks & open questions
Anything needing the user's decision before implementation.
