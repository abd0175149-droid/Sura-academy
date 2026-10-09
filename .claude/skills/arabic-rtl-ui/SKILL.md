---
name: arabic-rtl-ui
description: Arabic-first RTL rules for Flutter, Filament and Blade/Tailwind in Sura Academy — directional layout APIs, bidi isolation for mixed text, icon mirroring, localization, golden tests in both directions. Use whenever building or changing any screen, component, admin page, public page, PDF or email layout.
paths:
  - "apps/family/**"
  - "apps/teacher/**"
  - "packages/sura_ui/**"
  - "packages/sura_kids/**"
  - "packages/sura_classroom/**"
  - "apps/api/resources/**"
  - "apps/api/app/Modules/*/Filament/**"
---

# Arabic-first RTL UI

## Flutter: physical → directional
| Don't | Do |
| --- | --- |
| `EdgeInsets.only(left: 8)` / `fromLTRB` | `EdgeInsetsDirectional.only(start: 8)` / `fromSTEB` |
| `Alignment.centerLeft` | `AlignmentDirectional.centerStart` |
| `Positioned(left: 0)` | `PositionedDirectional(start: 0)` |
| `TextAlign.left/right` | `TextAlign.start/end` |
| `BorderRadius.only(topLeft: …)` | `BorderRadiusDirectional.only(topStart: …)` |
| `Row` children ordered for LTR assumptions | rely on `Directionality`; never reverse lists manually |

- App direction comes from the locale (`ar` → RTL). Content blocks in the other language get their own `Directionality` (e.g., an English vocabulary item inside Arabic UI).
- Mixed text: wrap numbers, emails, phone numbers, Latin names, codes and times inside Arabic sentences with the `sura_ui` isolate helper (Unicode FSI U+2068 … PDI U+2069). Phone numbers and emails render LTR.
- Icons: directional icons (back/forward arrows, chevrons, send, reply, undo/redo, progress direction) must mirror; use icons with `matchTextDirection` or flip custom SVGs when `Directionality.of(context) == TextDirection.rtl`. Do not mirror: media play/pause, clocks, check marks, logos, the Arabic letter visuals.
- Text fields: `textDirection` follows the input language; Arabic input fields start RTL; allow mixed input.
- Numbers: Western digits by default (spec). Dates: Gregorian displayed; format with `intl` for the locale.
- No `letterSpacing` on Arabic text; avoid uppercase transforms; line height per `arabic-typography`.

## Tailwind (public site, Filament custom views)
Use logical utilities: `ms-* me-* ps-* pe-* start-* end-* text-start text-end rounded-s* rounded-e* border-s* border-e* float-start float-end`. Set `<html lang="ar" dir="rtl">` per locale and `lang`/`dir` on mixed blocks.

## Filament
Panel locale `ar` (RTL handled by Filament); every label from `lang/`; check tables, filters, modals and notifications in RTL; numbers and money aligned to end.

## Goldens (mandatory for new screens)
`pumpGolden(widget, locale: 'ar'|'en', textScale: 1.0|1.3|2.0)` from `sura_ui`. Child screens include 2.0. Review diffs visually; accept only intended changes.

## Checklist before closing UI work
- [ ] No physical direction APIs (hook-enforced; suppress only with `// rtl-ok: reason`).
- [ ] All strings in ARB/lang for ar and en; ICU plurals for counts.
- [ ] Mixed text isolated; icons mirrored correctly.
- [ ] Goldens ar/en; semantics labels; targets ≥ 44 (≥ 56 child).
- [ ] rtl-reviewer agent run.
