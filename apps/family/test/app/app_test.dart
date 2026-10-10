import 'package:family/app/app.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('FamilyApp', () {
    testWidgets('Platform: renders the Arabic RTL shell by default', (
      tester,
    ) async {
      await tester.pumpWidget(const FamilyApp());
      await tester.pumpAndSettle();

      expect(find.text('أكاديمية سُرى'), findsOneWidget);
      expect(find.text('منطقة الأسرة'), findsOneWidget);
      expect(
        Directionality.of(tester.element(find.text('منطقة الأسرة'))),
        TextDirection.rtl,
      );
    });

    testWidgets('Platform: renders the English LTR shell when asked', (
      tester,
    ) async {
      await tester.pumpWidget(const FamilyApp(locale: Locale('en')));
      await tester.pumpAndSettle();

      expect(find.text('Family area'), findsOneWidget);
      expect(
        Directionality.of(tester.element(find.text('Family area'))),
        TextDirection.ltr,
      );
    });
  });
}
