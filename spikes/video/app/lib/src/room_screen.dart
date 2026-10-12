import 'dart:async';

import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import 'api.dart';
import 'session.dart';
import 'trials.dart';
import 'video_client.dart';

/// The call: up to four video tiles, own mic/camera, and the host panel for teacher/substitute.
/// A bot (`bot: true`) keeps rejoining so the trials always have a remote video to wait for.
class RoomScreen extends StatefulWidget {
  const RoomScreen({
    required this.request,
    this.bot = false,
    this.stats = false,
    super.key,
  });

  final JoinRequest request;
  final bool bot;

  /// Log `SPIKE_STATS` lines to the console (the throttled participant's evidence).
  final bool stats;

  @override
  State<RoomScreen> createState() => _RoomScreenState();
}

class _RoomScreenState extends State<RoomScreen> {
  late final SpikeVideoClient _client = widget.request.createClient(
    log: widget.stats ? debugPrint : null,
  );
  TokenGrant? _grant;
  TrialResult? _join;
  String? _error;
  bool _mic = true;
  bool _camera = true;
  bool _locked = false;
  String? _egressId;
  bool _recording = false;
  bool _closed = false;

  @override
  void initState() {
    super.initState();
    unawaited(_connect());
  }

  Future<void> _connect() async {
    final result = await measureJoin(
      index: 0,
      fetchToken: () async => _grant = await widget.request.fetchToken(),
      client: _client,
      videoTimeout: const Duration(minutes: 5),
    );
    if (!mounted || _closed) return;
    setState(() {
      _join = result;
      _error = result.error;
    });
    if (!widget.bot) {
      unawaited(
        widget.request.api
            .metrics({
              ...widget.request.metricsBase(),
              ...result.toJson(),
              'mode': 'manual',
            })
            .catchError((_) {}),
      );
    }
    if (widget.bot && !result.ok && result.connectedMs == null) {
      await Future<void>.delayed(const Duration(seconds: 3));
      if (mounted && !_closed) await _connect();
    }
  }

  Future<void> _host(Future<Object?> Function() action) async {
    final l = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await action();
      messenger.showSnackBar(SnackBar(content: Text(l.done)));
    } on SpikeApiException catch (e) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(l.error(e.messageAr.isEmpty ? e.code : e.messageAr)),
        ),
      );
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(l.error('$e'))));
    }
  }

  Future<void> _muteAll() => _host(() async {
    if (!await _client.muteAllInClient()) {
      await widget.request.api.admin(_grant!, 'mute-all');
    }
    return null;
  });

  Future<void> _remove(String identity) => _host(() async {
    if (!await _client.removeInClient(identity)) {
      await widget.request.api.admin(_grant!, 'remove', {'identity': identity});
    }
    return null;
  });

  Future<void> _toggleLock() => _host(() async {
    await widget.request.api.admin(_grant!, _locked ? 'unlock' : 'lock');
    setState(() => _locked = !_locked);
    return null;
  });

  Future<void> _toggleRecording() => _host(() async {
    final on = !_recording;
    if (!await _client.setRecordingInClient(on: on)) {
      if (on) {
        _egressId =
            (await widget.request.api.admin(
                  _grant!,
                  'record/start',
                ))['egress_id']
                as String?;
      } else {
        await widget.request.api.admin(_grant!, 'record/stop', {
          'egress_id': _egressId,
        });
      }
    }
    setState(() => _recording = on);
    return null;
  });

  Future<void> _leave() async {
    _closed = true;
    await _client.disconnect();
    if (mounted) Navigator.of(context).maybePop();
  }

  @override
  void dispose() {
    _closed = true;
    _client.dispose();
    super.dispose();
  }

  String _qualityLabel(AppLocalizations l, SpikeQuality q) => switch (q) {
    SpikeQuality.excellent => l.qualityExcellent,
    SpikeQuality.good => l.qualityGood,
    SpikeQuality.poor => l.qualityPoor,
    SpikeQuality.lost => l.qualityLost,
    SpikeQuality.unknown => l.qualityUnknown,
  };

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context);
    final isHost = _grant?.adminTicket != null;
    final join = _join;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.request.room),
        actions: [
          ValueListenableBuilder<bool>(
            valueListenable: _client.audioOnly,
            builder: (_, on, _) =>
                on ? Chip(label: Text(l.audioOnly)) : const SizedBox.shrink(),
          ),
          const SizedBox(width: 8),
          ValueListenableBuilder<SpikeQuality>(
            valueListenable: _client.quality,
            builder: (_, q, _) => Text(l.quality(_qualityLabel(l, q))),
          ),
          const SizedBox(width: 12),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsetsDirectional.symmetric(
                horizontal: 16,
                vertical: 8,
              ),
              child: Align(
                alignment: AlignmentDirectional.centerStart,
                child: Text(
                  _error != null
                      ? l.error(_error!)
                      : join?.firstVideoMs != null
                      ? l.joinTime(
                          (join!.firstVideoMs! / 1000).toStringAsFixed(2),
                        )
                      : l.waitingVideo,
                ),
              ),
            ),
            Expanded(
              child: ValueListenableBuilder<List<SpikeParticipant>>(
                valueListenable: _client.participants,
                builder: (context, people, _) => GridView.count(
                  crossAxisCount: MediaQuery.sizeOf(context).width > 700
                      ? 3
                      : 2,
                  padding: const EdgeInsetsDirectional.all(8),
                  mainAxisSpacing: 8,
                  crossAxisSpacing: 8,
                  childAspectRatio: 4 / 3,
                  children: [
                    for (final p in people)
                      _Tile(
                        participant: p,
                        youLabel: l.you,
                        onRemove: isHost && !p.isLocal
                            ? () => _remove(p.identity)
                            : null,
                        removeLabel: l.remove,
                      ),
                  ],
                ),
              ),
            ),
            if (isHost)
              Padding(
                padding: const EdgeInsetsDirectional.symmetric(horizontal: 8),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    OutlinedButton.icon(
                      onPressed: _muteAll,
                      icon: const Icon(Icons.mic_off),
                      label: Text(l.muteAll),
                    ),
                    OutlinedButton.icon(
                      onPressed: _toggleLock,
                      icon: Icon(_locked ? Icons.lock_open : Icons.lock),
                      label: Text(_locked ? l.unlockRoom : l.lockRoom),
                    ),
                    OutlinedButton.icon(
                      onPressed: _toggleRecording,
                      icon: Icon(
                        _recording
                            ? Icons.stop_circle
                            : Icons.fiber_manual_record,
                      ),
                      label: Text(_recording ? l.recordStop : l.recordStart),
                    ),
                  ],
                ),
              ),
            Padding(
              padding: const EdgeInsetsDirectional.all(12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                spacing: 12,
                children: [
                  IconButton.filledTonal(
                    tooltip: l.microphone,
                    iconSize: 28,
                    icon: Icon(_mic ? Icons.mic : Icons.mic_off),
                    onPressed: () async {
                      setState(() => _mic = !_mic);
                      await _client.setMicrophone(on: _mic);
                    },
                  ),
                  IconButton.filledTonal(
                    tooltip: l.camera,
                    iconSize: 28,
                    icon: Icon(_camera ? Icons.videocam : Icons.videocam_off),
                    onPressed: () async {
                      setState(() => _camera = !_camera);
                      await _client.setCamera(on: _camera);
                    },
                  ),
                  FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: Theme.of(context).colorScheme.error,
                    ),
                    onPressed: _leave,
                    icon: const Icon(Icons.call_end),
                    label: Text(l.leave),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Tile extends StatelessWidget {
  const _Tile({
    required this.participant,
    required this.youLabel,
    required this.removeLabel,
    this.onRemove,
  });

  final SpikeParticipant participant;
  final String youLabel;
  final String removeLabel;
  final VoidCallback? onRemove;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final video = participant.video;
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: ColoredBox(
        color: scheme.surfaceContainerHighest,
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (video != null)
              video(context)
            else
              Center(
                child: Icon(
                  Icons.person,
                  size: 48,
                  color: scheme.onSurfaceVariant,
                ),
              ),
            PositionedDirectional(
              start: 8,
              bottom: 8,
              end: 8,
              child: Row(
                children: [
                  Icon(
                    participant.microphoneOn ? Icons.mic : Icons.mic_off,
                    size: 18,
                    color: scheme.onSurface,
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      participant.isLocal
                          ? '${participant.name} ($youLabel)'
                          : participant.name,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: scheme.onSurface,
                        backgroundColor: scheme.surface.withValues(alpha: 0.6),
                      ),
                    ),
                  ),
                  if (onRemove != null)
                    IconButton(
                      tooltip: removeLabel,
                      icon: const Icon(Icons.person_remove, size: 20),
                      onPressed: onRemove,
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
