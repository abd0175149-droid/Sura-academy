import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

Set<String> _messageKeys(String path) {
  final json =
      jsonDecode(File(path).readAsStringSync()) as Map<String, dynamic>;
  return json.keys.where((key) => !key.startsWith('@')).toSet();
}

void main() {
  test('Platform: Arabic and English ARB files define the same messages', () {
    final arabic = _messageKeys('lib/l10n/app_ar.arb');
    final english = _messageKeys('lib/l10n/app_en.arb');

    expect(english, equals(arabic));
  });
}
