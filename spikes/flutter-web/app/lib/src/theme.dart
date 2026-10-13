import 'package:flutter/material.dart';

/// Font families bundled in pubspec.yaml (ADR-010: never fetched from a third party).
abstract final class SpikeFonts {
  /// UI, Arabic and Latin.
  static const ui = 'IBMPlexSansArabic';

  /// Vocalized learning content. The unhinted build has no Latin letters, so content styles fall
  /// back to [ui] for Latin before Flutter would try its network font fallback.
  static const content = 'NotoNaskhArabic';
}

ThemeData spikeTheme() => ThemeData(
  colorSchemeSeed: const Color(0xFF0E5D6B),
  fontFamily: SpikeFonts.ui,
  // Arabic UI line height ≥ 1.6 (arabic-typography skill); no letter spacing on Arabic.
  textTheme: const TextTheme(
    bodyLarge: TextStyle(height: 1.6),
    bodyMedium: TextStyle(height: 1.6),
    titleMedium: TextStyle(height: 1.6),
    labelLarge: TextStyle(height: 1.4, letterSpacing: 0),
  ),
);
