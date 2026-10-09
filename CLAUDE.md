# Sura Academy (أكاديمية سُرى) — Project Constitution for Claude

Live small-group classes (2–4 children, ages 5–14) in Arabic, Islamic culture and values. Families in North America/Europe/Australia; teachers in Jordan; legal entity in Jordan. This file loads every session and holds only what must never be forgotten. Everything else is linked.

## Language
- Talk to the user in Arabic. Code, identifiers, commits, comments and technical docs are English.
- Never hardcode user-facing text. Arabic + English strings live in l10n files (Flutter ARB, Laravel `lang/`).

## Source-of-truth order — stop on conflict, never guess
1. `docs/00-governance/constitution.md`
2. `docs/02-architecture/adr/ADR-*.md`
3. `docs/00-governance/decisions.md` (Approved rows, then Default rows)
4. `docs/01-product/spec/*.md` — requirement IDs (RBAC-01, BIL-07, …) are permanent; never renumber
5. `docs/03-plan/phase-*.md` — the Work Package (WP) definitions
6. Code
If sources conflict, or a needed rule/decision is missing: stop, append a `D-xx` row (status Open) to `decisions.md`, and ask the user. Do not invent business rules.

## How work happens (mandatory)
- All implementation happens inside a Work Package: run `/sura-wp WP-x.y`. No code outside a WP.
- Session start: the SessionStart hook prints the current focus. Read `docs/03-plan/status.md`, then ONLY the target WP section, then ONLY the files that WP lists under "اقرأ فقط". Never read whole `docs/` trees; use the `spec-reader` agent for long spec files.
- Plan before code: `specs/WP-x.y/design.md` + `tasks.md`, then wait for the user's approval.
- TDD is mandatory for engines: ledger, scheduling/time, billing, compensation, access control.
- Before claiming anything is done: run `/verify` and paste the summary. Then update `status.md` and run the review agents the WP lists.
- One WP per session. `/clear` between WPs. Commit small, coherent steps.

## Stack (ADR-002, ADR-003, ADR-004)
- `apps/api`: Laravel 13 + PHP 8.4, PostgreSQL 17+, Redis + Horizon, Sanctum + Fortify, Filament admin at `/admin`, public site in Blade + Tailwind, Pest, Larastan, Pint. Modular monolith: `app/Modules/<Module>/` (ADR-001).
- `apps/family` (parent area + child mode) and `apps/teacher`: Flutter, web-first (PWA) in R1, mobile later. Shared Dart packages in `packages/` (pub workspace): `sura_core`, `sura_ui`, `sura_arabic`, `sura_api` (generated), `sura_kids`, `sura_classroom`. Riverpod + go_router.
- API contract: `contracts/openapi/sura-api.v1.yaml` is the source; Dart client is generated (never hand-edited).
- Video and payments sit behind adapters: `VideoProvider` (ADR-005), `PaymentGateway` = PayTabs (ADR-006).

## Commands
- All gates: `bash scripts/verify.sh --changed` (during work) · `bash scripts/verify.sh --all` (WP end)
- API: `cd apps/api && php artisan test --parallel` · `vendor/bin/pint` · `vendor/bin/phpstan analyse`
- Dart: `dart format .` · `flutter analyze` · `flutter test` (run inside the app/package)
- Contract → client: `bash scripts/gen-api-client.sh`
- Traceability: `php scripts/check-traceability.php`
- Local services: `docker compose -f infra/docker/compose.yaml up -d`

## Non-negotiables (constitution articles in brackets)
1. [Art. 5] `credit_ledger_entries` and `audit_logs` are append-only (DB-enforced). Corrections are `REVERSAL` entries. Every ledger entry stores `rule_version_id`. Unique `(student_id, session_id, type)` blocks double charges.
2. [Art. 6] Instants are UTC `timestamptz`. Class patterns are local time + the class's IANA zone. `Asia/Amman` is UTC+3 all year. Never hardcode offsets. Time logic is tested across the 13 zones in `scheduling-time` skill.
3. [Art. 5] Money is integer minor units + ISO currency (USD cents, JOD fils = 3 decimals). No floats.
4. [Art. 7] Authorization is server-side on every endpoint: permission AND data-scope policy (own family / assigned class / own teacher record). Every role × endpoint has a test.
5. [Art. 8] Child privacy: minimum child data; no third-party analytics/ads/trackers in child surfaces; no child PII in logs, Sentry, analytics or AI prompts; no private adult↔child channel.
6. [Art. 9] RTL-first: directional APIs only (Flutter `EdgeInsetsDirectional`, `AlignmentDirectional`, `PositionedDirectional`; CSS logical utilities). Set direction/lang per content block. Fonts are bundled, never fetched from third parties.
7. [Art. 10] OpenAPI-first, `/api/v1`, unified error `{code, message_ar, message_en, details, request_id}`, `Idempotency-Key` on every POST that moves money, credits or subscriptions, ISO-8601 UTC timestamps.
8. [Art. 5] Business numbers come from versioned settings (`spec/21-appendices.md`), never constants.
9. [Art. 4] Every change traces to requirement IDs: test names start with the ID, commits cite WP and IDs.
10. [Art. 3] Build only the IDs assigned to the current WP. R2/R3 items or anything not in the spec need an approved CR.

## Knowledge (loaded on demand)
- Skills: `credit-ledger`, `scheduling-time`, `billing-payments`, `child-privacy`, `arabic-rtl-ui`, `arabic-text`, `arabic-typography`, `curriculum-model`, `api-contract`; workflow skills `/sura-wp`, `/verify`, `/sura-status`.
- Path rules in `.claude/rules/` load when you touch matching files.
- Agents: `spec-reader`, `ledger-auditor`, `privacy-reviewer`, `rtl-reviewer`, `security-reviewer`, `traceability-checker`.
- Domain vocabulary and code names: `docs/00-governance/glossary.md`. Table/model naming: `docs/02-architecture/data-model-conventions.md`.

## Git
Branch `wp/<wp-id>-<slug>`. Conventional commits with references: `feat(ledger): consume credit on attendance [WP-2.3][ATT-07]`. Never push to `main`, never force-push, never rewrite shared history.

## Never
- Edit `docs/01-product/spec/**`, `docs/01-product/source/**` or `docs/00-governance/constitution.md` (human-only; propose a CR instead).
- Read, print or commit secrets or `.env` files.
- Skip, delete or weaken a failing test, or lower a threshold, to make `/verify` pass.
- Hand-edit generated code (`packages/sura_api/lib/src/generated/**`).
