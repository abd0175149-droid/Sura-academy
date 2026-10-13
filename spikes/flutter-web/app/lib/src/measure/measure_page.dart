import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../platform/browser.dart' as browser;
import 'startup.dart';

class MeasurePage extends StatefulWidget {
  const MeasurePage({super.key});

  @override
  State<MeasurePage> createState() => _MeasurePageState();
}

class _MeasurePageState extends State<MeasurePage> {
  String? _status;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    if (!browser.isBrowser) return Center(child: Text(l10n.webOnly));
    final env = browser.environment();
    final marks = browser.startupMarks();
    final isolated = env['isolated'] == true;
    String ms(String key) =>
        marks[key]?.round().toString() ?? l10n.notAvailable;
    final rows = <(String, String)>[
      (l10n.measureVariant, browser.pageMeta('variant')),
      (l10n.measureRuntime, compiledToWasm ? l10n.measureWasm : l10n.measureJs),
      if (compiledToWasm)
        (
          l10n.measureThreads,
          isolated ? l10n.measureMulti : l10n.measureSingle,
        ),
      (l10n.measureIsolation, isolated ? l10n.measureOn : l10n.measureOff),
      (
        l10n.measureServiceWorker,
        env['sw'] == 'controlled'
            ? l10n.measureSwControlled
            : l10n.measureSwNone,
      ),
      (l10n.measureBytes, '${env['transfer_kb']}'),
    ];
    final timings = <(String, String)>[
      (l10n.markSplash, ms('fcp')),
      (l10n.markEntrypoint, ms('entrypoint')),
      (l10n.markEngine, ms('engine')),
      (l10n.markFirstFrame, ms('first-frame')),
      (l10n.markInteractive, ms('interactive')),
    ];
    return ListView(
      padding: const EdgeInsetsDirectional.all(16),
      children: [
        for (final (k, v) in rows) _row(context, k, v),
        const SizedBox(height: 16),
        Semantics(
          header: true,
          headingLevel: 2,
          child: Text(
            l10n.measureTimings,
            style: Theme.of(context).textTheme.titleMedium,
          ),
        ),
        for (final (k, v) in timings) _row(context, k, v),
        const SizedBox(height: 24),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            FilledButton(
              onPressed: () => browser.navigate(
                Uri(
                  path: '/auto',
                  queryParameters: {
                    'k': browser.runKey(),
                    'd': browser.queryParam('d'),
                    'n': browser.queryParam('n'),
                  },
                ).toString(),
              ),
              child: Text(l10n.autoRun),
            ),
            OutlinedButton(
              onPressed: () async {
                await browser.clearCaches();
                if (mounted) setState(() => _status = l10n.cachesCleared);
              },
              child: Text(l10n.clearCaches),
            ),
          ],
        ),
        if (_status != null)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: 12),
            child: Text(_status!),
          ),
      ],
    );
  }

  Widget _row(BuildContext context, String label, String value) => Padding(
    padding: const EdgeInsetsDirectional.symmetric(vertical: 4),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(child: Text(label)),
        const SizedBox(width: 16),
        // Numbers and build names are LTR runs inside the RTL row.
        Text(
          value,
          textDirection: TextDirection.ltr,
          style: const TextStyle(fontFeatures: [FontFeature.tabularFigures()]),
        ),
      ],
    ),
  );
}
