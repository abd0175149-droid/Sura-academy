import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_web_spike/src/arabic/diacritics.dart';

void main() {
  test(
    'spike CNT-08 hiding diacritics removes only marks and keeps letters',
    () {
      expect(
        hideDiacritics('بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ'),
        'بسم ٱلله ٱلرحمن ٱلرحيم',
      );
      // Hamza forms, tanween with shadda, tatweel, Latin and digits survive.
      expect(hideDiacritics('إِنَّ المُؤْمِنَ قَوِيٌّ'), 'إن المؤمن قوي');
      expect(hideDiacritics('سـلام Ali 2026'), 'سـلام Ali 2026');
      // Combining hamza above/below (U+0654, U+0655) belongs to the letter, not to the tashkeel.
      expect(hideDiacritics('أَ'), 'أ');
      // Quranic small high marks are display marks too.
      expect(hideDiacritics('الْكِتَابُ ۛ'), 'الكتاب ');
    },
  );

  test('spike CNT-08 hiding diacritics leaves unvocalized text unchanged', () {
    const plain = 'مرحبا بكم في أكاديمية سرى';
    expect(hideDiacritics(plain), plain);
    expect(hasDiacritics(plain), isFalse);
    expect(hasDiacritics('مَرْحَبًا'), isTrue);
  });
}
