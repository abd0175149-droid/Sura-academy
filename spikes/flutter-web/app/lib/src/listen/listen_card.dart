import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../arabic/diacritics.dart';
import '../platform/browser.dart' as browser;
import '../reading/samples.dart';

/// The speech engine the card talks to (the browser's Web Speech API in the app, a fake in tests).
abstract interface class Speaker {
  List<browser.SpeechVoice> voices();
  bool speak(String text, String voiceName);
  void stop();
  void onVoicesChanged(void Function() callback);
}

class BrowserSpeaker implements Speaker {
  const BrowserSpeaker();

  @override
  List<browser.SpeechVoice> voices() => browser.arabicVoices();

  @override
  bool speak(String text, String voiceName) =>
      browser.speak(text, voiceName: voiceName);

  @override
  void stop() => browser.stopSpeaking();

  @override
  void onVoicesChanged(void Function() callback) =>
      browser.onVoicesChanged(callback);
}

/// «استمع» probe (owner's request, 2026-10-13; candidate CR-005): hears how the device's own
/// Arabic voices read vocalized text, the same text without diacritics, and a class time with a
/// link. Not for the Quranic sample: machine recitation is out of the question.
class ListenCard extends StatefulWidget {
  const ListenCard({super.key, required this.speaker});

  final Speaker speaker;

  @override
  State<ListenCard> createState() => _ListenCardState();
}

class _ListenCardState extends State<ListenCard> {
  List<browser.SpeechVoice> _voices = const [];
  String? _voice;
  String? _status;

  @override
  void initState() {
    super.initState();
    _load();
    widget.speaker.onVoicesChanged(() {
      if (mounted) setState(_load);
    });
  }

  void _load() {
    _voices = widget.speaker.voices();
    if (_voice == null || !_voices.any((v) => v.name == _voice)) {
      _voice = _voices.isEmpty ? null : _voices.first.name;
    }
    browser.reportProbe('voices', {
      'voices': [
        for (final v in _voices)
          {'name': v.name, 'lang': v.lang, 'on_device': v.onDevice},
      ],
    });
  }

  @override
  void dispose() {
    widget.speaker.stop();
    super.dispose();
  }

  void _say(String text) {
    final ok = _voice != null && widget.speaker.speak(text, _voice!);
    setState(
      () => _status = ok ? null : AppLocalizations.of(context).listenNoVoice,
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Card.outlined(
      child: Padding(
        padding: const EdgeInsetsDirectional.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Semantics(
              header: true,
              headingLevel: 2,
              child: Text(l10n.listenTitle, style: theme.textTheme.titleMedium),
            ),
            const SizedBox(height: 8),
            if (_voices.isEmpty)
              Text(l10n.listenNoVoice, key: const ValueKey('listen-no-voice'))
            else ...[
              DropdownButton<String>(
                key: const ValueKey('listen-voice'),
                value: _voice,
                isExpanded: true,
                items: [
                  for (final v in _voices)
                    DropdownMenuItem(
                      value: v.name,
                      child: Text(
                        '${v.name} (${v.lang}) · ${v.onDevice ? l10n.listenOnDevice : l10n.listenOnline}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: (v) => setState(() => _voice = v),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  FilledButton.tonalIcon(
                    key: const ValueKey('listen-story'),
                    icon: const Icon(Icons.volume_up_outlined),
                    onPressed: () => _say(ReadingSamples.story),
                    label: Text(l10n.listenStory),
                  ),
                  FilledButton.tonalIcon(
                    icon: const Icon(Icons.volume_up_outlined),
                    onPressed: () => _say(hideDiacritics(ReadingSamples.story)),
                    label: Text(l10n.listenStoryPlain),
                  ),
                  FilledButton.tonalIcon(
                    icon: const Icon(Icons.volume_up_outlined),
                    onPressed: () => _say(ReadingSamples.stress),
                    label: Text(l10n.listenWords),
                  ),
                  FilledButton.tonalIcon(
                    icon: const Icon(Icons.volume_up_outlined),
                    onPressed: () => _say(ReadingSamples.mixed),
                    label: Text(l10n.listenClassTime),
                  ),
                  OutlinedButton.icon(
                    icon: const Icon(Icons.stop_circle_outlined),
                    onPressed: widget.speaker.stop,
                    label: Text(l10n.listenStop),
                  ),
                ],
              ),
            ],
            if (_status != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: 8),
                child: Text(_status!),
              ),
          ],
        ),
      ),
    );
  }
}
