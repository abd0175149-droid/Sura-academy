# Tasks — {{WP-id}}

Status legend: [ ] todo · [x] done. Each task ≤ ~2h. Verification command required.

- [ ] T1 Contract: … — verify: `npx @redocly/cli lint contracts/openapi/sura-api.v1.yaml`
- [ ] T2 Tests (failing) for <IDs>: … — verify: `php artisan test --filter="<ID>"` fails for the right reason
- [ ] T3 Implementation: … — verify: `bash scripts/verify.sh --changed`
- [ ] T4 …
- [ ] Tn Docs: status.md, traceability.md, notes.md demo steps — verify: `php scripts/check-traceability.php --wp {{WP-id}}`
