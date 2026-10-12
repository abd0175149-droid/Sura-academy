<?php
declare(strict_types=1);
/**
 * Sura Academy traceability checks.
 *   php scripts/check-traceability.php            full: structure + tests for WPs in Review/Done
 *   php scripts/check-traceability.php --quick    structure + unknown IDs in test names
 *   php scripts/check-traceability.php --wp WP-2.3   structure + tests for that WP's IDs
 *   --root <dir>   check another tree instead of this repository (used by scripts/test-traceability.php)
 */
$root = dirname(__DIR__);
$quick = false;
$wpArg = null;
for ($i = 1; $i < $argc; $i++) {
    match ($argv[$i]) {
        '--quick' => $quick = true,
        '--wp' => $wpArg = $argv[++$i] ?? null,
        '--root' => $root = rtrim($argv[++$i] ?? '', '/'),
        default => (function () use ($argv, $i): never {
            fwrite(STDERR, "unknown argument: {$argv[$i]}\n");
            exit(2);
        })(),
    };
}
if (!is_dir($root)) {
    fwrite(STDERR, "--root: not a directory: {$root}\n");
    exit(2);
}
$errors = [];

function table_rows(string $text): array
{
    $out = [];
    foreach (explode("\n", $text) as $line) {
        if (!str_starts_with($line, '| ')) {
            continue;
        }
        $out[] = array_map('trim', explode('|', trim(trim($line), '|')));
    }
    return $out;
}

function rows(string $file): array
{
    return is_file($file) ? table_rows((string) file_get_contents($file)) : [];
}

/** [id => 'R1'|'R2'|'R3'] for requirement rows: first cell is an ID, last cell starts with R1/R2/R3. */
function requirement_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $c) {
        if (count($c) >= 3 && preg_match('/^[A-Z]{2,5}-\d{2,3}$/', $c[0]) && preg_match('/^R[123]/', end($c), $m)) {
            $out[] = [$c[0], $m[0]];
        }
    }
    return $out;
}

/** The appendices hold business rules and 22-* is an unapproved CR proposal: neither holds numbered requirements. */
function holds_requirements(string $file): bool
{
    return !str_contains($file, '21-appendices') && !str_contains($file, '22-learning');
}

/** [total, R1, R2, R3] from a «المجموع: N متطلباً … — R1: a · R2: b · R3: c» line, or null. */
function stated_totals(string $file): ?array
{
    $text = is_file($file) ? (string) file_get_contents($file) : '';
    if (!preg_match('/المجموع:\**\s*(\d+)\s[^—\n]*—\s*R1:\s*(\d+)\s*·\s*R2:\s*(\d+)\s*·\s*R3:\s*(\d+)/u', $text, $m)) {
        return null;
    }
    return array_map('intval', array_slice($m, 1));
}

function fmt_split(array $t): string
{
    return "(R1 {$t[1]} · R2 {$t[2]} · R3 {$t[3]})";
}

$specDir = $root . '/docs/01-product/spec';

// 1) Requirement IDs from the spec.
$specIds = [];   // id => file
$specPrio = [];  // id => R1|R2|R3
$fileIds = [];   // file => [id => R1|R2|R3]
foreach (glob($specDir . '/*.md') ?: [] as $f) {
    $file = basename($f);
    if (!holds_requirements($file)) {
        continue;
    }
    foreach (requirement_rows(rows($f)) as [$id, $prio]) {
        if (isset($specIds[$id])) {
            $errors[] = "duplicate requirement id {$id} ({$specIds[$id]}, {$file})";
        }
        $specIds[$id] = $file;
        $specPrio[$id] = $prio;
        $fileIds[$file][$id] = $prio;
    }
}
if (!$specIds) {
    $errors[] = 'no requirement rows found under docs/01-product/spec';
}
$byPrio = array_count_values($specPrio) + ['R1' => 0, 'R2' => 0, 'R3' => 0];
$actualTotals = [count($specPrio), $byPrio['R1'], $byPrio['R2'], $byPrio['R3']];

// 1a) Totals stated in the spec index and in the traceability header match the spec.
foreach (['spec/README.md' => $specDir . '/README.md', 'docs/03-plan/traceability.md' => $root . '/docs/03-plan/traceability.md'] as $label => $path) {
    $stated = stated_totals($path);
    if ($stated === null) {
        $errors[] = "{$label} has no totals line («المجموع: N متطلباً — R1: … · R2: … · R3: …»)";
    } elseif ($stated !== $actualTotals) {
        $errors[] = "{$label} states {$stated[0]} requirements " . fmt_split($stated)
            . "; the spec has {$actualTotals[0]} " . fmt_split($actualTotals);
    }
}

// 1b) The spec index table: every file listed, and per file the ID ranges and R1/R2/R3 counts match.
$indexed = [];
foreach (rows($specDir . '/README.md') as $c) {
    if (count($c) < 6 || !preg_match('/^`([^`]+\.md)`$/', $c[0], $m)) {
        continue;
    }
    $file = $m[1];
    $indexed[$file] = true;
    if (!is_file("{$specDir}/{$file}")) {
        $errors[] = "spec/README.md lists {$file}, which does not exist";
        continue;
    }
    if (!holds_requirements($file)) {
        continue;
    }
    $listed = [];
    foreach ($c[2] === '—' ? [] : preg_split('/\s*·\s*/u', $c[2]) as $range) {
        if (!preg_match('/^([A-Z]{2,5})-(\d{2,3})…(\d{2,3})$/u', $range, $r)) {
            $errors[] = "spec/README.md: cannot read id range «{$range}» for {$file}";
            continue;
        }
        for ($n = (int) $r[2]; $n <= (int) $r[3]; $n++) {
            $listed[sprintf('%s-%0' . strlen($r[2]) . 'd', $r[1], $n)] = true;
        }
    }
    $inFile = $fileIds[$file] ?? [];
    foreach (array_keys(array_diff_key($listed, $inFile)) as $id) {
        $errors[] = "spec/README.md: {$file} range lists {$id}, not in the file";
    }
    foreach (array_keys(array_diff_key($inFile, $listed)) as $id) {
        $errors[] = "spec/README.md: {$file} has {$id}, outside its listed ranges";
    }
    $have = array_count_values($inFile);
    foreach (['R1' => 3, 'R2' => 4, 'R3' => 5] as $prio => $col) {
        if ($c[$col] !== '—' && !ctype_digit($c[$col])) {
            $errors[] = "spec/README.md: cannot read {$prio} count «{$c[$col]}» for {$file}";
        } elseif ((int) $c[$col] !== ($have[$prio] ?? 0)) {
            $errors[] = "spec/README.md: {$file} {$prio} count is " . (int) $c[$col] . ', the file has ' . ($have[$prio] ?? 0);
        }
    }
}
foreach (glob($specDir . '/*.md') ?: [] as $f) {
    if (basename($f) !== 'README.md' && !isset($indexed[basename($f)])) {
        $errors[] = basename($f) . ' is not listed in spec/README.md';
    }
}

const PACKAGE_NOTE = '> **تنبيه الحزمة:**';

/** Non-empty trimmed lines [lineNo => text], without what the repository added on transfer: `<!-- … -->`
 *  provenance headers and `> **تنبيه الحزمة:** …` package notes (spec/README.md, «ملاحظات النقل»). */
function content_lines(string $text): array
{
    $out = [];
    foreach (explode("\n", $text) as $n => $line) {
        $line = trim($line);
        if ($line !== '' && !str_starts_with($line, '<!--') && !str_starts_with($line, PACKAGE_NOTE)) {
            $out[$n + 1] = $line;
        }
    }
    return $out;
}

function short(string $s): string
{
    return mb_strimwidth($s, 0, 70, '…');
}

// 1c) The split files equal the source export they were cut from (scripts/split-spec.php), section by section.
// A difference is the owner's decision, never a hand fix (phase-0-foundation.md, WP-0.3).
$fixHint = 'record an Open D-xx in docs/00-governance/decisions.md; do not edit the spec by hand';
$readmeText = is_file($specDir . '/README.md') ? (string) file_get_contents($specDir . '/README.md') : '';
$sourceName = null;
$sections = [];
$packageNotes = 0;
if (!preg_match('/`\.\.\/source\/([^`]+\.md)`/u', $readmeText, $m)) {
    $errors[] = 'spec/README.md does not name the source file (`../source/<file>.md`)';
} elseif (!is_file($root . '/docs/01-product/source/' . $m[1])) {
    $errors[] = "source file docs/01-product/source/{$m[1]} does not exist";
} else {
    $sourceName = $m[1];
    foreach (preg_split('/^(?=## )/m', (string) file_get_contents($root . '/docs/01-product/source/' . $sourceName)) as $part) {
        if (!str_starts_with($part, '## ')) {
            continue; // title and provenance above the first section
        }
        $heading = trim(substr(strstr($part, "\n", true) ?: $part, 3));
        if (isset($sections[$heading])) {
            $errors[] = "source ({$sourceName}) has two sections «{$heading}»";
        }
        $sections[$heading] = $part;
    }
    $sectionFile = [];
    foreach (glob($specDir . '/*.md') ?: [] as $f) {
        $file = basename($f);
        if ($file === 'README.md' || str_contains($file, '22-learning')) {
            continue; // the index and the CR proposal are not part of the source
        }
        $text = (string) file_get_contents($f);
        $packageNotes += substr_count($text, "\n" . PACKAGE_NOTE);
        $split = content_lines($text);
        $heading = preg_match('/^## (.+)$/', (string) reset($split), $h) ? trim($h[1]) : '?';
        if (!isset($sections[$heading])) {
            $errors[] = "{$file}: section «{$heading}» is not in the source ({$sourceName}) — {$fixHint}";
            continue;
        }
        $sectionFile[$heading] = $file;
        $src = array_values(content_lines($sections[$heading]));
        $i = 0;
        foreach ($split as $lineNo => $line) {
            if (($src[$i] ?? null) !== $line) {
                $errors[] = "{$file} differs from the source ({$sourceName}) at line {$lineNo}: «" . short($line)
                    . '» vs source «' . short($src[$i] ?? '(end of section)') . "» — {$fixHint}";
                continue 2;
            }
            $i++;
        }
        if ($i < count($src)) {
            $errors[] = "{$file} differs from the source ({$sourceName}): the source continues with «" . short($src[$i]) . "» — {$fixHint}";
        }
    }
    $sourceIds = [];
    foreach ($sections as $heading => $text) {
        if (!isset($sectionFile[$heading])) {
            $errors[] = "source section «{$heading}» has no split file — {$fixHint}";
        } elseif (!holds_requirements($sectionFile[$heading])) {
            continue;
        }
        foreach (requirement_rows(table_rows($text)) as [$id]) {
            $sourceIds[$id] = true;
        }
    }
    foreach (array_keys(array_diff_key($specIds, $sourceIds)) as $id) {
        $errors[] = "{$id} is in the split spec but not in the source ({$sourceName}) — {$fixHint}";
    }
    foreach (array_keys(array_diff_key($sourceIds, $specIds)) as $id) {
        $errors[] = "{$id} is in the source but not in the split spec ({$sourceName}) — {$fixHint}";
    }
}

// 2) Traceability table: one row per spec ID, with the spec's priority and file, assigned to at least one
// work package; plus the business-rules table (BR-xxx => work packages).
$trace = [];
$rules = [];
foreach (rows($root . '/docs/03-plan/traceability.md') as $c) {
    if (count($c) === 2 && preg_match('/^BR-\d{3}$/', $c[0])) {
        $rules[$c[0]] = preg_split('/\s*,\s*/', $c[1]);
        continue;
    }
    if (count($c) !== 7 || !preg_match('/^[A-Z]{2,5}-\d{2,3}$/', $c[0])) {
        continue;
    }
    [$id, $prio, , $backend, $experience, $file] = $c;
    if (!isset($specIds[$id])) {
        $errors[] = "{$id} is in docs/03-plan/traceability.md but not in the spec";
        continue;
    }
    if (isset($trace[$id])) {
        $errors[] = "duplicate traceability row {$id}";
    }
    $trace[$id] = ['backend' => $backend, 'experience' => $experience, 'status' => $c[6]];
    if (!str_starts_with($prio, $specPrio[$id])) {
        $errors[] = "{$id}: traceability priority «{$prio}» does not match the spec ({$specPrio[$id]})";
    }
    if (trim($file, '`') !== $specIds[$id]) {
        $errors[] = "{$id}: traceability names " . trim($file, '`') . "; the spec has it in {$specIds[$id]}";
    }
    if ($backend === '—' && $experience === '—') {
        $errors[] = "{$id} is not assigned to any work package";
    }
}
foreach ($specIds as $id => $file) {
    if (!isset($trace[$id])) {
        $errors[] = "{$id} ({$file}) is not mapped in docs/03-plan/traceability.md";
    }
}

// 3) Status table and phase files.
$status = [];
foreach (rows($root . '/docs/03-plan/status.md') as $c) {
    if (count($c) >= 6 && preg_match('/^WP-\d+\.\d+$/', $c[0])) {
        $status[$c[0]] = $c[5];
    }
}
$phaseText = implode("\n", array_map('file_get_contents', glob($root . '/docs/03-plan/phase-*.md') ?: []));
foreach ($trace as $id => $t) {
    foreach (['backend', 'experience'] as $k) {
        $wp = $t[$k];
        if ($wp !== '—' && !isset($status[$wp])) {
            $errors[] = "{$id}: {$k} work package {$wp} is missing from status.md";
        }
    }
}
foreach ($rules as $rule => $wps) {
    foreach ($wps as $wp) {
        if (!isset($status[$wp])) {
            $errors[] = "{$rule}: work package {$wp} is missing from status.md";
        }
    }
}
foreach (array_keys($status) as $wp) {
    $q = preg_quote($wp, '/');
    if (!preg_match("/^(### {$q}\\s|\\| {$q} \\|)/m", $phaseText)) {
        $errors[] = "{$wp} is in status.md but has no definition in docs/03-plan/phase-*.md (a `### {$wp}` heading or a `| {$wp} |` row)";
    }
}

// 4) Tests.
$testFiles = [];
$dirs = array_merge(
    glob($root . '/apps/api/tests') ?: [],
    glob($root . '/apps/*/test') ?: [],
    glob($root . '/apps/*/integration_test') ?: [],
    glob($root . '/packages/*/test') ?: [],
    glob($root . '/e2e') ?: []
);
foreach ($dirs as $d) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (preg_match('/\.(php|dart|ts|js)$/', $file->getFilename())) {
            $testFiles[] = $file->getPathname();
        }
    }
}
$testIndex = [];
foreach ($testFiles as $tf) {
    $src = (string) file_get_contents($tf);
    if (preg_match_all('/\b(it|test|testWidgets|group|describe)\(\s*[\'"]([A-Z]{2,5}-\d{2,3})\b/', $src, $m)) {
        foreach ($m[2] as $id) {
            $testIndex[$id][] = substr($tf, strlen($root) + 1);
        }
    }
}
foreach (array_keys($testIndex) as $id) {
    if (!isset($specIds[$id]) && !isset($rules[$id])) {
        $errors[] = "test name references unknown requirement id {$id} (" . $testIndex[$id][0] . ')';
    }
}

if (!$quick) {
    $targetWps = $wpArg ? [$wpArg] : array_keys(array_filter($status, fn ($s) => in_array($s, ['Review', 'Done'], true)));
    foreach ($targetWps as $wp) {
        if (!isset($status[$wp])) {
            $errors[] = "unknown work package {$wp}";
            continue;
        }
        foreach ($trace as $id => $t) {
            if (($t['backend'] === $wp || $t['experience'] === $wp) && empty($testIndex[$id])) {
                $errors[] = "{$wp}: {$id} has no test whose name starts with the ID";
            }
        }
    }
}

$source = $sourceName === null ? 'missing'
    : "{$sourceName} (" . count($sections) . ' sections · ' . $packageNotes . ' package note' . ($packageNotes === 1 ? '' : 's') . ')';
echo 'requirements: ' . count($specIds) . ' ' . fmt_split($actualTotals) . " · source: {$source} · mapped: " . count($trace)
    . ' · work packages: ' . count($status) . ' · test files: ' . count($testFiles) . ' · ids referenced by tests: ' . count($testIndex) . "\n";
if ($errors) {
    foreach (array_slice($errors, 0, 60) as $e) {
        echo "✖ {$e}\n";
    }
    echo count($errors) . " problem(s)\n";
    exit(1);
}
echo "✔ traceability OK\n";
exit(0);
