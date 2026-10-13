import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../api.dart';
import '../platform/browser.dart' as browser;

/// Yes/no checklist the owner fills on the device after the manual checks (VoiceOver, keyboard,
/// install, payment return, video). Answers only; no free text.
class FeedbackPage extends StatefulWidget {
  const FeedbackPage({super.key});

  @override
  State<FeedbackPage> createState() => _FeedbackPageState();
}

class _FeedbackPageState extends State<FeedbackPage> {
  final _answers = <int, bool>{};
  String? _status;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final questions = [
      l10n.feedbackQ1,
      l10n.feedbackQ2,
      l10n.feedbackQ3,
      l10n.feedbackQ4,
      l10n.feedbackQ5, //
      l10n.feedbackQ6, l10n.feedbackQ7, l10n.feedbackQ8, l10n.feedbackQ9,
      l10n.feedbackQ10,
      l10n.feedbackQ11,
      l10n.feedbackQ12,
    ];
    return ListView(
      padding: const EdgeInsetsDirectional.all(16),
      children: [
        Text(l10n.feedbackIntro),
        const SizedBox(height: 16),
        for (final (i, q) in questions.indexed)
          Padding(
            padding: const EdgeInsetsDirectional.only(bottom: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${i + 1}. $q'),
                const SizedBox(height: 6),
                SegmentedButton<bool>(
                  emptySelectionAllowed: true,
                  segments: [
                    ButtonSegment(value: true, label: Text(l10n.yes)),
                    ButtonSegment(value: false, label: Text(l10n.no)),
                  ],
                  selected: {?_answers[i]},
                  onSelectionChanged: (s) => setState(() {
                    if (s.isEmpty) {
                      _answers.remove(i);
                    } else {
                      _answers[i] = s.first;
                    }
                  }),
                ),
              ],
            ),
          ),
        FilledButton(
          onPressed: _answers.isEmpty ? null : _send,
          child: Text(l10n.send),
        ),
        if (_status != null)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: 12),
            child: Text(_status!),
          ),
      ],
    );
  }

  Future<void> _send() async {
    final l10n = AppLocalizations.of(context);
    final lang = Localizations.localeOf(context).languageCode;
    try {
      await SpikeApi().postFeedback({
        'device': browser.queryParam('d'),
        'variant': browser.pageMeta('variant'),
        // Names of the device's Arabic voices (no personal data): which engine «استمع» used.
        'voices': [
          for (final v in browser.arabicVoices().take(20))
            {'name': v.name, 'lang': v.lang, 'on_device': v.onDevice},
        ],
        'answers': {
          for (final e in _answers.entries)
            'q${e.key + 1}': e.value ? 'yes' : 'no',
        },
      });
      if (mounted) setState(() => _status = l10n.feedbackSent);
    } on SpikeApiException catch (e) {
      if (mounted) {
        setState(() => _status = l10n.feedbackFailed(e.message(lang)));
      }
    } on Object catch (e) {
      if (mounted) setState(() => _status = l10n.feedbackFailed('$e'));
    }
  }
}
