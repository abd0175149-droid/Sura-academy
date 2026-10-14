import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_web_spike/src/arabic/diacritics.dart';
import 'package:flutter_web_spike/src/reading/reading_page.dart';

import 'test_helpers.dart';

final _sampleFinder = find.byWidgetPredicate(
  (w) =>
      w is Text &&
      w.key is ValueKey<String> &&
      (w.key! as ValueKey<String>).value.startsWith('sample:'),
  skipOffstage: false,
);

Iterable<Text> _samples(WidgetTester tester) =>
    tester.widgetList<Text>(_sampleFinder);

void main() {
  setUpAll(loadSpikeFonts);

  testWidgets(
    'spike CNT-08 reading page shows both fonts at 16 24 32 without overflow at text scale 2.0',
    (tester) async {
      await useTabletSize(tester);
      await tester.pumpWidget(wrap(const ReadingPage(), textScale: 2.0));
      await tester.pumpAndSettle();

      final seen = <String>{};
      for (final text in _samples(tester)) {
        seen.add('${text.style!.fontFamily}@${text.style!.fontSize!.toInt()}');
      }
      for (final family in ['NotoNaskhArabic', 'IBMPlexSansArabic']) {
        for (final size in [16, 24, 32]) {
          expect(seen, contains('$family@$size'));
        }
      }
      // Overflow or clipping errors surface as exceptions during layout.
      expect(tester.takeException(), isNull);
    },
  );

  testWidgets('spike CNT-08 diacritics switch updates every sample', (
    tester,
  ) async {
    await useTabletSize(tester);
    await tester.pumpWidget(wrap(const ReadingPage()));
    await tester.pumpAndSettle();
    expect(_samples(tester).where((t) => hasDiacritics(t.data!)), isNotEmpty);

    await tester.tap(find.byKey(const ValueKey('diacritics-switch')));
    await tester.pumpAndSettle();
    expect(_samples(tester).where((t) => hasDiacritics(t.data!)), isEmpty);

    await tester.tap(find.byKey(const ValueKey('diacritics-switch')));
    await tester.pumpAndSettle();
    expect(_samples(tester).where((t) => hasDiacritics(t.data!)), isNotEmpty);
  });

  testWidgets('spike CNT-08 text size control enlarges the samples', (
    tester,
  ) async {
    await useTabletSize(tester);
    await tester.pumpWidget(wrap(const ReadingPage()));
    await tester.pumpAndSettle();
    final before = tester.getSize(_sampleFinder.first).height;

    await tester.tap(find.text('الأكبر'));
    await tester.pumpAndSettle();
    final after = tester.getSize(_sampleFinder.first).height;
    expect(after, greaterThan(before * 1.8));
    expect(tester.takeException(), isNull);
  });

  testWidgets('spike NFR-a11y diacritics switch is one labelled toggle', (
    tester,
  ) async {
    final semantics = tester.ensureSemantics();
    await useTabletSize(tester);
    await tester.pumpWidget(wrap(const ReadingPage()));
    await tester.pumpAndSettle();
    expect(
      tester.getSemantics(find.byKey(const ValueKey('diacritics-switch'))),
      isSemantics(
        label: 'إظهار التشكيل',
        isToggled: true,
        hasToggledState: true,
      ),
    );
    semantics.dispose();
  });

  testWidgets(
    'spike CNT-08 Arabic samples stay right-to-left under the English UI',
    (tester) async {
      await useTabletSize(tester);
      await tester.pumpWidget(
        wrap(const ReadingPage(), locale: const Locale('en')),
      );
      await tester.pumpAndSettle();
      final sample = _sampleFinder.first;
      expect(Directionality.of(tester.element(sample)), TextDirection.rtl);
      expect(tester.widget<Text>(sample).locale, const Locale('ar'));
    },
  );

  testWidgets(
    'spike NFR-a11y touch targets stay at least 44 px on desktop platforms',
    (tester) async {
      // A Chromebook reports linux: Flutter would otherwise use compact density there.
      debugDefaultTargetPlatformOverride = TargetPlatform.linux;
      addTearDown(() => debugDefaultTargetPlatformOverride = null);
      await useTabletSize(tester);
      await tester.pumpWidget(wrap(ReadingPage(onCapture: (_, _) async {})));
      await tester.pumpAndSettle();
      for (final label in ['التقاط صورة', 'عادي', 'الأكبر']) {
        final button = find
            .ancestor(
              of: find.text(label),
              matching: find.byWidgetPredicate(
                (w) => w is ButtonStyleButton || w is SegmentedButton<double>,
              ),
            )
            .first;
        expect(
          tester.getSize(button).height,
          greaterThanOrEqualTo(44),
          reason: label,
        );
      }
      debugDefaultTargetPlatformOverride = null;
    },
  );
}
