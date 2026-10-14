/// Display-only diacritics hiding for the reading screen (CNT-08 «إظهار التشكيل أو إخفاؤه»).
///
/// Stored text is never changed; this only feeds what is painted. Combining hamza (U+0654,
/// U+0655) and maddah (U+0653) are kept: they belong to the letter (أ إ آ in decomposed form),
/// not to the tashkeel. Honorific signs U+0610–U+0614 and ayah/sajdah symbols are kept too.
library;

bool _isMark(int c) =>
    (c >= 0x064B && c <= 0x0652) || // fathatan … sukun
    (c >= 0x0656 && c <= 0x065F) || // subscript alef, inverted damma, …
    c == 0x0670 || // superscript (dagger) alef
    (c >= 0x0615 && c <= 0x061A) || // small high tah … small kasra
    (c >= 0x06D6 && c <= 0x06DC) || // Quranic small high ligatures and marks
    (c >= 0x06DF && c <= 0x06E4) ||
    (c >= 0x06E7 && c <= 0x06E8) ||
    (c >= 0x06EA && c <= 0x06ED) ||
    (c >= 0x08D3 && c <= 0x08E1) || // extended Arabic marks (open tanween, …)
    (c >= 0x08E3 && c <= 0x08FF);

String hideDiacritics(String text) =>
    String.fromCharCodes(text.runes.where((c) => !_isMark(c)));

bool hasDiacritics(String text) => text.runes.any(_isMark);
