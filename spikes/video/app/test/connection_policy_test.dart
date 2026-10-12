import 'package:flutter_test/flutter_test.dart';
import 'package:sura_video_spike/src/connection_policy.dart';
import 'package:sura_video_spike/src/video_client.dart';

void main() {
  final t0 = DateTime.utc(2026, 11, 2, 15);
  DateTime at(int seconds) => t0.add(Duration(seconds: seconds));

  test('spike CLS-14 drops to audio-only after 5 s of poor quality', () {
    final policy = ConnectionPolicy();
    expect(policy.update(SpikeQuality.poor, at(0)), isFalse);
    expect(policy.update(SpikeQuality.poor, at(4)), isFalse);
    expect(policy.update(SpikeQuality.lost, at(5)), isTrue);
    expect(policy.audioOnly, isTrue);
  });

  test('spike CLS-14 a short dip does not switch', () {
    final policy = ConnectionPolicy();
    policy
      ..update(SpikeQuality.poor, at(0))
      ..update(SpikeQuality.good, at(3));
    expect(policy.update(SpikeQuality.poor, at(6)), isFalse);
    expect(policy.audioOnly, isFalse);
  });

  test('spike CLS-14 video returns after 10 s of good quality, not before', () {
    final policy = ConnectionPolicy()
      ..update(SpikeQuality.poor, at(0))
      ..update(SpikeQuality.poor, at(5));
    expect(policy.audioOnly, isTrue);
    expect(policy.update(SpikeQuality.good, at(6)), isFalse);
    expect(policy.update(SpikeQuality.excellent, at(15)), isFalse);
    expect(policy.update(SpikeQuality.good, at(16)), isTrue);
    expect(policy.audioOnly, isFalse);
  });

  test('spike CLS-14 unknown quality neither degrades nor recovers', () {
    final policy = ConnectionPolicy();
    expect(policy.update(SpikeQuality.unknown, at(0)), isFalse);
    expect(policy.update(SpikeQuality.unknown, at(60)), isFalse);
    expect(policy.audioOnly, isFalse);
  });
}
