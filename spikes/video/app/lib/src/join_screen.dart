import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import 'api.dart';
import 'room_screen.dart';
import 'session.dart';
import 'trials_screen.dart';
import 'zoom_factory.dart';

/// Join form. On the web the spike server is the page's own origin; on phones it is typed once.
/// `?bot=1&code=…&room=…&seat=…&provider=livekit` joins straight away as a camera bot;
/// `?auto=trials&code=…&device=…&count=…` runs the join trials headless (tools/bot.mjs --mode trials).
class JoinScreen extends StatefulWidget {
  const JoinScreen({super.key});

  @override
  State<JoinScreen> createState() => _JoinScreenState();
}

class _JoinScreenState extends State<JoinScreen> {
  static const _trialCount = 20;
  final _form = GlobalKey<FormState>();
  final _server = TextEditingController(
    text: const String.fromEnvironment('SPIKE_SERVER'),
  );
  final _code = TextEditingController();
  final _room = TextEditingController(text: 'spike-room');
  final _device = TextEditingController();
  String _provider = 'livekit';
  String _role = 'student';
  int _seat = 1;
  String _network = 'normal';

  @override
  void initState() {
    super.initState();
    final q = Uri.base.queryParameters;
    _code.text = q['code'] ?? '';
    if (kIsWeb && q['bot'] == '1') {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        final request = JoinRequest(
          api: SpikeApi(
            Uri.base.replace(path: '/', query: ''),
            q['code'] ?? '',
          ),
          provider: q['provider'] ?? 'livekit',
          role: 'bot',
          seat: int.tryParse(q['seat'] ?? '') ?? 1,
          room: q['room'] ?? 'spike-room',
          device: 'bot',
          network: q['network'] ?? 'normal',
        );
        Navigator.of(context).pushReplacement(
          MaterialPageRoute<void>(
            builder: (_) => RoomScreen(
              request: request,
              bot: true,
              stats: q['stats'] == '1',
            ),
          ),
        );
      });
    } else if (kIsWeb && q['auto'] == 'trials') {
      // Headless baseline from this PC: run the trials as a student without touching the form.
      WidgetsBinding.instance.addPostFrameCallback((_) {
        final request = JoinRequest(
          api: SpikeApi(
            Uri.base.replace(path: '/', query: ''),
            q['code'] ?? '',
          ),
          provider: q['provider'] ?? 'livekit',
          role: 'student',
          seat: int.tryParse(q['seat'] ?? '') ?? 2,
          room: q['room'] ?? 'spike-room',
          device: q['device'] ?? 'headless-chromium',
          network: q['network'] ?? 'normal',
        );
        Navigator.of(context).pushReplacement(
          MaterialPageRoute<void>(
            builder: (_) => TrialsScreen(
              request: request,
              count: int.tryParse(q['count'] ?? '') ?? _trialCount,
            ),
          ),
        );
      });
    }
  }

  @override
  void dispose() {
    for (final c in [_server, _code, _room, _device]) {
      c.dispose();
    }
    super.dispose();
  }

  JoinRequest? _request() {
    if (!(_form.currentState?.validate() ?? false)) return null;
    final base = kIsWeb
        ? Uri.base.replace(path: '/', query: '')
        : Uri.parse(_server.text.trim());
    return JoinRequest(
      api: SpikeApi(base, _code.text.trim()),
      provider: _provider,
      role: _role,
      seat: _seat,
      room: _room.text.trim(),
      // iOS and Android keyboards capitalise the first letter; the server accepts a-z 0-9 - only.
      device: _device.text.trim().toLowerCase(),
      network: _network,
    );
  }

  void _open(Widget Function(JoinRequest) screen) {
    final request = _request();
    if (request == null) return;
    Navigator.of(context)
        .push(MaterialPageRoute<void>(builder: (_) => screen(request)));
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context);
    final zoomBlocked = _provider == 'zoom' && !zoomAvailable;
    String? notEmpty(String? v) => (v == null || v.trim().isEmpty) ? '—' : null;

    return Scaffold(
      appBar: AppBar(title: Text(l.joinTitle)),
      body: SafeArea(
        child: Form(
          key: _form,
          child: ListView(
            padding: const EdgeInsetsDirectional.all(16),
            children: [
              SegmentedButton<String>(
                segments: const [
                  ButtonSegment(value: 'livekit', label: Text('LiveKit')),
                  ButtonSegment(value: 'zoom', label: Text('Zoom')),
                ],
                selected: {_provider},
                onSelectionChanged: (s) => setState(() => _provider = s.first),
              ),
              if (zoomBlocked) ...[
                const SizedBox(height: 8),
                Text(
                  l.zoomWebUnavailable,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ],
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                initialValue: _role,
                decoration: InputDecoration(labelText: l.role),
                items: [
                  DropdownMenuItem(
                    value: 'teacher',
                    child: Text(l.roleTeacher),
                  ),
                  DropdownMenuItem(
                    value: 'student',
                    child: Text(l.roleStudent),
                  ),
                  DropdownMenuItem(
                    value: 'substitute',
                    child: Text(l.roleSubstitute),
                  ),
                  DropdownMenuItem(
                    value: 'supervisor',
                    child: Text(l.roleSupervisor),
                  ),
                ],
                onChanged: (v) => setState(() => _role = v ?? _role),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                initialValue: _seat,
                decoration: InputDecoration(labelText: l.seat),
                items: [
                  for (var i = 1; i <= 9; i++)
                    DropdownMenuItem(value: i, child: Text('$i')),
                ],
                onChanged: (v) => setState(() => _seat = v ?? _seat),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _room,
                decoration: InputDecoration(labelText: l.room),
                validator: (v) =>
                    RegExp(r'^[a-z0-9][a-z0-9-]{2,39}$').hasMatch(v ?? '')
                    ? null
                    : 'a-z 0-9 -',
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _device,
                decoration: InputDecoration(
                  labelText: l.device,
                  hintText: l.deviceHint,
                ),
                validator: (v) =>
                    RegExp(r'^[a-z0-9-]{2,30}$')
                        .hasMatch((v ?? '').trim().toLowerCase())
                    ? null
                    : 'a-z 0-9 -',
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: _network,
                decoration: InputDecoration(labelText: l.network),
                items: [
                  DropdownMenuItem(
                    value: 'normal',
                    child: Text(l.networkNormal),
                  ),
                  DropdownMenuItem(
                    value: 'limited-1.5mbit',
                    child: Text(l.networkLimited),
                  ),
                ],
                onChanged: (v) => setState(() => _network = v ?? _network),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _code,
                decoration: InputDecoration(labelText: l.accessCode),
                validator: notEmpty,
              ),
              if (!kIsWeb) ...[
                const SizedBox(height: 12),
                TextFormField(
                  controller: _server,
                  keyboardType: TextInputType.url,
                  textDirection: TextDirection.ltr,
                  decoration: InputDecoration(
                    labelText: l.serverUrl,
                    hintText: 'https://….trycloudflare.com',
                  ),
                  validator: (v) =>
                      (Uri.tryParse(v ?? '')?.isScheme('https') ?? false)
                      ? null
                      : 'https://…',
                ),
              ],
              const SizedBox(height: 24),
              FilledButton(
                onPressed: zoomBlocked
                    ? null
                    : () => _open((r) => RoomScreen(request: r)),
                child: Text(l.join),
              ),
              const SizedBox(height: 12),
              OutlinedButton(
                onPressed: zoomBlocked
                    ? null
                    : () => _open(
                        (r) => TrialsScreen(request: r, count: _trialCount),
                      ),
                child: Text(l.autoTrials(_trialCount)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
