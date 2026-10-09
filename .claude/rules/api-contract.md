---
paths:
  - "contracts/**"
  - "apps/api/app/Modules/*/Http/**"
  - "apps/api/routes/**"
  - "packages/sura_api/**"
---

# API contract rules

- Change `contracts/openapi` first, then write a failing feature test that calls `assertMatchesContract()`, then implement.
- Paths: `/api/v1/<kebab-case-plural>`; operationId `<module>.<resource>.<action>`; schemas PascalCase; properties snake_case.
- Every error response uses `#/components/schemas/Error` with a stable `code` registered in `contracts/openapi/components/error-codes.yaml`.
- Money is `{ amount_minor: integer, currency: string }`. Instants are ISO-8601 UTC strings. Local class times are `{ local_time: "HH:MM", timezone: "<IANA>" }`.
- Lists use cursor pagination (`cursor`, `limit` ≤ 100, response `next_cursor`).
- POST endpoints that create payments, subscriptions, refunds, credits or ledger effects require the `Idempotency-Key` header (documented as required).
- After any contract change: `bash scripts/gen-api-client.sh` and commit the regenerated client in the same PR.
- Full conventions: `api-contract` skill.
