import 'package:flutter/material.dart';
import 'package:flutter/semantics.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_web_spike/src/form/form_page.dart';

import 'test_helpers.dart';

void main() {
  setUpAll(loadSpikeFonts);

  testWidgets('spike NFR-a11y form list and button expose Arabic semantics', (
    tester,
  ) async {
    final semantics = tester.ensureSemantics();
    await useTabletSize(tester);
    await tester.pumpWidget(wrap(const FormPage()));
    await tester.pumpAndSettle();

    expect(
      tester.getSemantics(find.byKey(const ValueKey('name-field'))),
      isSemantics(isTextField: true, label: 'اسم تجريبي'),
    );
    expect(
      tester.getSemantics(find.byKey(const ValueKey('form-title'))),
      isSemantics(isHeader: true, label: 'نموذج تجريبي'),
    );
    expect(
      tester.getSemantics(find.byKey(const ValueKey('lesson-list'))).role,
      SemanticsRole.list,
    );
    expect(
      tester.getSemantics(find.byKey(const ValueKey('lesson-1'))).role,
      SemanticsRole.listItem,
    );

    final button = find.byKey(const ValueKey('counter-button'));
    expect(
      tester.getSemantics(button),
      isSemantics(isButton: true, label: 'اضغط هنا'),
    );
    await tester.tap(button);
    await tester.pumpAndSettle();
    expect(find.text('ضغطت مرة واحدة'), findsOneWidget);

    // Touch target at least 44 px (child UI rule is 56; this is the adult form).
    expect(tester.getSize(button).height, greaterThanOrEqualTo(44));
    semantics.dispose();
  });
}
