import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sura_video_spike/main.dart';

void main() {
  testWidgets('spike join screen is Arabic and right-to-left', (tester) async {
    tester.view
      ..physicalSize = const Size(800, 2000)
      ..devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    await tester.pumpWidget(const SpikeApp());
    await tester.pumpAndSettle();

    expect(find.text('الدخول إلى غرفة تجريبية'), findsOneWidget);
    expect(find.text('دخول'), findsOneWidget);
    expect(find.text('20 محاولة تلقائية'), findsOneWidget);
    final direction = Directionality.of(tester.element(find.text('دخول')));
    expect(direction, TextDirection.rtl);
  });

  testWidgets('spike join form refuses a real-looking device name', (
    tester,
  ) async {
    tester.view
      ..physicalSize = const Size(800, 2000)
      ..devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    await tester.pumpWidget(const SpikeApp());
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextFormField).at(1), 'Sara iPad');
    await tester.tap(find.text('دخول'));
    await tester.pumpAndSettle();

    expect(find.text('a-z 0-9 -'), findsWidgets);
    expect(find.text('الدخول إلى غرفة تجريبية'), findsOneWidget);
  });
}
