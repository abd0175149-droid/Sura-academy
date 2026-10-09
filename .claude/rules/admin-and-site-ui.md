---
paths:
  - "apps/api/resources/**"
  - "apps/api/app/Modules/*/Filament/**"
---

# Filament admin and public site rules

- Filament panel locale `ar` with RTL; every label from `lang/`. Tables use logical alignment; numbers and dates render in Western digits.
- Every sensitive Filament action (refund, manual credit, locked attendance edit, price change, role change, safeguarding access) opens a required "reason" field and writes an audit entry.
- Filament resources call module Actions; no business logic in resources.
- Public site: Blade + Tailwind logical utilities only (ms/me/ps/pe/start/end/text-start/text-end). `<html dir lang>` per locale, `lang` per mixed block. Fonts self-hosted.
- Public pages may load Plausible only; no other third-party scripts. Cookie banner defaults to essential only.
- Public schedule and pricing pages read from the API/engines; never duplicate prices or rules in templates.
