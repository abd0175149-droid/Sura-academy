import 'package:flutter/material.dart';
import 'package:flutter/semantics.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import '../l10n/app_localizations.dart';
import 'api.dart';
import 'feedback/feedback_page.dart';
import 'form/form_page.dart';
import 'listen/listen_card.dart';
import 'measure/measure_page.dart';
import 'payment/payment_page.dart';
import 'platform/browser.dart' as browser;
import 'reading/reading_page.dart';
import 'theme.dart';
// Deferred: LiveKit is heavy and only this tab needs it (dart2js splits it into its own file;
// dart2wasm ignores `deferred` unless --enable-wasm-deferred-loading).
import 'video/video_page.dart' deferred as video;

/// Loaded the first time the video tab is shown (top-level finals are lazy).
final Future<void> _videoLibrary = video.loadLibrary();

const _tabs = ['reading', 'form', 'payment', 'video', 'measure', 'feedback'];

class SpikeApp extends StatelessWidget {
  const SpikeApp({super.key});

  @override
  Widget build(BuildContext context) {
    // Arabic first; `?lang=en` checks the mirrored layout.
    final locale = Locale(browser.queryParam('lang') == 'en' ? 'en' : 'ar');
    // No `dir` on <html> (ADR-010 §4), but `lang` must follow the locale.
    browser.setDocumentLang(locale.languageCode);
    return MaterialApp(
      onGenerateTitle: (context) => AppLocalizations.of(context).appTitle,
      debugShowCheckedModeBanner: false,
      locale: locale,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: spikeTheme(),
      home: const _Home(),
    );
  }
}

class _Home extends StatelessWidget {
  const _Home();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final initial = _tabs.indexOf(browser.queryParam('tab'));
    final api = SpikeApi();
    return DefaultTabController(
      length: _tabs.length,
      initialIndex: initial < 0 ? 0 : initial,
      child: Scaffold(
        appBar: AppBar(
          title: Text(l10n.appTitle),
          bottom: TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: [
              Tab(text: l10n.tabReading),
              Tab(text: l10n.tabForm),
              Tab(text: l10n.tabPayment),
              Tab(text: l10n.tabVideo),
              Tab(text: l10n.tabMeasure),
              Tab(text: l10n.tabFeedback),
            ],
          ),
        ),
        body: Semantics(
          role: SemanticsRole.main,
          child: TabBarView(
            children: [
              ReadingPage(
                speaker: browser.isBrowser ? const BrowserSpeaker() : null,
                onCapture: (png, meta) => api.postShot(png, {
                  ...meta,
                  'device': browser.queryParam('d'),
                  'variant': browser.pageMeta('variant'),
                }),
              ),
              const FormPage(),
              const PaymentPage(),
              FutureBuilder<void>(
                future: _videoLibrary,
                builder: (context, snap) =>
                    snap.connectionState == ConnectionState.done
                    ? video.VideoPage()
                    : const Center(child: CircularProgressIndicator()),
              ),
              const MeasurePage(),
              const FeedbackPage(),
            ],
          ),
        ),
      ),
    );
  }
}
