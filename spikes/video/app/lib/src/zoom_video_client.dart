import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_zoom_videosdk/flutter_zoom_view.dart' as zoom_view;
import 'package:flutter_zoom_videosdk/native/zoom_videosdk.dart';
import 'package:flutter_zoom_videosdk/native/zoom_videosdk_event_listener.dart';
import 'package:flutter_zoom_videosdk/native/zoom_videosdk_user.dart';

import 'api.dart';
import 'connection_policy.dart';
import 'video_client.dart';

/// Zoom Video SDK through `flutter_zoom_videosdk` (Android and iOS only; no Flutter web).
/// Host moderation and cloud recording run here, in the client SDK, with `role_type` 1.
class ZoomVideoClient extends SpikeVideoClient {
  final _zoom = ZoomVideoSdk();
  final _events = ZoomVideoSdkEventListener();
  final _participants = ValueNotifier<List<SpikeParticipant>>(const []);
  final _quality = ValueNotifier<SpikeQuality>(SpikeQuality.unknown);
  final _audioOnly = ValueNotifier<bool>(false);
  final _policy = ConnectionPolicy();
  final _subscriptions = <dynamic>[];

  static bool _initialized = false;
  Completer<void>? _joined;
  Timer? _policyTick;
  bool _cameraWanted = true;
  List<ZoomVideoSdkUser> _users = const [];
  String? _myId;

  @override
  ValueListenable<List<SpikeParticipant>> get participants => _participants;
  @override
  ValueListenable<SpikeQuality> get quality => _quality;
  @override
  ValueListenable<bool> get audioOnly => _audioOnly;

  @override
  Future<void> connect(TokenGrant grant) async {
    await disconnect();
    if (!_initialized) {
      await _zoom.initSdk(InitConfig(domain: 'zoom.us', enableLog: false));
      _initialized = true;
    }
    final joined = _joined = Completer<void>();
    _subscriptions
      ..add(
        _events.addListener(EventType.onSessionJoin, (dynamic data) async {
          final me = ZoomVideoSdkUser.fromJson(
            jsonDecode((data as Map)['sessionUser'] as String)
                as Map<String, dynamic>,
          );
          _myId = me.userId;
          await _refreshUsers();
          if (!joined.isCompleted) joined.complete();
        }),
      )
      ..add(
        _events.addListener(
          EventType.onUserJoin,
          (dynamic _) => _refreshUsers(),
        ),
      )
      ..add(
        _events.addListener(
          EventType.onUserLeave,
          (dynamic _) => _refreshUsers(),
        ),
      )
      ..add(
        _events.addListener(
          EventType.onUserVideoStatusChanged,
          (dynamic _) => _refreshUsers(),
        ),
      )
      ..add(
        _events.addListener(
          EventType.onUserAudioStatusChanged,
          (dynamic _) => _refreshUsers(),
        ),
      )
      ..add(
        _events.addListener(EventType.onUserOverallNetworkStatusChanged, (
          dynamic data,
        ) {
          final user = ZoomVideoSdkUser.fromJson(
            jsonDecode((data as Map)['user'] as String) as Map<String, dynamic>,
          );
          if (user.userId == _myId) _onQuality(_map('${data['level']}'));
        }),
      )
      ..add(
        _events.addListener(EventType.onError, (dynamic data) {
          if (!joined.isCompleted) {
            joined.completeError(StateError('zoom error: $data'));
          }
        }),
      );

    await _zoom.joinSession(
      JoinSessionConfig(
        sessionName: grant.sessionName,
        token: grant.token,
        userName: grant.displayName,
        audioOptions: {
          'connect': true,
          'mute': false,
          'autoAdjustSpeakerVolume': false,
        },
        videoOptions: {'localVideoOn': true},
        sessionIdleTimeoutMins: 40,
      ),
    );
    await joined.future;
    _cameraWanted = true;
    _policyTick = Timer.periodic(
      const Duration(seconds: 1),
      (_) => _onQuality(_quality.value),
    );
  }

  /// Zoom has no frame callback in Flutter; the first remote user whose received video reports
  /// a frame rate or size counts as "video visible". Polled every 100 ms.
  @override
  Future<void> firstRemoteVideo() async {
    while (true) {
      if (_joined == null) throw StateError('not connected');
      for (final user in _users.where((u) => u.userId != _myId)) {
        final stats = user.videoStatisticInfo;
        if (stats != null &&
            ((await stats.getFps()) > 0 || (await stats.getWidth()) > 0)) {
          return;
        }
      }
      await Future<void>.delayed(const Duration(milliseconds: 100));
      await _refreshUsers();
    }
  }

  @override
  Future<void> setMicrophone({required bool on}) async {
    final me = _myId;
    if (me == null) return;
    await (on
        ? _zoom.audioHelper.unMuteAudio(me)
        : _zoom.audioHelper.muteAudio(me));
    await _refreshUsers();
  }

  @override
  Future<void> setCamera({required bool on}) async {
    _cameraWanted = on;
    await (on && !_policy.audioOnly
        ? _zoom.videoHelper.startVideo()
        : _zoom.videoHelper.stopVideo());
    await _refreshUsers();
  }

  @override
  Future<bool> muteAllInClient() async {
    await _zoom.audioHelper.muteAllAudio(true);
    return true;
  }

  @override
  Future<bool> removeInClient(String identity) async {
    for (final user in _users) {
      if (user.customUserId == identity) {
        return _zoom.userHelper.removeUser(user.userId);
      }
    }
    return false;
  }

  @override
  Future<bool> setRecordingInClient({required bool on}) async {
    final result = on
        ? await _zoom.recordingHelper.startCloudRecording()
        : await _zoom.recordingHelper.stopCloudRecording();
    debugPrint('zoom cloud recording ${on ? 'start' : 'stop'}: $result');
    return true;
  }

  /// Zoom reports network level as a string such as "Good", "Normal", "Bad" or "None".
  static SpikeQuality _map(String level) => switch (level.toLowerCase()) {
    final l when l.contains('good') => SpikeQuality.excellent,
    final l when l.contains('normal') => SpikeQuality.good,
    final l when l.contains('bad') => SpikeQuality.poor,
    final l when l.contains('none') => SpikeQuality.lost,
    _ => SpikeQuality.unknown,
  };

  void _onQuality(SpikeQuality q) {
    _quality.value = q;
    if (!_policy.update(q, DateTime.now())) return;
    _audioOnly.value = _policy.audioOnly;
    if (_policy.audioOnly) {
      unawaited(_zoom.videoHelper.stopVideo());
    } else if (_cameraWanted) {
      unawaited(_zoom.videoHelper.startVideo());
    }
    unawaited(_refreshUsers());
  }

  Future<void> _refreshUsers() async {
    final me = await _zoom.session.getMySelf();
    final remote =
        await _zoom.session.getRemoteUsers() ?? const <ZoomVideoSdkUser>[];
    _users = [?me, ...remote];
    final views = <SpikeParticipant>[];
    for (final user in _users) {
      final videoOn = await user.videoStatus?.isOn() ?? false;
      final muted = await user.audioStatus?.isMuted() ?? true;
      views.add(
        SpikeParticipant(
          identity: user.customUserId.isEmpty ? user.userId : user.customUserId,
          name: user.userName,
          isLocal: user.userId == _myId,
          microphoneOn: !muted,
          video: videoOn && !_policy.audioOnly
              ? (_) => zoom_view.View(
                  key: ValueKey('zoom-${user.userId}'),
                  creationParams: <String, dynamic>{
                    'userId': user.userId,
                    'sharing': false,
                    'preview': false,
                    'focused': false,
                    'hasMultiCamera': false,
                    'isPiPView': false,
                    'videoAspect': VideoAspect.PanAndScan,
                    'fullScreen': false,
                    'resolution': VideoResolution.Resolution360,
                  },
                )
              : null,
        ),
      );
    }
    _participants.value = views;
  }

  @override
  Future<void> disconnect() async {
    _policyTick?.cancel();
    _policyTick = null;
    for (final s in _subscriptions) {
      // ignore: avoid_dynamic_calls
      s.cancel();
    }
    _subscriptions.clear();
    if (_joined != null) {
      _joined = null;
      await _zoom.leaveSession(false);
    }
    _myId = null;
    _users = const [];
    _policy.audioOnly = false;
    _audioOnly.value = false;
    _quality.value = SpikeQuality.unknown;
    _participants.value = const [];
  }

  @override
  void dispose() {
    unawaited(disconnect());
    _participants.dispose();
    _quality.dispose();
    _audioOnly.dispose();
  }
}
