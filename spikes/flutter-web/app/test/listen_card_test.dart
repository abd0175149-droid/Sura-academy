import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_web_spike/src/arabic/diacritics.dart';
import 'package:flutter_web_spike/src/listen/listen_card.dart';
import 'package:flutter_web_spike/src/platform/browser.dart' as browser;
import 'package:flutter_web_spike/src/reading/samples.dart';

import 'test_helpers.dart';

class FakeSpeaker implements Speaker {
  FakeSpeaker(this._voices);

  final List<browser.SpeechVoice> _voices;
  final spoken = <(String, String)>[];
  var stopped = 0;

  @override
  List<browser.SpeechVoice> voices() => _voices;

  @override
  bool speak(String text, String voiceName) {
    spoken.add((text, voiceName));
    return true;
  }

  @override
  void stop() => stopped++;

  @override
  void onVoicesChanged(void Function() callback) {}
}

void main() {
  setUpAll(loadSpikeFonts);

  testWidgets(
    'spike TTS listen card speaks the samples with the first on-device Arabic voice',
    (tester) async {
      await useTabletSize(tester);
      final speaker = FakeSpeaker(const [
        (name: 'Majed', lang: 'ar-SA', onDevice: true),
        (name: 'Google العربية', lang: 'ar', onDevice: false),
      ]);
      await tester.pumpWidget(wrap(ListenCard(speaker: speaker)));
      await tester.pumpAndSettle();

      await tester.tap(find.text('استمع إلى القصة'));
      await tester.tap(find.text('القصة دون تشكيل'));
      expect(speaker.spoken, [
        (ReadingSamples.story, 'Majed'),
        (hideDiacritics(ReadingSamples.story), 'Majed'),
      ]);
      // The Quranic sample is never offered to machine speech.
      expect(speaker.spoken.where((s) => s.$1.contains('ٱلْحَمْدُ')), isEmpty);

      await tester.tap(find.text('إيقاف'));
      expect(speaker.stopped, 1);
    },
  );

  testWidgets(
    'spike TTS listen card says when the device has no Arabic voice',
    (tester) async {
      await useTabletSize(tester);
      await tester.pumpWidget(wrap(ListenCard(speaker: FakeSpeaker(const []))));
      await tester.pumpAndSettle();
      expect(find.byKey(const ValueKey('listen-no-voice')), findsOneWidget);
      expect(find.text('استمع إلى القصة'), findsNothing);
    },
  );
}
