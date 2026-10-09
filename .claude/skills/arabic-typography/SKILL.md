---
name: arabic-typography
description: Arabic typography rules for Sura Academy — font roles, sizes and line heights for vocalized text, diacritics fidelity, bundled fonts, PDF and email rendering. Use when choosing text styles, building reading/lesson screens, generating PDFs (invoices, reports, certificates) or emails.
paths:
  - "packages/sura_ui/**"
  - "apps/api/resources/**"
---

# Arabic typography

## Fonts (spec CNT-08, `spec/16` fonts row)
| Role | Font | Notes |
| --- | --- | --- |
| UI (Arabic + Latin) | IBM Plex Sans Arabic | weights 400/500/600/700 |
| Vocalized learning content | Noto Naskh Arabic | full diacritics positioning |
| Fallback / PDF classical | Amiri | |
All fonts are bundled assets (OFL licenses in `packages/sura_ui/fonts/LICENSES`). Never load fonts from Google Fonts or any CDN (child privacy + Flutter web config).

## Sizes and spacing
- UI body ≥ 16 logical px; child UI ≥ 20.
- Vocalized reading text: 24–32 px for ages 5–10, ≥ 20 for 11–14; line height ≥ 1.9 for vocalized, ≥ 1.6 for UI Arabic.
- No letter spacing on Arabic. Avoid bold for vocalized body text (diacritic collisions); use size/color for emphasis.
- Provide "show/hide diacritics" and "enlarge text" controls on reading screens (CNT-08).

## Diacritics fidelity
- Never strip or reorder diacritics in stored content (display normalization = NFC only).
- Golden tests include the fixture sentence with full tashkeel at text scale 1.0/1.3/2.0 to catch clipping.
- Containers holding Arabic text must not clip vertically; avoid fixed heights for text rows.

## PDF (Gotenberg/Chromium)
`@font-face` with embedded bundled fonts; `<html lang="ar" dir="rtl">`; test by extracting text from the generated PDF and comparing to source (normalized NFC). Invoices and reports bilingual.

## Email
Bilingual templates with `dir` per block; system font stack fallback after the web font; test in Mailpit with RTL clients' rendering checklist.
