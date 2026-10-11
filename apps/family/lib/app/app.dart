import 'package:family/l10n/generated/app_localizations.dart';
import 'package:flutter/material.dart';
import 'package:sura_core/sura_core.dart';

/// Root widget. Arabic by default (RTL-first); [locale] overrides it.
class FamilyApp extends StatelessWidget {
  /// Creates the app shell.
  const new({super.key, this.locale});

  /// Locale to use instead of the Arabic default.
  final Locale? locale;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      onGenerateTitle: (context) => AppLocalizations.of(context).appTitle,
      locale: locale ?? const Locale(SuraLocales.defaultLanguageCode),
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      home: const _HomePage(),
    );
  }
}

class _HomePage extends StatelessWidget {
  const new();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.appTitle)),
      body: Center(child: Text(l10n.homeSubtitle)),
    );
  }
}
