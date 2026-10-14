import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_web_spike/src/measure/metrics_payload.dart';

void main() {
  const env = {
    'isolated': true,
    'sw': 'none',
    'ua': 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X)',
    'vendor': 'Apple Computer, Inc.',
    'transfer_kb': 3200,
    'encoded_kb': 3100,
    'engine_files': ['main.dart.wasm', 'skwasm.wasm'],
    'screen': '820x1180@2',
    'nickname': 'should never be sent',
  };

  Map<String, Object?> build({
    String device = 'ipad-safari',
    bool wasm = true,
  }) => buildMetricsPayload(
    marks: const {
      'boot': 12.4,
      'fcp': 80.2,
      'first-frame': 1500.7,
      'interactive': 1520.1,
      'other': 1,
    },
    env: env,
    wasm: wasm,
    variant: 'wasm/iso',
    phase: 'cold',
    run: 'r-ab12',
    device: device,
    network: 'wifi',
    semantics: true,
  );

  test(
    'spike NFR-perf startup marks are reported once with renderer info',
    () async {
      final payload = build();
      expect(payload['marks'], {
        'boot': 12,
        'fcp': 80,
        'first-frame': 1501,
        'interactive': 1520,
      });
      expect(payload['wasm'], isTrue);
      expect(payload['threads'], 'multi');
      expect(build(wasm: false)['threads'], 'js');

      var posts = 0;
      final reporter = StartupReporter((_) async => posts++);
      await reporter.report(payload);
      await reporter.report(payload);
      expect(posts, 1);
    },
  );

  test('spike PRV-10 metrics payload has only allow-listed fields', () {
    final payload = build(device: 'Ahmad iPad');
    expect(metricsFields.containsAll(payload.keys), isTrue);
    expect(payload.containsKey('nickname'), isFalse);
    expect(payload['device'], 'other');
    expect((payload['marks']! as Map).keys, isNot(contains('other')));
  });
}
