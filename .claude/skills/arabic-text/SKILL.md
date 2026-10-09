---
name: arabic-text
description: Arabic text processing rules for Sura Academy — the three normalizations (display, search, grading), diacritics handling by exercise type, Postgres search normalization, digits, and the on-screen Arabic keyboard. Use for search, answer checking, homework/quiz grading, keyboards, slugs, sorting, deduplication of names, or any string comparison involving Arabic.
paths:
  - "packages/sura_arabic/**"
  - "apps/api/app/Support/Arabic/**"
---

# Arabic text

Implemented twice with identical test fixtures: Dart (`packages/sura_arabic`) and PHP (`App\Support\Arabic`). Both must pass the same fixture file (`packages/sura_arabic/test/fixtures/normalization.json`, copied to `apps/api/tests/Fixtures`).

## 1. Display normalization
Unicode NFC only. Never strip diacritics from content; never change hamza or ta marbuta. Store content exactly as authored (after NFC).

## 2. Search normalization (index and query)
Apply in order:
1. NFC.
2. Remove diacritics: U+064B–U+065F, U+0670 (superscript alef), U+06D6–U+06ED (Quranic marks).
3. Remove tatweel U+0640.
4. Alef forms أ (U+0623), إ (U+0625), آ (U+0622), ٱ (U+0671) → ا (U+0627).
5. ى (U+0649) → ي (U+064A); ئ (U+0626) → ي; ؤ (U+0624) → و (U+0648).
6. ة (U+0629) → ه (U+0647).
7. Arabic-Indic ٠–٩ (U+0660–U+0669) and Extended ۰–۹ (U+06F0–U+06F9) → 0–9.
8. Lowercase Latin; collapse whitespace; trim.

Postgres: immutable SQL function `sura_normalize_ar(text)` implementing the same steps; generated column `search_text` + GIN (`pg_trgm`) index. Queries normalize the input with the same function (spec: PostgreSQL search with Arabic normalization).

## 3. Grading normalization (answer checking)
Each exercise declares a policy:
| Policy | Use when | Compare |
| --- | --- | --- |
| `exact_vocalized` | diacritics are the skill being tested | NFC + canonical order of combining marks (shadda before vowel) |
| `letters` (default) | spelling is tested, diacritics are not | strip diacritics and tatweel; keep hamza forms and ة distinct |
| `lenient` | comprehension answers | full search normalization |
Never auto-grade pronunciation (R1/R2).

## Digits and names
Western digits in UI by default. Duplicate-detection keys for trials (D-09) use search normalization on the first name.

## On-screen keyboard (STU-07)
- Standard Arabic layout rows (as on common Arabic keyboards), large keys, optional harakat row (fatha, damma, kasra, sukun, shadda, tanween ×3), a key for ة, ى, ء and hamza seats.
- Inserting a haraka attaches to the previous letter; shadda + vowel order handled.
- Works on web and touch; does not trigger the system keyboard on tablets when active.

## Fixtures (minimum)
Fully vocalized sentence; words with each hamza seat; ة vs ه; ى vs ي; tatweel; Quranic marks; mixed Arabic/English/digits; empty and whitespace-only strings.
