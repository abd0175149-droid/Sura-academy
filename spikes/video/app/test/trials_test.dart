import 'package:flutter_test/flutter_test.dart';
import 'package:sura_video_spike/src/trials.dart';

void main() {
  group('spike NFR-join time to first video percentiles', () {
    test('nearest-rank median and p95 in seconds', () {
      final ms = [for (var i = 1; i <= 20; i++) i * 100];
      expect(percentileSeconds(ms, 50), 1.0);
      expect(percentileSeconds(ms, 95), 1.9);
    });

    test('one failure in 20 keeps p95 measurable, two make it fail', () {
      final one = [for (var i = 1; i <= 19; i++) 1000, null];
      expect(percentileSeconds(one, 95), 1.0);
      final two = [for (var i = 1; i <= 18; i++) 1000, null, null];
      expect(percentileSeconds(two, 95), isNull);
    });

    test('no trials gives no number', () {
      expect(percentileSeconds([], 95), isNull);
    });
  });
}
