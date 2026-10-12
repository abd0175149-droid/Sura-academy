<?php

declare(strict_types=1);

/*
 * Regression tests for scripts/check-traceability.php, WP-0.3.
 *
 *   php scripts/test-traceability.php
 *
 * Builds a small fixture repository (split spec files, the source export, the spec README index,
 * traceability, status and phase files, one test file) in a throwaway directory, applies one
 * mutation per case, runs the checker with --root against it and asserts the exit code and a
 * fragment of its output. A mutation that matches nothing in the fixture fails its case, so no
 * case can pass by accident.
 */

$root = dirname(__DIR__);
$checker = $root.'/scripts/check-traceability.php';
$php = PHP_BINARY;

$tmp = sys_get_temp_dir().'/sura-trace-tests-'.getmypid();
register_shutdown_function(static function () use ($tmp): void {
    if (is_dir($tmp)) {
        exec('rm -rf '.escapeshellarg($tmp));
    }
});

const SPEC = 'docs/01-product/spec/';
const SOURCE = 'docs/01-product/source/spec-rev1.md';
const README = SPEC.'README.md';
const TRACE = 'docs/03-plan/traceability.md';
const STATUS = 'docs/03-plan/status.md';
const PHASE1 = 'docs/03-plan/phase-1-core.md';
const PHASE2 = 'docs/03-plan/phase-2-later.md';
const TESTS = 'apps/api/tests/Feature/AlphaTest.php';

/** The consistent fixture repository: path => content. Every case starts from it. */
function fixture(): array
{
    $header = "<!-- منقول آلياً من المصدر (المراجعة 1). لا يُعدَّل إلا عبر طلب تغيير -->\n";
    $table = "| المعرّف | الميزة | الوصف | الأولوية |\n| --- | --- | --- | --- |\n";
    $sections = [
        '00-intro.md' => "## مقدمة\n\nنص تمهيدي للمواصفات.\n",
        '01-alpha.md' => "## ألفا\n\nمتطلبات الدخول والخروج.\n\n{$table}"
            ."| ALP-01 | دخول | وصف الدخول | R1 |\n"
            ."| ALP-02 | خروج | وصف الخروج | R1 |\n"
            ."| ALP-03 | تصدير | وصف التصدير | R2 |\n",
        '02-beta.md' => "## بيتا\n\n### العرض\n\n{$table}"
            ."| BET-01 | عرض | وصف العرض | R1 |\n"
            ."| BET-02 | قراءة آلية | وصف القراءة | R3 |\n\n### الألعاب\n\n{$table}"
            ."| GAM-01 | مكافآت | وصف المكافآت | R1 احتساب، R2 سير كامل |\n"
            ."| GAM-02 | ألعاب | وصف الألعاب | R2 |\n",
        '21-appendices.md' => "## الملاحق\n\n| القاعدة | الوصف |\n| --- | --- |\n"
            ."| BR-001 | قاعدة أولى |\n| BR-002 | قاعدة ثانية |\n",
    ];

    $files = [];
    foreach ($sections as $name => $body) {
        $files[SPEC.$name] = $header."\n".$body;
    }
    // A CR proposal: not in the source, and its rows are not requirements.
    $files[SPEC.'22-learning-experience-proposal.md'] = "<!-- مقترح غير معتمد -->\n## مقترح\n\n{$table}"
        ."| OUT-01 | نتاجات | وصف النتاجات | R1 |\n";
    $files[SOURCE] = "# مواصفات تجريبية\n\nالمصدر: وثيقة تجريبية — المراجعة 1\n\n".implode("\n", $sections);

    $files[README] = <<<'MD'
        # فهرس المواصفات

        النسخة الكاملة في ملف واحد: `../source/spec-rev1.md`.

        | الملف | القسم | المعرّفات | R1 | R2 | R3 |
        | --- | --- | --- | --- | --- | --- |
        | `00-intro.md` | مقدمة | — | — | — | — |
        | `01-alpha.md` | ألفا | ALP-01…03 | 2 | 1 | — |
        | `02-beta.md` | بيتا | BET-01…02 · GAM-01…02 | 2 | 1 | 1 |
        | `21-appendices.md` | الملاحق | — | — | — | — |
        | `22-learning-experience-proposal.md` | مقترح | OUT (مقترحة) | — | — | — |

        **المجموع:** 7 متطلباً مرقماً — R1: 4 · R2: 2 · R3: 1. القواعد BR-001…BR-002 في `21-appendices.md`.

        MD;

    $files[TRACE] = <<<'MD'
        # مصفوفة التتبع

        المجموع: 7 متطلباً — R1: 4 · R2: 2 · R3: 1.

        | المعرّف | الأولوية | الميزة | حزمة الخلفية | حزمة التجربة | ملف المواصفات | الحالة |
        | --- | --- | --- | --- | --- | --- | --- |
        | ALP-01 | R1 | دخول | WP-1.1 | — | `01-alpha.md` | — |
        | ALP-02 | R1 | خروج | WP-1.2 | WP-1.2 | `01-alpha.md` | — |
        | ALP-03 | R2 | تصدير | WP-2.1 | WP-2.1 | `01-alpha.md` | — |
        | BET-01 | R1 | عرض | — | WP-1.2 | `02-beta.md` | — |
        | BET-02 | R3 | قراءة آلية | WP-2.1 | WP-2.1 | `02-beta.md` | — |
        | GAM-01 | R1 احتساب، R2 سير كامل | مكافآت | WP-1.2 | — | `02-beta.md` | — |
        | GAM-02 | R2 | ألعاب | WP-2.1 | — | `02-beta.md` | — |

        ## قواعد العمل

        | القاعدة | الحزمة |
        | --- | --- |
        | BR-001 | WP-1.1 |
        | BR-002 | WP-1.2, WP-2.1 |

        ## متطلبات مقترحة

        | المعرّف | الأولوية المقترحة | الميزة | الحزمة | الحالة |
        | --- | --- | --- | --- | --- |
        | OUT-01 | R1 | نتاجات | WP-2.1 | Proposed |

        MD;

    $files[STATUS] = <<<'MD'
        # حالة التنفيذ

        | الحزمة | الاسم | المرحلة | النافذة | يعتمد على | الحالة | الفرع / PR | آخر تحديث |
        | --- | --- | --- | --- | --- | --- | --- | --- |
        | WP-1.1 | الأساس | 1 | W1 | — | Done | — | — |
        | WP-1.2 | الواجهة | 1 | W2 | WP-1.1 | In progress | — | — |
        | WP-2.1 | اللاحق | 2 | W3 | WP-1.2 | Not started | — | — |

        MD;

    $files[PHASE1] = "# المرحلة 1\n\n### WP-1.1 الأساس\n\nنص.\n\n### WP-1.2 الواجهة\n\nنص.\n";
    $files[PHASE2] = "# المرحلة 2\n\n| الحزمة | الاسم | المتطلبات |\n| --- | --- | --- |\n| WP-2.1 | اللاحق | ALP-03 · BET-02 · GAM-02 |\n";

    $files[TESTS] = "<?php\n\nit('ALP-01 signs the parent in', function () {});\n\ntest('BR-001 rule holds', function () {});\n";

    return $files;
}

/** Mutation: replace $from with $to in $file. Fails the case when $from is not in the file. */
function swap(string $file, string $from, string $to): Closure
{
    return static function (array $files) use ($file, $from, $to): array {
        if (! isset($files[$file]) || ! str_contains($files[$file], $from)) {
            throw new RuntimeException("mutation matched nothing: {$file} has no «{$from}»");
        }
        $files[$file] = str_replace($from, $to, $files[$file]);

        return $files;
    };
}

/** Mutation: add or replace a whole file. */
function put(string $file, string $content): Closure
{
    return static fn (array $files): array => [$file => $content] + $files;
}

/** Mutation: apply several mutations in order. */
function all(Closure ...$mutations): Closure
{
    return static function (array $files) use ($mutations): array {
        foreach ($mutations as $m) {
            $files = $m($files);
        }

        return $files;
    };
}

/** Runs the checker on $dir with $args; returns [exitCode, combined output]. */
function run_checker(string $php, string $checker, string $dir, array $args): array
{
    $proc = proc_open(
        array_merge([$php, $checker, '--root', $dir], $args),
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );
    $out = (string) stream_get_contents($pipes[1]);
    $err = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($proc), trim($out."\n".$err)];
}

$note = "> **تنبيه الحزمة:** صف «الدخول» استُبدل بقرار ADR-099.\n";
$caption = "> **شكل (منقول نصاً من مخطط الوثيقة): مسار الدخول**\n";

// [name, mutation or null, checker args, expected exit code, fragment the output must contain]
$cases = [
    // Behaviour that existed before WP-0.3.
    ['baseline fixture passes (full)', null, [], 0, '✔ traceability OK'],
    ['baseline fixture passes (--quick)', null, ['--quick'], 0, '✔ traceability OK'],
    ['unmapped spec id', swap(TRACE, "| ALP-03 | R2 | تصدير | WP-2.1 | WP-2.1 | `01-alpha.md` | — |\n", ''), [], 1,
        'ALP-03 (01-alpha.md) is not mapped'],
    ['duplicate traceability row', swap(TRACE, "| ALP-02 | R1 |", "| ALP-01 | R1 | دخول | WP-1.1 | — | `01-alpha.md` | — |\n| ALP-02 | R1 |"), [], 1,
        'duplicate traceability row ALP-01'],
    ['trace wp missing from status', swap(TRACE, '| ALP-03 | R2 | تصدير | WP-2.1 |', '| ALP-03 | R2 | تصدير | WP-9.9 |'), [], 1,
        'ALP-03: backend work package WP-9.9 is missing from status.md'],
    ['status wp with no definition', swap(PHASE2, '| WP-2.1 | اللاحق |', '| — | اللاحق |'), [], 1,
        'WP-2.1 is in status.md but has no definition'],
    ['unknown id in test name', swap(TESTS, "it('ALP-01", "it('ZZZ-99"), ['--quick'], 1,
        'test name references unknown requirement id ZZZ-99'],
    ['review wp without tests fails in full mode', swap(STATUS, '| WP-1.2 | الواجهة | 1 | W2 | WP-1.1 | In progress |', '| WP-1.2 | الواجهة | 1 | W2 | WP-1.1 | Review |'), [], 1,
        'WP-1.2: ALP-02 has no test whose name starts with the ID'],
    ['review wp without tests passes --quick', swap(STATUS, '| WP-1.2 | الواجهة | 1 | W2 | WP-1.1 | In progress |', '| WP-1.2 | الواجهة | 1 | W2 | WP-1.1 | Review |'), ['--quick'], 0,
        '✔ traceability OK'],
    ['--wp checks that wp only', null, ['--wp', 'WP-1.2'], 1, 'WP-1.2: GAM-01 has no test'],
    ['--wp with tests passes', null, ['--wp', 'WP-1.1'], 0, '✔ traceability OK'],
    ['--wp unknown', null, ['--wp', 'WP-9.9'], 1, 'unknown work package WP-9.9'],

    // N1 duplicate requirement ids in the spec.
    ['N1 duplicate id in spec files', swap(SPEC.'02-beta.md', "| BET-01 | عرض |", "| ALP-01 | مكرر | وصف | R1 |\n| BET-01 | عرض |"), ['--quick'], 1,
        'duplicate requirement id ALP-01 (01-alpha.md, 02-beta.md)'],

    // N2 README totals.
    ['N2 readme total mismatch', swap(README, '**المجموع:** 7 متطلباً', '**المجموع:** 8 متطلباً'), ['--quick'], 1,
        'spec/README.md states 8 requirements (R1 4 · R2 2 · R3 1); the spec has 7 (R1 4 · R2 2 · R3 1)'],
    ['N2 readme priority split mismatch', swap(README, 'R1: 4 · R2: 2', 'R1: 3 · R2: 3'), ['--quick'], 1,
        'spec/README.md states 7 requirements (R1 3 · R2 3 · R3 1); the spec has 7 (R1 4 · R2 2 · R3 1)'],
    ['N2 readme totals line missing', swap(README, '**المجموع:** 7 متطلباً مرقماً', 'المجموع غير مذكور'), ['--quick'], 1,
        'spec/README.md has no totals line'],

    // N3 traceability header totals.
    ['N3 traceability header total mismatch', swap(TRACE, 'R1: 4 · R2: 2 · R3: 1.', 'R1: 4 · R2: 2 · R3: 2.'), ['--quick'], 1,
        'docs/03-plan/traceability.md states 7 requirements (R1 4 · R2 2 · R3 2); the spec has 7 (R1 4 · R2 2 · R3 1)'],

    // N4 README index table.
    ['N4 readme per-file count mismatch', swap(README, '| ALP-01…03 | 2 | 1 | — |', '| ALP-01…03 | 3 | 1 | — |'), ['--quick'], 1,
        'spec/README.md: 01-alpha.md R1 count is 3, the file has 2'],
    ['N4 readme id range missing an id', swap(README, 'ALP-01…03', 'ALP-01…04'), ['--quick'], 1,
        'spec/README.md: 01-alpha.md range lists ALP-04, not in the file'],
    ['N4 file id outside readme range', swap(README, 'GAM-01…02', 'GAM-01…01'), ['--quick'], 1,
        'spec/README.md: 02-beta.md has GAM-02, outside its listed ranges'],
    ['N4 readme range unparseable', swap(README, 'ALP-01…03', 'ALP-01..03'), ['--quick'], 1,
        'spec/README.md: cannot read id range «ALP-01..03» for 01-alpha.md'],
    ['N4 spec file not in readme index', swap(README, "| `02-beta.md` | بيتا | BET-01…02 · GAM-01…02 | 2 | 1 | 1 |\n", ''), ['--quick'], 1,
        '02-beta.md is not listed in spec/README.md'],
    ['N4 readme lists a missing file', swap(README, '| `00-intro.md` |', '| `00-intro-old.md` |'), ['--quick'], 1,
        'spec/README.md lists 00-intro-old.md, which does not exist'],

    // N5 split files vs the source export.
    ['N5 package note and header comment are ignored', swap(SPEC.'01-alpha.md', "| ALP-03 | تصدير | وصف التصدير | R2 |\n", "| ALP-03 | تصدير | وصف التصدير | R2 |\n\n{$note}"), ['--quick'], 0,
        '✔ traceability OK'],
    ['N5 package notes are counted in the summary', swap(SPEC.'01-alpha.md', "| ALP-03 | تصدير | وصف التصدير | R2 |\n", "| ALP-03 | تصدير | وصف التصدير | R2 |\n\n{$note}"), ['--quick'], 0,
        'source: spec-rev1.md (4 sections · 1 package note)'],
    ['N5 figure caption is compared like any line', swap(SPEC.'00-intro.md', "## مقدمة\n", "## مقدمة\n\n{$caption}"), ['--quick'], 1,
        '00-intro.md differs from the source (spec-rev1.md) at line 5'],
    ['N5 requirement row differs from source', swap(SPEC.'01-alpha.md', 'وصف الخروج', 'وصف معدل للخروج'), ['--quick'], 1,
        '01-alpha.md differs from the source (spec-rev1.md) at line 10'],
    ['N5 difference message asks for an Open decision', swap(SPEC.'01-alpha.md', 'وصف الخروج', 'وصف معدل للخروج'), ['--quick'], 1,
        'record an Open D-xx in docs/00-governance/decisions.md; do not edit the spec by hand'],
    ['N5 prose differs from source', swap(SOURCE, 'نص تمهيدي للمواصفات.', 'نص تمهيدي آخر.'), ['--quick'], 1,
        '00-intro.md differs from the source (spec-rev1.md) at line 5'],
    ['N5 id only in split', swap(SPEC.'01-alpha.md', "| ALP-03 | تصدير | وصف التصدير | R2 |\n", "| ALP-03 | تصدير | وصف التصدير | R2 |\n| ALP-04 | جديد | وصف | R1 |\n"), ['--quick'], 1,
        'ALP-04 is in the split spec but not in the source'],
    ['N5 id only in source', swap(SOURCE, "| ALP-03 | تصدير | وصف التصدير | R2 |\n", "| ALP-03 | تصدير | وصف التصدير | R2 |\n| ALP-04 | جديد | وصف | R1 |\n"), ['--quick'], 1,
        'ALP-04 is in the source but not in the split spec'],
    ['N5 source section without split file', swap(SOURCE, '## الملاحق', "## قسم جديد\n\nنص.\n\n## الملاحق"), ['--quick'], 1,
        'source section «قسم جديد» has no split file'],
    ['N5 split file without source section', swap(SPEC.'00-intro.md', '## مقدمة', '## تمهيد'), ['--quick'], 1,
        '00-intro.md: section «تمهيد» is not in the source'],
    ['N5 readme does not name the source', swap(README, '`../source/spec-rev1.md`', 'الملف المصدر'), ['--quick'], 1,
        'spec/README.md does not name the source file'],
    ['N5 named source file missing', swap(README, 'spec-rev1.md', 'spec-rev2.md'), ['--quick'], 1,
        'source file docs/01-product/source/spec-rev2.md does not exist'],

    // N6 traceability row consistency.
    ['N6 trace priority differs from spec', swap(TRACE, '| ALP-03 | R2 |', '| ALP-03 | R1 |'), ['--quick'], 1,
        'ALP-03: traceability priority «R1» does not match the spec (R2)'],
    ['N6 trace spec file differs', swap(TRACE, "| ALP-03 | R2 | تصدير | WP-2.1 | WP-2.1 | `01-alpha.md` |", "| ALP-03 | R2 | تصدير | WP-2.1 | WP-2.1 | `02-beta.md` |"), ['--quick'], 1,
        'ALP-03: traceability names 02-beta.md; the spec has it in 01-alpha.md'],
    ['N6 row assigned to no work package', swap(TRACE, '| BET-01 | R1 | عرض | — | WP-1.2 |', '| BET-01 | R1 | عرض | — | — |'), ['--quick'], 1,
        'BET-01 is not assigned to any work package'],
    ['N6 trace row for an id not in the spec', swap(TRACE, '| ALP-02 | R1 |', "| ALP-09 | R1 | قديم | WP-1.1 | — | `01-alpha.md` | — |\n| ALP-02 | R1 |"), ['--quick'], 1,
        'ALP-09 is in docs/03-plan/traceability.md but not in the spec'],

    // N7 work package definitions.
    ['N7 wp mentioned only in prose', swap(PHASE1, "### WP-1.2 الواجهة\n", "نص يذكر WP-1.2 عرضاً.\n"), ['--quick'], 1,
        'WP-1.2 is in status.md but has no definition'],

    // N8 business rules table.
    ['N8 br table names unknown wp', swap(TRACE, '| BR-002 | WP-1.2, WP-2.1 |', '| BR-002 | WP-1.2, WP-9.9 |'), ['--quick'], 1,
        'BR-002: work package WP-9.9 is missing from status.md'],
    ['N8 test name with br id outside the table', put(TESTS, "<?php\n\nit('ALP-01 signs the parent in', function () {});\n\ntest('BR-003 rule holds', function () {});\n"), ['--quick'], 1,
        'test name references unknown requirement id BR-003'],
];

$passed = 0;
$failures = [];
foreach ($cases as $n => [$name, $mutation, $args, $expect, $fragment]) {
    try {
        $files = $mutation ? $mutation(fixture()) : fixture();
    } catch (RuntimeException $e) {
        $failures[] = "✗ {$name}: {$e->getMessage()}";
        continue;
    }
    $dir = "{$tmp}/case-{$n}";
    foreach ($files as $rel => $content) {
        if (! is_dir(dirname("{$dir}/{$rel}"))) {
            mkdir(dirname("{$dir}/{$rel}"), 0777, true);
        }
        file_put_contents("{$dir}/{$rel}", $content);
    }
    [$code, $out] = run_checker($php, $checker, $dir, $args);
    if ($code === $expect && str_contains($out, $fragment)) {
        $passed++;

        continue;
    }
    $failures[] = "✗ {$name}: expected exit {$expect} and «{$fragment}», got exit {$code}\n    ".str_replace("\n", "\n    ", $out);
}

echo 'traceability tests: '.$passed.'/'.count($cases)." as expected\n";
foreach ($failures as $f) {
    echo "{$f}\n";
}
exit($failures === [] ? 0 : 1);
