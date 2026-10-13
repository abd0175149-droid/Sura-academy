import 'video_client.dart';

/// CLS-14 probe: drop to audio-only after [degradeAfter] of poor/lost quality, and bring video back
/// after [recoverAfter] of good/excellent quality. Hysteresis keeps it from flapping.
class ConnectionPolicy {
  ConnectionPolicy({
    this.degradeAfter = const Duration(seconds: 5),
    this.recoverAfter = const Duration(seconds: 10),
  });

  final Duration degradeAfter;
  final Duration recoverAfter;

  bool audioOnly = false;
  DateTime? _badSince;
  DateTime? _goodSince;

  /// Feed every quality report and a periodic tick; returns true when [audioOnly] changed.
  bool update(SpikeQuality quality, DateTime now) {
    final bad = quality == SpikeQuality.poor || quality == SpikeQuality.lost;
    final good =
        quality == SpikeQuality.good || quality == SpikeQuality.excellent;
    _badSince = bad ? (_badSince ?? now) : null;
    _goodSince = good ? (_goodSince ?? now) : null;

    if (!audioOnly &&
        _badSince != null &&
        now.difference(_badSince!) >= degradeAfter) {
      audioOnly = true;
      return true;
    }
    if (audioOnly &&
        _goodSince != null &&
        now.difference(_goodSince!) >= recoverAfter) {
      audioOnly = false;
      return true;
    }
    return false;
  }
}
