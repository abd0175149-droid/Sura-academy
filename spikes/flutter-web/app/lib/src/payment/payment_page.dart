import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../platform/browser.dart' as browser;

/// A fake hosted payment page on a second origin (spike server, port 27201): full-page redirect
/// and return, popup, and iframe — each under the page's isolation headers or without them.
class PaymentPage extends StatefulWidget {
  const PaymentPage({super.key});

  @override
  State<PaymentPage> createState() => _PaymentPageState();
}

class _PaymentPageState extends State<PaymentPage> {
  StreamSubscription<Map<String, Object?>>? _messages;
  String? _status;
  String? _via;
  String? _frameSrc;

  String get _payOrigin => browser.pageMeta('pay-origin');

  @override
  void initState() {
    super.initState();
    // Back from the redirect: the gateway appended status and mode to our return URL.
    final returned = browser.queryParam('status');
    if (returned.isNotEmpty) {
      _status = returned;
      _via = 'redirect';
      _report();
    }
    if (browser.isBrowser && _payOrigin.isNotEmpty) {
      _messages = browser.messagesFrom(_payOrigin).listen((m) {
        setState(() {
          _status = '${m['status']}';
          _via = '${m['mode']}';
          if (_via == 'frame' || _via == 'frame-corp') _frameSrc = null;
        });
        _report();
      });
    }
  }

  @override
  void dispose() {
    _messages?.cancel();
    super.dispose();
  }

  void _report() =>
      browser.reportProbe('payment', {'status': _status, 'via': _via});

  Uri get _appOrigin => Uri.base.replace(path: '', query: '', fragment: '');

  String _returnUrl() {
    final q = Map<String, String>.of(Uri.base.queryParameters)
      ..remove('status')
      ..remove('ref')
      ..remove('mode')
      ..['tab'] = 'payment';
    return Uri.base.replace(queryParameters: q, fragment: '').toString();
  }

  String _payUrl(String path, String mode) => Uri.parse(_payOrigin)
      .replace(
        path: path,
        queryParameters: {
          'return': _returnUrl(),
          'origin': _appOrigin.origin,
          'mode': mode,
        },
      )
      .toString();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    if (!browser.isBrowser) return Center(child: Text(l10n.webOnly));
    final status = switch (_status) {
      null => l10n.payStatusNone,
      'paid' => l10n.payStatusPaid,
      'cancelled' => l10n.payStatusCancelled,
      'no-opener' => l10n.payPopupNoOpener,
      final other => other,
    };
    return ListView(
      padding: const EdgeInsetsDirectional.all(16),
      children: [
        Text(l10n.paymentIntro),
        const SizedBox(height: 16),
        Semantics(
          liveRegion: true,
          child: Text(
            l10n.payResult(status),
            key: const ValueKey('pay-result'),
            style: Theme.of(context).textTheme.titleMedium,
          ),
        ),
        const SizedBox(height: 16),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            FilledButton(
              onPressed: () => browser.navigate(_payUrl('/pay', 'redirect')),
              child: Text(l10n.payRedirect),
            ),
            OutlinedButton(
              onPressed: () => browser.openPopup(_payUrl('/popup', 'popup')),
              child: Text(l10n.payPopup),
            ),
            OutlinedButton(
              onPressed: () =>
                  setState(() => _frameSrc = _payUrl('/frame', 'frame')),
              child: Text(l10n.payFrame),
            ),
            OutlinedButton(
              onPressed: () =>
                  setState(() => _frameSrc = _payUrl('/frame', 'frame-corp')),
              child: Text(l10n.payFrameCorp),
            ),
          ],
        ),
        if (_frameSrc != null && kIsWeb) ...[
          const SizedBox(height: 16),
          SizedBox(
            height: 320,
            child: HtmlElementView.fromTagName(
              key: ValueKey(_frameSrc),
              tagName: 'iframe',
              onElementCreated: (e) => browser.configureIframe(e, _frameSrc!),
            ),
          ),
          TextButton(
            onPressed: () => setState(() => _frameSrc = null),
            child: Text(l10n.payClose),
          ),
        ],
      ],
    );
  }
}
