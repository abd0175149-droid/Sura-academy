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
$warnings = [];

function rows(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    $out = [];
    foreach (explode("\n", (string) file_get_contents($file)) as $line) {
        if (!str_starts_with($line, '| ')) {
            continue;
        }
        $out[] = array_map('trim', explode('|', trim(trim($line), '|')));
    }
    return $out;
}

// 1) Requirement IDs from the spec (rows whose first cell is an ID and last cell starts with R1/R2/R3).
$specIds = [];
foreach (glob($root . '/docs/01-product/spec/*.md') ?: [] as $f) {
    if (str_contains($f, '21-appendices') || str_contains($f, '22-learning')) {
        continue;
    }
    foreach (rows($f) as $c) {
        if (count($c) >= 3 && preg_match('/^[A-Z]{2,5}-\d{2,3}$/', $c[0]) && preg_match('/^R[123]/', end($c))) {
            $specIds[$c[0]] = basename($f);
        }
    }
}
if (!$specIds) {
    $errors[] = 'no requirement rows found under docs/01-product/spec';
}

// 2) Traceability table.
$trace = [];
foreach (rows($root . '/docs/03-plan/traceability.md') as $c) {
    if (count($c) === 7 && preg_match('/^[A-Z]{2,5}-\d{2,3}$/', $c[0]) && isset($specIds[$c[0]])) {
        if (isset($trace[$c[0]])) {
            $errors[] = "duplicate traceability row {$c[0]}";
        }
        $trace[$c[0]] = ['backend' => $c[3], 'experience' => $c[4], 'status' => $c[6]];
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
$phaseText = '';
foreach (glob($root . '/docs/03-plan/phase-*.md') ?: [] as $f) {
    $phaseText .= file_get_contents($f);
}
foreach ($trace as $id => $t) {
    foreach (['backend', 'experience'] as $k) {
        $wp = $t[$k];
        if ($wp !== '—' && !isset($status[$wp])) {
            $errors[] = "{$id}: {$k} work package {$wp} is missing from status.md";
        }
    }
}
foreach (array_keys($status) as $wp) {
    if (!str_contains($phaseText, $wp)) {
        $errors[] = "{$wp} is in status.md but has no definition in docs/03-plan/phase-*.md";
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
    if (!isset($specIds[$id]) && !preg_match('/^BR-0(0[1-9]|1[0-8])$/', $id)) {
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

$counts = array_count_values(array_map(fn ($f) => $f, array_keys($specIds)));
echo 'requirements: ' . count($specIds) . ' · mapped: ' . count($trace) . ' · work packages: ' . count($status)
    . ' · test files: ' . count($testFiles) . ' · ids referenced by tests: ' . count($testIndex) . "\n";
foreach ($warnings as $w) {
    echo "⚠ {$w}\n";
}
if ($errors) {
    foreach (array_slice($errors, 0, 60) as $e) {
        echo "✖ {$e}\n";
    }
    echo count($errors) . " problem(s)\n";
    exit(1);
}
echo "✔ traceability OK\n";
exit(0);
