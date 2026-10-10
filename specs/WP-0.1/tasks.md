# Tasks — WP-0.1

Status legend: [ ] todo · [x] done. Each task ≤ ~2h. Verification command required.

- [x] T1 Root files: `.editorconfig`, `.gitattributes` (LF), README link check — verify: `git ls-files --eol | grep -v 'i/lf' | grep -v 'i/-text'` is empty
- [x] T2 Docker: `infra/docker/compose.yaml` (postgres 17, redis 7, mailpit, minio, gotenberg[pdf], clamav[scan]) + PG init roles/DBs + health checks — verify: `docker compose -f infra/docker/compose.yaml --profile pdf --profile scan up -d && docker compose -f infra/docker/compose.yaml ps` (all healthy)
- [x] T3 Laravel 13 skeleton: `composer create-project`, PG/Redis/UTC/ar-en config, `.env.example` matching compose, remove `sessions` migration, phpunit on PG `sura_test` — verify: `php artisan migrate` on local PG; `\dt` has no `sessions`
- [x] T4 Tooling: Pest + arch/laravel plugins, Larastan level 8 (`phpstan.neon`), `pint.json` — verify: `vendor/bin/pint --test && vendor/bin/phpstan analyse`
- [x] T5 Tests first: platform tests (UTC, locale, no sessions table), provider tests (routes /api/v1, policy discovery via fixture module), arch tests (directories, cross-module Models ban) → run, see them fail for the right reason — verify: `php artisan test` fails as expected
- [x] T6 Implement: `config/modules.php`, 11 module skeletons, `ModulesServiceProvider`; prove the arch test catches a temporary violation — verify: `php artisan test` green
- [x] T7 ADR-002 packages (`composer require` only) + Horizon config + Laravel Boost (`boost:install`, Claude Code) + fill ADR-002 versions table — verify: `composer show --direct`; `git diff .mcp.json` empty; `verify.sh --changed` green
- [ ] T8 Dart workspace: root `pubspec.yaml` workspace, shared `analysis_options.yaml` (very_good_analysis), 6 packages with `resolution: workspace` + one trivial test each — verify: `flutter pub get && dart analyze --fatal-infos .`
- [ ] T9 Apps: `flutter create` family (web, ios, android) and teacher (web), gen-l10n with ar/en ARB, RTL shell + widget tests, `fontFallbackBaseUrl` on our origin — verify: `flutter test` in each app; `flutter build web --wasm --no-web-resources-cdn` for both
- [ ] T10 `scripts/verify.sh --only <area>` + `.github/workflows/ci.yml` (api with PG/Redis services, dart, contract, traceability; caches) — verify: `bash scripts/verify.sh --all` green locally; CI green on the PR
- [ ] T11 Docs: ADR-002 table, notes.md (evidence, demo steps, branch-protection steps), status.md, focus block — verify: `php scripts/check-traceability.php --wp WP-0.1`
