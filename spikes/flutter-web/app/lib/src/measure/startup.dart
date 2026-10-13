import 'package:flutter/foundation.dart';

import '../api.dart';
import '../platform/browser.dart' as browser;
import 'metrics_payload.dart';

/// True when compiled with dart2wasm (Flutter docs, "Wasm" page).
const bool compiledToWasm = bool.fromEnvironment('dart.tool.dart2wasm');

/// Called once the first frame is on screen and one more frame has run (semantics tree built,
/// input live): marks "interactive", posts the timings, and hands over to the auto-run page.
Future<void> onInteractive(SpikeApi api, {required bool semantics}) async {
  if (!browser.isBrowser) return;
  browser.mark('sura-interactive');
  final payload = buildMetricsPayload(
    marks: browser.startupMarks(),
    env: browser.environment(),
    wasm: compiledToWasm,
    variant: browser.pageMeta('variant'),
    phase: browser.queryParam('phase').isEmpty
        ? 'manual'
        : browser.queryParam('phase'),
    run: browser.pageMeta('run'),
    device: browser.queryParam('d'),
    network: browser.queryParam('n'),
    semantics: semantics,
  );
  browser.reportProbe('startup', payload);
  if (browser.runKey().isNotEmpty) {
    try {
      await _reporter.report(payload);
    } on Object catch (e) {
      debugPrint('metrics not sent: $e');
    }
  }
  if (browser.queryParam('auto') == '1') browser.autoNext();
}

final _reporter = StartupReporter(SpikeApi().postMetrics);
