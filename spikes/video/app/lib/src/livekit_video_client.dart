import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:livekit_client/livekit_client.dart' as lk;

import 'api.dart';
import 'connection_policy.dart';
import 'video_client.dart';

/// LiveKit Cloud through `livekit_client` (web, iOS, Android from one code base).
class LiveKitVideoClient extends SpikeVideoClient {
  LiveKitVideoClient({this.publishMedia = true, this.log});

  /// Bots and trial runs publish too, like a real student with camera and microphone on.
  final bool publishMedia;

  /// When set, a `SPIKE_STATS {json}` line every 2 s: quality, audio-only, received video per
  /// participant (CLS-14 evidence from the throttled participant).
  final void Function(String line)? log;

  final _participants = ValueNotifier<List<SpikeParticipant>>(const []);
  final _quality = ValueNotifier<SpikeQuality>(SpikeQuality.unknown);
  final _audioOnly = ValueNotifier<bool>(false);
  final _policy = ConnectionPolicy();

  lk.Room? _room;
  lk.EventsListener<lk.RoomEvent>? _listener;
  Timer? _policyTick;
  Timer? _statsTick;
  bool _cameraWanted = true;

  @override
  ValueListenable<List<SpikeParticipant>> get participants => _participants;
  @override
  ValueListenable<SpikeQuality> get quality => _quality;
  @override
  ValueListenable<bool> get audioOnly => _audioOnly;

  @override
  Future<void> connect(TokenGrant grant) async {
    await disconnect();
    final room = lk.Room(
      roomOptions: const lk.RoomOptions(
        adaptiveStream: true,
        dynacast: true,
        defaultVideoPublishOptions: lk.VideoPublishOptions(simulcast: true),
      ),
    );
    _room = room;
    _listener = room.createListener()
      ..on<lk.ParticipantConnectionQualityUpdatedEvent>((e) {
        if (e.participant is lk.LocalParticipant) {
          _onQuality(_map(e.connectionQuality));
        }
      })
      ..on<lk.TrackSubscribedEvent>((e) {
        if (_policy.audioOnly && e.publication.kind == lk.TrackType.VIDEO) {
          unawaited(e.publication.disable());
        }
        _rebuild();
      })
      ..on<lk.RoomEvent>((_) => _rebuild());
    await room.connect(
      grant.serverUrl!,
      grant.token,
      fastConnectOptions: lk.FastConnectOptions(
        microphone: lk.TrackOption(enabled: publishMedia),
        camera: lk.TrackOption(enabled: publishMedia),
      ),
    );
    _cameraWanted = publishMedia;
    _policyTick = Timer.periodic(
      const Duration(seconds: 1),
      (_) => _onQuality(_quality.value),
    );
    if (log != null) {
      _statsTick = Timer.periodic(
        const Duration(seconds: 2),
        (_) => unawaited(_logStats()),
      );
    }
    _rebuild();
  }

  @override
  Future<void> firstRemoteVideo() async {
    while (true) {
      final room = _room;
      if (room == null) throw StateError('not connected');
      // Copies: the awaits below let participants join or leave mid-loop (seen once on iPhone).
      for (final p in room.remoteParticipants.values.toList()) {
        for (final pub in p.videoTrackPublications.toList()) {
          final track = pub.track;
          if (track is lk.RemoteVideoTrack) {
            final stats = await track.getReceiverStats();
            if ((stats?.framesDecoded ?? 0) > 0) return;
          }
        }
      }
      await Future<void>.delayed(const Duration(milliseconds: 50));
    }
  }

  @override
  Future<void> setMicrophone({required bool on}) async {
    await _room?.localParticipant?.setMicrophoneEnabled(on);
    _rebuild();
  }

  @override
  Future<void> setCamera({required bool on}) async {
    _cameraWanted = on;
    await _room?.localParticipant?.setCameraEnabled(on && !_policy.audioOnly);
    _rebuild();
  }

  void _onQuality(SpikeQuality q) {
    _quality.value = q;
    if (!_policy.update(q, DateTime.now())) return;
    _audioOnly.value = _policy.audioOnly;
    final room = _room;
    if (room == null) return;
    for (final p in room.remoteParticipants.values) {
      for (final pub in p.videoTrackPublications) {
        unawaited(_policy.audioOnly ? pub.disable() : pub.enable());
      }
    }
    unawaited(
      room.localParticipant?.setCameraEnabled(
        _cameraWanted && !_policy.audioOnly,
      ),
    );
    _rebuild();
  }

  static SpikeQuality _map(lk.ConnectionQuality q) => switch (q) {
    lk.ConnectionQuality.excellent => SpikeQuality.excellent,
    lk.ConnectionQuality.good => SpikeQuality.good,
    lk.ConnectionQuality.poor => SpikeQuality.poor,
    lk.ConnectionQuality.lost => SpikeQuality.lost,
    lk.ConnectionQuality.unknown => SpikeQuality.unknown,
  };

  void _rebuild() {
    final room = _room;
    if (room == null) {
      _participants.value = const [];
      return;
    }
    SpikeParticipant view(lk.Participant p) {
      lk.VideoTrack? video;
      for (final pub in p.videoTrackPublications) {
        final track = pub.track;
        // Not `pub.enabled`: with adaptive stream a remote track counts as disabled until a view
        // of it is on screen, so filtering on it would never show (and never receive) the video.
        if (track is lk.VideoTrack &&
            !pub.muted &&
            !(pub is lk.RemoteTrackPublication && _policy.audioOnly)) {
          video = track;
          if (pub.source == lk.TrackSource.screenShareVideo) break;
        }
      }
      final shown = video;
      return SpikeParticipant(
        identity: p.identity,
        name: p.name.isEmpty ? p.identity : p.name,
        isLocal: p is lk.LocalParticipant,
        microphoneOn: p.isMicrophoneEnabled(),
        video: shown == null
            ? null
            : (_) => lk.VideoTrackRenderer(shown, fit: lk.VideoViewFit.cover),
      );
    }

    _participants.value = [
      if (room.localParticipant != null) view(room.localParticipant!),
      ...room.remoteParticipants.values.map(view),
    ];
  }

  Future<void> _logStats() async {
    final room = _room;
    final sink = log;
    if (room == null || sink == null) return;
    final remote = <Map<String, Object?>>[];
    for (final p in room.remoteParticipants.values) {
      for (final pub in p.videoTrackPublications) {
        final track = pub.track;
        if (track is! lk.RemoteVideoTrack) continue;
        final s = await track.getReceiverStats();
        remote.add({
          'identity': p.identity,
          'enabled': pub.enabled,
          'fps': s?.framesPerSecond,
          'w': s?.frameWidth,
          'h': s?.frameHeight,
          'lost': s?.packetsLost,
          'bytes': s?.bytesReceived,
        });
      }
    }
    sink(
      'SPIKE_STATS ${jsonEncode({'t': DateTime.now().toUtc().toIso8601String(), 'quality': _quality.value.name, 'audio_only': _policy.audioOnly, 'remote_video': remote})}',
    );
  }

  @override
  Future<void> disconnect() async {
    _policyTick?.cancel();
    _policyTick = null;
    _statsTick?.cancel();
    _statsTick = null;
    await _listener?.dispose();
    _listener = null;
    final room = _room;
    _room = null;
    if (room != null) {
      await room.disconnect();
      await room.dispose();
    }
    _policy.audioOnly = false;
    _audioOnly.value = false;
    _quality.value = SpikeQuality.unknown;
    _rebuild();
  }

  @override
  void dispose() {
    unawaited(disconnect());
    _participants.dispose();
    _quality.dispose();
    _audioOnly.dispose();
  }
}
