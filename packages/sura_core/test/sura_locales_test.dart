import 'package:sura_core/sura_core.dart';
import 'package:test/test.dart';

void main() {
  test('Platform: Arabic is the default locale and English is supported', () {
    expect(SuraLocales.defaultLanguageCode, 'ar');
    expect(SuraLocales.supportedLanguageCodes, ['ar', 'en']);
  });
}
