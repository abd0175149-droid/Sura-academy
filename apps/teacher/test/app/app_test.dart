import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:teacher/app/app.dart';

void main() {
  group('TeacherApp', () {
    testWidgets('Platform: renders the Arabic RTL shell by default', (
      tester,
    ) async {
      await tester.pumpWidget(const TeacherApp());
      await tester.pumpAndSettle();

      expect(find.text('أكاديمية سُرى'), findsOneWidget);
      expect(find.text('بوابة المعلمة'), findsOneWidget);
      expect(
        Directionality.of(tester.element(find.text('بوابة المعلمة'))),
        TextDirection.rtl,
      );
    });

    testWidgets('Platform: renders the English LTR shell when asked', (
      tester,
    ) async {
      await tester.pumpWidget(const TeacherApp(locale: Locale('en')));
      await tester.pumpAndSettle();

      expect(find.text('Teacher portal'), findsOneWidget);
      expect(
        Directionality.of(tester.element(find.text('Teacher portal'))),
        TextDirection.ltr,
      );
    });
  });
}
