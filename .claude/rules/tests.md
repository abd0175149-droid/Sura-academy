---
paths:
  - "apps/api/tests/**"
  - "**/test/**"
  - "**/integration_test/**"
  - "e2e/**"
---

# Test rules

- Test names start with the requirement ID: Pest `it('ATT-03 rejects a third excused absence within 8 sessions')`, Dart `test('STU-07 keyboard inserts shadda over the previous letter', ...)`. Use `BR-0xx` for business rules and `NFR-<area>` for non-functional checks.
- Engines (ledger, scheduling, billing, compensation, access) are written test-first.
- Time tests use the 13-zone dataset and the DST dates listed in the `scheduling-time` skill. Freeze time with `travelTo()` / `withClock`; never depend on the real clock.
- Randomized tests use a fixed seed printed on failure.
- No network: fake gateways (`FakePaymentGateway`, `FakeVideoProvider`, `FakeOtpSender`) and Mailpit/MinIO locally. Real sandbox calls only in spikes and in explicitly tagged `@group sandbox` tests that CI skips.
- Factories create minimal valid data; child factories never generate photos, emails or addresses.
- Never delete, skip or weaken an existing test to make a change pass.
