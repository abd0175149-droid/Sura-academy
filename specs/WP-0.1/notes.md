# Notes — WP-0.1

## Decisions applied
- Design approved by the owner on 2026-10-10, including the two proposals in design §8:
  - Q1: app identifiers `--org academy.sura` → `academy.sura.family`, `academy.sura.teacher`.
  - Q2: one compose file; host ports from `SURA_*_PORT` variables defaulting to the server table (25432, 26379, 21025, 28025, 29000, 29001, 23000, 23310). `.env.example` uses the same values.
- D-32/D-33: compose follows `server-environment.md` §6 so the same file runs on WSL and on `mafia-prod`.
- ADR-001: 11 module directories (`Jobs (بنية)` is infrastructure, not a module directory).

## Deviations / surprises
- T2: `minio/minio` (Docker Hub) and `quay.io/minio/minio` no longer publish images. Compose uses `cgr.dev/chainguard/minio:latest` (MinIO built from source by Chainguard; free tier offers only `latest`). Its image has bash but no grep/curl, so the health check speaks HTTP/1.1 over `/dev/tcp` (MinIO rejects HTTP/1.0 without Host with 400).
- T5/T6: the module list is the constant `ModulesServiceProvider::MODULES` instead of `config/modules.php`. Arch tests need the list when the file loads, before the app (and config) boot. Fixture tests call the public `registerModule()` directly.
- T6 arch proof: a temporary `App\Modules\Billing\Actions\TmpViolation` using `App\Modules\Ledger\Models\TmpEntry` made the suite fail with `Expecting 'App\Modules\Billing' not to use 'App\Modules\Ledger\Models'.` Removed → 14/14 arch tests pass.
- T7: Fortify registers login/register/reset/2FA routes on install. `Fortify::ignoreRoutes()` in `AppServiceProvider` keeps them off until WP-1.2, guarded by the test `Platform: Fortify auth routes stay unregistered until WP-1.2 wires authentication` (WP-1.2 must remove that test and the call).
- T7: Horizon published (`config/horizon.php`, `HorizonServiceProvider`). Its dashboard gate defaults to local-only; WP-1.3 owns the real `viewHorizon` permission.
- T7: Laravel Boost 2.10.3 (guidelines + skills only; `--mcp` skipped because the root `.mcp.json` already defines `laravel-boost`, and it is unchanged). Boost enabled Laravel Cloud guidance; set `cloud: false` and dropped the `deploying-to-cloud` skill (we deploy to AWS). Removed the skeleton's stale `AGENTS.md` bootstrap stub. One generic Cloud line remains in the generated `apps/api/CLAUDE.md`; left as generated.
- T7: Larastan flagged `env()` returning `bool|string` in `config/filesystems.php` and `config/horizon.php`; fixed with an `is_string` guard (no casts, no ignores).
- T7: Laravel skeleton defaults kept (tinker, pail, pao, faker, mockery, collision, concurrently); listed in the ADR-002 note.
- T8: only `sura_core` has code and a test now (`SuraLocales`, used by both apps). The other five packages are empty libraries with a doc comment; a "trivial test" there would assert nothing, so each gets tests with its first real code (WP-1.6/1.7/3.x/4.5). `flutter_test` pins `test_api`, so `sura_core` uses `test ^1.31.1` (pub-resolved).
- T2: ClamAV needs ~5–10 min on first start to download signatures before it reports healthy.

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
