import 'package:flutter/material.dart';

import 'l10n/app_localizations.dart';
import 'src/join_screen.dart';

void main() => runApp(const SpikeApp());

/// WP-0.4 video provider spike. Arabic first, right-to-left; no analytics, no third-party hosts
/// except the video provider.
class SpikeApp extends StatelessWidget {
  const SpikeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      onGenerateTitle: (context) => AppLocalizations.of(context).appTitle,
      locale: const Locale('ar'),
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      theme: ThemeData(
        colorSchemeSeed: const Color(0xFF1F6B57),
        fontFamily: 'IBMPlexSansArabic',
      ),
      darkTheme: ThemeData(
        colorSchemeSeed: const Color(0xFF1F6B57),
        brightness: Brightness.dark,
        fontFamily: 'IBMPlexSansArabic',
      ),
      home: const JoinScreen(),
    );
  }
}
