import 'dart:async';

import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import 'session.dart';
import 'trials.dart';
import 'video_client.dart';

/// Runs [count] measured joins back to back (tap → first remote video), posts each to the spike
/// server, and shows median and p95. A bot must be in the room so there is a video to wait for.
class TrialsScreen extends StatefulWidget {
  const TrialsScreen({required this.request, required this.count, super.key});

  final JoinRequest request;
  final int count;

  @override
  State<TrialsScreen> createState() => _TrialsScreenState();
}

class _TrialsScreenState extends State<TrialsScreen> {
  late final SpikeVideoClient _client = widget.request.createClient();
  final _results = <TrialResult>[];
  bool _stopped = false;

  @override
  void initState() {
    super.initState();
    unawaited(_run());
  }

  Future<void> _run() async {
    for (var i = 1; i <= widget.count && !_stopped; i++) {
      final result = await measureJoin(
        index: i,
        fetchToken: widget.request.fetchToken,
        client: _client,
      );
      await _client.disconnect();
      unawaited(
        widget.request.api
            .metrics({
              ...widget.request.metricsBase(),
              ...result.toJson(),
              'mode': 'auto',
            })
            .catchError((_) {}),
      );
      if (!mounted) return;
      setState(() => _results.add(result));
      await Future<void>.delayed(const Duration(seconds: 2));
    }
  }

  @override
  void dispose() {
    _stopped = true;
    _client.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context);
    final done = _results.length == widget.count;
    final times = _results.map((r) => r.ok ? r.firstVideoMs : null).toList();
    String fmt(double? s) => s == null ? '—' : s.toStringAsFixed(2);

    return Scaffold(
      appBar: AppBar(title: Text(l.autoTrials(widget.count))),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsetsDirectional.all(16),
              child: Text(
                done
                    ? l.trialsDone(
                        _results.where((r) => r.ok).length,
                        widget.count,
                        fmt(percentileSeconds(times, 50)),
                        fmt(percentileSeconds(times, 95)),
                      )
                    : l.trialProgress(_results.length + 1, widget.count),
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ),
            if (!done) const LinearProgressIndicator(),
            Expanded(
              child: ListView(
                children: [
                  for (final r in _results.reversed)
                    ListTile(
                      dense: true,
                      leading: Icon(
                        r.ok ? Icons.check_circle : Icons.error,
                        color: r.ok
                            ? Colors.green
                            : Theme.of(context).colorScheme.error,
                      ),
                      title: r.ok
                          ? Text(
                              l.trialSeconds(
                                r.index,
                                fmt(r.firstVideoMs! / 1000),
                              ),
                            )
                          : Text(
                              '${r.index}. ${r.error}',
                              textDirection: TextDirection.ltr,
                            ),
                      subtitle: Text(
                        l.trialTimes(
                          '${r.tokenMs ?? '—'}',
                          '${r.connectedMs ?? '—'}',
                        ),
                      ),
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
