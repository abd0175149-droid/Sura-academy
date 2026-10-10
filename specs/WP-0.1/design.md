# Design — WP-0.1 Repository and tooling bootstrap

## 1. Scope
- In scope (IDs): none (infrastructure WP, no functional requirement IDs). Deliverables 1–7 of `phase-0-foundation.md#WP-0.1`.
- Explicitly out of scope: domain tables, authentication flows, UI beyond the default app shells, the OpenAPI contract itself (WP-1.7), fonts/design system (WP-1.6), deployment (WP-1.8).
- Decisions applied:
  - D-32 / D-33: develop in WSL2; the dev server is a preview target. `infra/docker/compose.yaml` follows the binding rules in `server-environment.md` §6 so the same file runs both locally and on `mafia-prod`.
  - ADR-001: module layout. ADR-002: PHP package list (only these). ADR-003: Dart tooling. ADR-004: contract-first (only the CI job skeleton here). ADR-010: web build flags.
- Interpretation (no conflict): ADR-001 / architecture §3 say "12 modules", but the 12th row is `Jobs (بنية)`, which is infrastructure (Horizon + Scheduler) with no tables and no directory. So 11 directories go under `app/Modules/`, exactly as the WP lists them.

## 2. Traceability
No requirement IDs. Each deliverable maps to a test or check instead:

| Deliverable | Code location (planned) | Verification (planned) |
| --- | --- | --- |
| Root files | `.editorconfig`, `.gitattributes`, `README.md` | `git ls-files --eol` shows `i/lf` |
| Laravel app on PG/Redis, UTC, ar/en | `apps/api/` (`config/app.php`, `.env.example`, `phpunit.xml`) | Pest `Platform: app boots with UTC timezone and ar locale (en fallback)`; `php artisan migrate` on PG |
| No `sessions` table | `database/migrations/0001_01_01_000000_create_users_table.php` | Pest `Platform: default migrations do not create a sessions table` |
| Module skeleton ×11 | `app/Modules/<Module>/{Actions,Contracts,Events,Http,Jobs,Models,Policies,Filament}/` | Pest arch `Arch: every module has the standard directories` |
| `ModulesServiceProvider` | `app/Providers/ModulesServiceProvider.php` | Pest `Platform: module api routes are registered under /api/v1`, `Platform: module policies are discovered by convention` (fixture module in `tests/Fixtures/Modules`) |
| Cross-module Models ban | `tests/Arch/ModulesTest.php` | Run against a temporary violating class → fails; removed → passes (recorded in notes.md) |
| Pest + arch, Larastan L8, Pint | `phpstan.neon`, `pint.json` | `verify.sh` gates `api:pint`, `api:phpstan` |
| ADR-002 packages + versions table | `composer.json/lock`, ADR-002 table | ADR-002 table filled from `composer show` |
| Laravel Boost | `apps/api/CLAUDE.md` (generated) | `/mcp` shows `laravel-boost` connected; root `.mcp.json` unchanged (`git diff`) |
| Dart workspace | root `pubspec.yaml`, `analysis_options.yaml`, `apps/family`, `apps/teacher`, `packages/*` (6) | `dart analyze --fatal-infos .`; `flutter test` per app: `family: app renders Arabic RTL shell`, `teacher: app renders Arabic RTL shell`; one trivial test per package |
| ARB ar/en | `apps/*/lib/l10n/app_{ar,en}.arb` | same widget tests check the localized title under `ar` and `en` |
| Docker services | `infra/docker/compose.yaml` | `docker compose ... up -d` then `ps` → all healthy |
| CI | `.github/workflows/ci.yml` | green on the first PR |

## 3. Data
No domain tables. Laravel default migrations are kept (`users`, `password_reset_tokens`, `cache`, `jobs`) except `sessions`, which is removed because `SESSION_DRIVER=redis`. The `users` table is a placeholder; WP-1.1/WP-1.2 own its real shape.
PostgreSQL roles (compose init script): `sura_migrator` (owner, DDL, `CREATEDB` for parallel test DBs) and `sura_app` (DML only). Databases: `sura` and `sura_test`. Init SQL lives in `infra/docker/postgres/init/`. Passwords come from env with dev-only defaults in `.env.example`; no real secret.

## 4. API contract changes
None. CI gets a `contract` job that runs `verify.sh --only contract`. That gate is skipped until `contracts/openapi/sura-api.v1.yaml` exists (WP-1.7).

## 5. Domain logic
- `ModulesServiceProvider`: iterates `config('modules.list')`, an explicit list of the 11 names. Explicit beats directory scanning: it is cheap, deterministic, and testable via config override. For each module:
  - routes: if `Modules/<M>/Http/routes/api.php` exists → `Route::prefix('api/v1')->middleware('api')->group(...)`; if `Http/routes/web.php` exists → `web` group.
  - policies: `Gate::guessPolicyNamesUsing` maps `App\Modules\<M>\Models\<X>` → `App\Modules\<M>\Policies\<X>Policy`, falling back to Laravel's default.
- Arch tests (`tests/Arch/ModulesTest.php`):
  - for each module A and each B ≠ A: `expect("App\\Modules\\A")->not->toUse("App\\Modules\\B\\Models")`.
  - presets: `php`, `security`; no `dd/dump/ray` in `app/`.
- Packages installed now (`composer require` only, no `*:install` that publishes auth/domain migrations): horizon, fortify, sanctum, spatie/laravel-permission, filament/filament, laravel/pennant, spatie/laravel-webhook-client, brick/money, propaganistas/laravel-phone, gotenberg/gotenberg-php, sentry/sentry-laravel; dev: pestphp/pest + pest-plugin-arch + pest-plugin-laravel, larastan/larastan, laravel/pint (preinstalled), laravel/boost. Publishing their migrations and configs (permission, pennant, fortify, sanctum tokens, Filament panel) is deferred to the WPs that own them (WP-1.1–1.4, 4.1). Horizon config is published now (infra only).
- Dart dependencies at this stage: `flutter_localizations`, `intl` (apps); `very_good_analysis` (dev, root). Riverpod, go_router, dio, sentry_flutter, and mocktail are added by WP-1.6 / WP-1.7 when first used, to avoid unused-dependency lints.

## 6. UI (if any)
Default `flutter create` counter screens are replaced by a minimal shell: `MaterialApp` with `localizationsDelegates`, `supportedLocales: [ar, en]`, `locale: ar`, showing the localized app title. No hardcoded strings. Directional widgets only. No goldens (WP-1.6). Web: `web/index.html` / `flutter_bootstrap.js` sets `fontFallbackBaseUrl` to our own origin (`/fonts/`) and builds with `--no-web-resources-cdn`, so the `web:no-third-party-resources` gate passes.

## 7. Security & privacy
- No secrets in the repo: `.env.example` holds dev-only placeholders. `.env` stays git-ignored and unread by Claude.
- Compose: every port on `127.0.0.1`, Redis `requirepass`, explicit network `10.213.27.0/24`, resource limits per `server-environment.md` §6, ClamAV/Gotenberg in profiles `scan`/`pdf`.
- No child data, no analytics packages. Sentry is installed but left unconfigured (no DSN) until WP-5.5.

## 8. Risks & open questions
1. **Q1 — App identifiers (needs your decision):** `flutter create --org` sets the iOS/Android bundle ID, which is hard to change after store publication. Proposal: `academy.sura` → `academy.sura.family` / `academy.sura.teacher`. If you own a different domain, tell me.
2. **Q2 — Ports:** `setup-dev-environment.md` lists default ports (5432…) for local work, while `server-environment.md` §6 (binding) requires the 2xxxx ports on the server. Proposal: one compose file, ports from variables defaulting to the server table (`${SURA_DB_PORT:-25432}` …), and `.env.example` uses the same values. Local and server then behave identically and avoid clashes with anything on Windows. (Guide table update recorded in notes.)
3. **`verify.sh --only <area>`:** the CI jobs (api, dart, contract, traceability) each call `verify.sh --all`. Without a filter each job would run every gate and need every toolchain. I'll add an optional `--only api|dart|contract|traceability` flag; behavior without it stays unchanged.
4. Flutter web may still embed `fonts.gstatic.com` in the engine JS even with `fontFallbackBaseUrl`. If the gate fails for that reason only, I'll stop and report rather than weaken the gate.
5. Filament major version: the "latest stable covered by Laravel Boost guidelines" is checked at install time and recorded in ADR-002 and notes.
6. API coverage threshold 80% applies under `--all`; the provider and arch tests should cover the little app code there is.
7. Branch protection on `main` is done by you on GitHub (deliverable 1). I'll list the steps in the demo notes.
