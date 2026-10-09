---
paths:
  - "apps/api/app/**/*.php"
  - "apps/api/routes/**/*.php"
  - "apps/api/config/**/*.php"
---

# Laravel backend rules

- Code lives in `app/Modules/<Module>/` (Identity, Families, Curriculum, Scheduling, Live, Ledger, Billing, Learning, Messaging, Teachers, Platform). Never write another module's tables or import its `Models\*`; call its `Contracts\*` interface or react to its events.
- Controllers are thin: validate with a FormRequest, authorize with a Policy, call one Action, return an API Resource. No business logic in controllers, Filament resources or Blade.
- One Action class per use case (`Actions/ConsumeCreditForAttendance.php`), invoked inside `DB::transaction()` when it writes. Actions return DTOs or models, never responses.
- Cross-module side effects go through `outbox_events` written in the same transaction; listeners are idempotent (check processed event id).
- Jobs are idempotent and safe to retry: guard with a unique key or a processed timestamp; never assume exactly-once delivery.
- Authorization: every route has `can:` middleware or a Policy check; data scope via query scopes (`forFamily`, `forTeacher`). Add the route to the permission map so `RouteMatrixTest` passes.
- Money: `App\Support\Money` / `*_minor` + `currency`; never float. Percentages in basis points.
- Time: `CarbonImmutable` only, UTC in storage, IANA zones from data. `now()` is UTC.
- Settings: read business numbers via the Settings service (`settings('attendance.excuse_cutoff_hours')`), never literals; capture `rule_version_id` where the spec requires it.
- `env()` only inside `config/`. Secrets via config backed by the secrets manager.
- Errors: throw domain exceptions mapped to stable codes (`LEDGER_INSUFFICIENT_CREDIT`); the handler renders the unified envelope with Arabic and English messages from `lang/`.
- Logging: structured context with `request_id`; never log names, emails, phones, child data or tokens.
- Larastan level 8 must stay clean. No `@phpstan-ignore` without a comment explaining why.
