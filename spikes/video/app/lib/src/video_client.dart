import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';

import 'api.dart';

/// Connection quality as both providers report it, mapped to one scale.
enum SpikeQuality { excellent, good, poor, lost, unknown }

/// One person in the room as the UI shows it.
@immutable
class SpikeParticipant {
  const SpikeParticipant({
    required this.identity,
    required this.name,
    required this.isLocal,
    this.video,
    this.microphoneOn = false,
  });

  final String identity;
  final String name;
  final bool isLocal;

  /// Builds the video view, or null when there is no video to show (camera off, audio-only).
  final WidgetBuilder? video;
  final bool microphoneOn;
}

/// The spike's provider seam. It mirrors the planned `VideoClient` in `sura_classroom` (ADR-005):
/// join with a server-issued token, show participants, measure, and moderate as host.
abstract class SpikeVideoClient {
  ValueListenable<List<SpikeParticipant>> get participants;
  ValueListenable<SpikeQuality> get quality;

  /// True while the connection policy has dropped video to keep audio (CLS-14).
  ValueListenable<bool> get audioOnly;

  /// Completes once connected with microphone and camera published.
  Future<void> connect(TokenGrant grant);

  /// Completes when the first remote video frame has been decoded.
  Future<void> firstRemoteVideo();

  Future<void> setMicrophone({required bool on});
  Future<void> setCamera({required bool on});

  /// Host moderation that the provider runs in the client SDK (Zoom). Returns false when the
  /// provider moderates server-side instead (LiveKit), so the caller uses the spike server.
  Future<bool> muteAllInClient() async => false;
  Future<bool> removeInClient(String identity) async => false;
  Future<bool> setRecordingInClient({required bool on}) async => false;

  Future<void> disconnect();
  void dispose();
}
