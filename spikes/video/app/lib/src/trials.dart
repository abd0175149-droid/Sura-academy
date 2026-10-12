import 'dart:async';
import 'dart:math' as math;

import 'api.dart';
import 'video_client.dart';

/// One join attempt: tap → token → connected → first remote video frame (NFR: < 10 s at p95).
class TrialResult {
  const TrialResult({
    required this.index,
    this.tokenMs,
    this.connectedMs,
    this.firstVideoMs,
    this.error,
  });

  final int index;
  final int? tokenMs;
  final int? connectedMs;
  final int? firstVideoMs;
  final String? error;

  bool get ok => firstVideoMs != null && error == null;

  Map<String, Object?> toJson() => {
    'trial': index,
    't_token_ms': tokenMs,
    't_connected_ms': connectedMs,
    't_first_video_ms': firstVideoMs,
    'ok': ok,
    if (error != null) 'error': error,
  };
}

/// Nearest-rank percentile in seconds. A failed trial (null) counts as slower than any success,
/// so more than 5 % failures make p95 null: the NFR is then not met.
double? percentileSeconds(List<int?> millis, double p) {
  if (millis.isEmpty) return null;
  final sorted = [...millis]
    ..sort((a, b) {
      if (a == null) return b == null ? 0 : 1;
      if (b == null) return -1;
      return a.compareTo(b);
    });
  final rank = math.max(1, (p / 100 * sorted.length).ceil());
  final value = sorted[rank - 1];
  return value == null ? null : value / 1000;
}

/// Runs one measured join with [client], then leaves. Timings are from the moment of the tap.
Future<TrialResult> measureJoin({
  required int index,
  required Future<TokenGrant> Function() fetchToken,
  required SpikeVideoClient client,
  Duration videoTimeout = const Duration(seconds: 30),
}) async {
  final clock = Stopwatch()..start();
  int? tokenMs;
  int? connectedMs;
  try {
    final grant = await fetchToken();
    tokenMs = clock.elapsedMilliseconds;
    await client.connect(grant);
    connectedMs = clock.elapsedMilliseconds;
    await client.firstRemoteVideo().timeout(videoTimeout);
    return TrialResult(
      index: index,
      tokenMs: tokenMs,
      connectedMs: connectedMs,
      firstVideoMs: clock.elapsedMilliseconds,
    );
  } on TimeoutException {
    return TrialResult(
      index: index,
      tokenMs: tokenMs,
      connectedMs: connectedMs,
      error: 'no remote video in ${videoTimeout.inSeconds}s',
    );
  } on Object catch (e) {
    return TrialResult(
      index: index,
      tokenMs: tokenMs,
      connectedMs: connectedMs,
      error: e.toString(),
    );
  }
}
