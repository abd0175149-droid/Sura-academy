/// Test content for the reading screen (content fixtures, not UI strings; in the product such
/// text comes from the curriculum, never from ARB files).
abstract final class ReadingSamples {
  static const story =
      'كَانَ فِي قَرْيَةٍ صَغِيرَةٍ وَلَدٌ يُحِبُّ القِرَاءَةَ، فَإِذَا جَاءَ المَسَاءُ جَلَسَ إِلَى '
      'جَدَّتِهِ تَقُصُّ عَلَيْهِ الحِكَايَاتِ، وَتُعَلِّمُهُ أَنَّ الصِّدْقَ نَجَاةٌ، وَأَنَّ الرِّفْقَ '
      'لَا يَكُونُ فِي شَيْءٍ إِلَّا زَانَهُ.';

  /// Shadda with tanween, hamza forms, lam-alef, maddah.
  static const stress =
      'مُعَلِّمٌ، مُدَرِّسَةً، سِرٍّ · سَأَلَ، سُئِلَ، يُؤْمِنُ، شَيْءٌ، مَاءً · '
      'لَا، لِأَنَّ، الْأَرْضُ · آمَنَ، قُرْآنٌ';

  /// Quranic orthography: alef wasla, dagger alef, ornate parentheses.
  static const quranic = '﴿ٱلْحَمْدُ لِلَّهِ رَبِّ ٱلْعَٰلَمِينَ﴾';

  /// Mixed direction with isolates (FSI … PDI) around the Latin and numeric runs.
  static const mixed =
      'الحِصَّةُ القَادِمَةُ يَوْمَ الأَحَدِ السَّاعَةَ \u206817:30\u2069 بِتَوْقِيتِ '
      '\u2068Europe/Berlin\u2069، وَالرَّابِطُ \u2068sura.academy/join\u2069';

  /// Glyphs neither bundled font has: an emoji and a rare symbol (font-fallback probe, PRV-10).
  static const fallback = 'أَحْسَنْتَ 🌟 نَجْمَةٌ وَهِلَالٌ ☪';

  static const all = [story, stress, quranic, mixed, fallback];
}
