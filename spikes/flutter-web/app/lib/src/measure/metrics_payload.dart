/// Load-time payload posted to the spike server's `/metrics` (D-22 evidence).
///
/// Only allow-listed fields leave the browser: fixed device and network labels, timings, renderer
/// facts and the user agent. No names, no free text (PRV-10, child-privacy rules).
library;

const metricsFields = {
  'variant',
  'phase',
  'run',
  'device',
  'network',
  'semantics',
  'marks',
  'wasm',
  'threads', //
  'isolated',
  'sw',
  'ua',
  'vendor',
  'transfer_kb',
  'encoded_kb',
  'engine_files',
  'screen',
};

const deviceLabels = {
  'pc-chrome',
  'ipad-safari',
  'chromebook-chrome',
  'android-chrome',
  'iphone-safari', //
  'android-app', 'headless-chrome', 'other',
};

const networkLabels = {
  'wifi',
  '4g',
  '5g',
  'ethernet',
  'unthrottled',
  'throttled-slow-4g',
  'throttled-fast-4g', //
  'unknown',
};

const markNames = {
  'boot',
  'fcp',
  'lcp',
  'entrypoint',
  'engine',
  'app-running',
  'first-frame',
  'interactive',
};

const _envFields = {
  'isolated',
  'sw',
  'ua',
  'vendor',
  'transfer_kb',
  'encoded_kb',
  'engine_files',
  'screen',
};

Map<String, Object?> buildMetricsPayload({
  required Map<String, double> marks,
  required Map<String, Object?> env,
  required bool wasm,
  required String variant,
  required String phase,
  required String run,
  required String device,
  required String network,
  required bool semantics,
}) {
  final isolated = env['isolated'] == true;
  return {
    'variant': variant,
    'phase': phase,
    'run': run,
    'device': deviceLabels.contains(device) ? device : 'other',
    'network': networkLabels.contains(network) ? network : 'unknown',
    'semantics': semantics,
    'marks': {
      for (final e in marks.entries)
        if (markNames.contains(e.key)) e.key: e.value.round(),
    },
    'wasm': wasm,
    // Skwasm runs multi-threaded only when the page is cross-origin isolated (flutter.js loader).
    'threads': wasm ? (isolated ? 'multi' : 'single') : 'js',
    for (final e in env.entries)
      if (_envFields.contains(e.key)) e.key: e.value,
  };
}

/// Posts the startup payload exactly once per page load.
class StartupReporter {
  StartupReporter(this._post);

  final Future<void> Function(Map<String, Object?> payload) _post;
  bool _sent = false;

  Future<void> report(Map<String, Object?> payload) async {
    if (_sent) return;
    _sent = true;
    await _post(payload);
  }
}
