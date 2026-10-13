import 'dart:async';
import 'dart:math';

import 'package:flutter/material.dart';
import 'package:livekit_client/livekit_client.dart' as lk;

import '../../l10n/app_localizations.dart';
import '../api.dart';
import '../platform/browser.dart' as browser;

/// Does LiveKit still work when the page is cross-origin isolated? Joins one test room on the
/// local LiveKit dev server or LiveKit Cloud and shows own and remote video.
class VideoPage extends StatefulWidget {
  const VideoPage({super.key});

  @override
  State<VideoPage> createState() => _VideoPageState();
}

enum _Phase { idle, connecting, connected, failed }

class _VideoPageState extends State<VideoPage> {
  final _api = SpikeApi();
  String _target = browser.queryParam('video') == 'cloud' ? 'cloud' : 'local';
  _Phase _phase = _Phase.idle;
  String _error = '';
  lk.Room? _room;
  lk.EventsListener<lk.RoomEvent>? _listener;

  @override
  void initState() {
    super.initState();
    if (browser.queryParam('video').isNotEmpty) unawaited(_join());
  }

  @override
  void dispose() {
    unawaited(_leave());
    super.dispose();
  }

  Future<void> _join() async {
    setState(() => _phase = _Phase.connecting);
    try {
      final identity = 'tester-${Random().nextInt(1 << 20).toRadixString(16)}';
      final grant = await _api.liveKitToken(
        target: _target,
        room: 'wp05-isolation',
        identity: identity,
      );
      final room = lk.Room();
      _listener = room.createListener()..on<lk.RoomEvent>((_) => _changed());
      await room.connect(
        grant.url,
        grant.token,
        fastConnectOptions: lk.FastConnectOptions(
          camera: const lk.TrackOption(enabled: true),
          microphone: const lk.TrackOption(enabled: false),
        ),
      );
      _room = room;
      setState(() => _phase = _Phase.connected);
    } on Object catch (e) {
      setState(() {
        _phase = _Phase.failed;
        _error = e is SpikeApiException
            ? e.message(Localizations.localeOf(context).languageCode)
            : '$e';
      });
    }
    _changed();
  }

  Future<void> _leave() async {
    await _listener?.dispose();
    await _room?.disconnect();
    _room = null;
    if (mounted) setState(() => _phase = _Phase.idle);
    _changed();
  }

  List<lk.VideoTrack> get _remoteVideos => [
    for (final p
        in _room?.remoteParticipants.values ?? const <lk.RemoteParticipant>[])
      for (final pub in p.videoTrackPublications)
        if (pub.track is lk.VideoTrack && !pub.muted)
          pub.track! as lk.VideoTrack,
  ];

  void _changed() {
    browser.reportProbe('video', {
      'target': _target,
      'phase': _phase.name,
      'isolated': browser.environment()['isolated'],
      'remote_participants': _room?.remoteParticipants.length ?? 0,
      'remote_videos': _remoteVideos.length,
      'local_video':
          _room?.localParticipant?.videoTrackPublications.isNotEmpty ?? false,
      'error': _error,
    });
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    if (!browser.isBrowser) return Center(child: Text(l10n.webOnly));
    final status = switch (_phase) {
      _Phase.idle => l10n.videoDisconnected,
      _Phase.connecting => l10n.videoConnecting,
      _Phase.connected => l10n.videoConnected,
      _Phase.failed => l10n.videoFailed(_error),
    };
    final local =
        _room?.localParticipant?.videoTrackPublications.firstOrNull?.track;
    return ListView(
      padding: const EdgeInsetsDirectional.all(16),
      children: [
        SegmentedButton<String>(
          segments: [
            ButtonSegment(value: 'local', label: Text(l10n.videoTargetLocal)),
            ButtonSegment(value: 'cloud', label: Text(l10n.videoTargetCloud)),
          ],
          selected: {_target},
          onSelectionChanged: _phase == _Phase.connected
              ? null
              : (s) => setState(() => _target = s.first),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 12,
          children: [
            FilledButton(
              onPressed: _phase == _Phase.connected ? null : _join,
              child: Text(l10n.videoJoin),
            ),
            OutlinedButton(
              onPressed: _phase == _Phase.connected ? _leave : null,
              child: Text(l10n.videoLeave),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Semantics(liveRegion: true, child: Text(status)),
        Text(l10n.videoRemoteCount(_room?.remoteParticipants.length ?? 0)),
        const SizedBox(height: 12),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            if (local != null) _tile(local, l10n.videoYou),
            for (final v in _remoteVideos) _tile(v, null),
          ],
        ),
      ],
    );
  }

  Widget _tile(lk.VideoTrack track, String? label) => SizedBox(
    width: 240,
    height: 180,
    child: Stack(
      fit: StackFit.expand,
      children: [
        lk.VideoTrackRenderer(track, fit: lk.VideoViewFit.cover),
        if (label != null)
          PositionedDirectional(
            start: 8,
            bottom: 8,
            child: ColoredBox(
              color: Colors.black54,
              child: Padding(
                padding: const EdgeInsetsDirectional.symmetric(horizontal: 6),
                child: Text(label, style: const TextStyle(color: Colors.white)),
              ),
            ),
          ),
      ],
    ),
  );
}
