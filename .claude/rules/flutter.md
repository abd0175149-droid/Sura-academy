---
paths:
  - "apps/family/**"
  - "apps/teacher/**"
  - "apps/kids/**"
  - "packages/**"
---

# Flutter / Dart rules

- Structure per feature: `lib/features/<feature>/{data,application,presentation}`. `data` wraps the generated `sura_api` client in a repository; `application` holds Riverpod notifiers (`@riverpod`); `presentation` holds screens and widgets. No HTTP calls from widgets.
- No business rules in Flutter. Eligibility, prices, balances, windows, permissions come from the API; the UI only displays and asks.
- Directional APIs only: `EdgeInsetsDirectional`, `AlignmentDirectional`, `PositionedDirectional`, `BorderRadiusDirectional`, `TextAlign.start/end`. The PostToolUse hook rejects physical variants.
- Strings only from ARB (`AppLocalizations.of(context)`), Arabic first. Plurals and gender via ICU syntax in ARB.
- Mixed-direction text (numbers, emails, Latin names inside Arabic) is wrapped with the `sura_ui` bidi isolate helper.
- Times: convert UTC from the API to the viewer's zone for display; show the class reference zone where the spec asks (PAR-04).
- Use `sura_ui` components; add new ones there with golden tests instead of styling ad hoc.
- Every interactive widget has a semantics label; touch targets ≥ 44 logical pixels (≥ 56 in `sura_kids`).
- Golden tests for every new screen: `ar` and `en`, text scale 1.0 and 1.3 (2.0 for child screens). Update goldens only for intended changes and list them in the WP notes.
- Web: never add code that loads resources from third-party hosts. Fonts are assets. CanvasKit is bundled.
- Generated code (`packages/sura_api/lib/src/generated/`, `*.g.dart`, `*.freezed.dart`) is never edited by hand.
- Lints: `very_good_analysis`; `flutter analyze` must be clean.
