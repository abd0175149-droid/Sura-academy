<?php
declare(strict_types=1);
/**
 * Sura Academy data-model (ERD) checks, WP-0.7.
 * The ERD documents in docs/02-architecture/erd/ agree with spec/14 (every spec table is mapped), with
 * data-model-conventions.md (types, names, append-only list) and with traceability.md (every R1 requirement
 * is covered); the migration order is FK-safe; the PII registry matches the PII tags; the Mermaid diagrams are
 * generated from the column tables. Self-test: scripts/test-erd.php.
 *   php scripts/check-erd.php            check
 *   php scripts/check-erd.php --write    regenerate the Mermaid blocks, then check
 *   --root <dir>   check another tree instead of this repository (used by scripts/test-erd.php)
 */
$root = dirname(__DIR__);
$write = false;
for ($i = 1; $i < $argc; $i++) {
    match ($argv[$i]) {
        '--write' => $write = true,
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

const ERD_DIR = 'docs/02-architecture/erd';
const SPEC14 = 'docs/01-product/spec/14-data-model-and-states.md';
const TRACE = 'docs/03-plan/traceability.md';
const CONVENTIONS = 'docs/02-architecture/data-model-conventions.md';
const NOT_MODULES = ['README.md', 'pii-registry.md', 'r1-coverage.md'];
const KINDS = ['mutable', 'append-only', 'derived', 'package', 'framework'];
const DISPOSITIONS = ['same', 'renamed', 'merged', 'package', 'deferred'];
const PII_ACTIONS = ['erase', 'pseudonymize', 'delete_row', 'delete_file', 'retain_until_period'];
const TYPES = '/^(uuid|string|text|smallint|integer|bigint|boolean|timestamptz|date|time|jsonb|inet|char\(\d+\))$/';
const REQ_ID = '/^[A-Z]{2,5}-\d{2,3}$/';

$errors = [];

function read(string $root, string $rel, array &$errors): string
{
    if (!is_file("{$root}/{$rel}")) {
        $errors[] = "missing {$rel}";
        return '';
    }
    return (string) file_get_contents("{$root}/{$rel}");
}

/** Cells of a Markdown table line; `\|` inside a cell is a literal pipe. */
function cells(string $line): array
{
    $inner = preg_replace('/^\||(?<!\\\\)\|$/', '', trim($line));
    return array_map(static fn (string $c): string => str_replace('\|', '|', trim($c)), preg_split('/(?<!\\\\)\|/', $inner));
}

/** Table rows (header included, separator lines excluded) of a block of Markdown. */
function rows(string $text): array
{
    $out = [];
    foreach (explode("\n", $text) as $line) {
        if (str_starts_with($line, '|') && !preg_match('/^\|\s*:?-{3}/', $line)) {
            $out[] = cells($line);
        }
    }
    return $out;
}

/** Text under the heading line $heading, up to the next heading of the same or a higher level. */
function section(string $text, string $heading): ?string
{
    $level = strspn($heading, '#');
    $lines = explode("\n", $text);
    $start = array_search($heading, array_map('rtrim', $lines), true);
    if ($start === false) {
        return null;
    }
    $out = [];
    for ($i = $start + 1; $i < count($lines); $i++) {
        if (preg_match('/^(#+) /', $lines[$i], $m) && strlen($m[1]) <= $level) {
            break;
        }
        $out[] = $lines[$i];
    }
    return implode("\n", $out);
}

// ---------- inputs ----------
$specTables = [];
$spec14 = read($root, SPEC14, $errors);
$domains = section($spec14, '### الجداول حسب المجال');
if ($spec14 !== '' && $domains === null) {
    $errors[] = SPEC14.': no «### الجداول حسب المجال» section';
}
foreach (rows($domains ?? '') as $c) {
    if (count($c) < 2 || $c[0] === 'المجال') {
        continue;
    }
    foreach (preg_split('/\s*،\s*/u', $c[1]) as $t) {
        if ($t !== '') {
            $specTables[] = $t;
        }
    }
}

$trace = read($root, TRACE, $errors);
$knownIds = [];
foreach (rows($trace) as $c) {
    if (preg_match(REQ_ID, $c[0])) {
        $knownIds[$c[0]] = true;
    }
}
$r1 = [];
foreach (rows(section($trace, '## المتطلبات') ?? '') as $c) {
    if (count($c) >= 2 && preg_match(REQ_ID, $c[0]) && str_starts_with($c[1], 'R1')) {
        $r1[] = $c[0];
    }
}

$appendOnlyListed = [];
foreach (explode("\n", section(read($root, CONVENTIONS, $errors), '## جداول الإضافة فقط') ?? '') as $line) {
    if (preg_match_all('/`([a-z0-9_]+)`/', $line, $m)) {
        $appendOnlyListed = $m[1];
        break;
    }
}

// ---------- module files ----------
/** name => [module, file, kind, cols => [name => [type, null, ref, notes]]] */
$tables = [];
$moduleFiles = [];
foreach (glob("{$root}/".ERD_DIR.'/*.md') ?: [] as $path) {
    $file = basename($path);
    if (in_array($file, NOT_MODULES, true)) {
        continue;
    }
    $text = (string) file_get_contents($path);
    if (!preg_match('/^# ERD — (\S+)/m', $text, $m)) {
        $errors[] = "{$file}: first heading must be «# ERD — <Module>»";
        continue;
    }
    $module = $m[1];
    $moduleFiles[$file] = ['path' => $path, 'module' => $module, 'tables' => []];
    $current = null;
    foreach (explode("\n", $text) as $line) {
        if (preg_match('/^### `([a-z0-9_]+)`\s*$/', $line, $m)) {
            $current = $m[1];
            if (isset($tables[$current])) {
                $errors[] = "table {$current} is defined in {$tables[$current]['file']} and {$file}";
                $current = null;
                continue;
            }
            $tables[$current] = ['module' => $module, 'file' => $file, 'kind' => null, 'cols' => []];
            $moduleFiles[$file]['tables'][] = $current;
            continue;
        }
        if ($current === null) {
            continue;
        }
        if (preg_match('/^#{1,3} /', $line)) {
            $current = null;
            continue;
        }
        if ($tables[$current]['kind'] === null && preg_match('/^- Kind:\s*([a-z-]+)/', $line, $m)) {
            $tables[$current]['kind'] = $m[1];
            continue;
        }
        if (!str_starts_with($line, '|') || preg_match('/^\|\s*:?-{3}/', $line)) {
            continue;
        }
        $c = cells($line);
        if ($c[0] === 'Column') {
            continue;
        }
        if (count($c) !== 5) {
            $errors[] = "{$current}: cannot read column row «{$c[0]}» (".count($c).' cells; column rows have 5)';
            continue;
        }
        $tables[$current]['cols'][$c[0]] = ['type' => $c[1], 'null' => $c[2], 'ref' => $c[3], 'notes' => $c[4]];
    }
}

function has_col(array $tables, string $table, string $col): bool
{
    return isset($tables[$table]['cols'][$col]);
}

// ---------- diagrams (generated; --write rewrites them) ----------
function diagram(array $tables, array $names): string
{
    $entities = '';
    $relations = '';
    foreach ($names as $t) {
        $entities .= "  {$t} {\n";
        foreach ($tables[$t]['cols'] as $name => $col) {
            $pk = (bool) preg_match('/\bPK\b/', $col['notes']);
            $fk = $col['ref'] !== '';
            if (!$pk && !$fk) {
                continue;
            }
            $type = preg_replace('/\(.*$/', '', $col['type']);
            $entities .= "    {$type} {$name} ".($pk && $fk ? 'PK, FK' : ($pk ? 'PK' : 'FK'))."\n";
            if ($fk) {
                $target = explode('.', $col['ref'])[0];
                $card = $col['null'] === 'yes' ? '|o--o{' : '||--o{';
                $relations .= "  {$target} {$card} {$t} : \"{$name}\"\n";
            }
        }
        $entities .= "  }\n";
    }
    return "```mermaid\nerDiagram\n{$entities}{$relations}```\n";
}

$start = '<!-- erd:diagram:start -->';
$end = '<!-- erd:diagram:end -->';
foreach ($moduleFiles as $file => $info) {
    $text = (string) file_get_contents($info['path']);
    $a = strpos($text, $start);
    $b = strpos($text, $end);
    if ($a === false || $b === false || $b < $a) {
        $errors[] = "{$file}: no {$start} … {$end} block";
        continue;
    }
    $current = substr($text, $a + strlen($start), $b - $a - strlen($start));
    $generated = diagram($tables, $info['tables']);
    if (trim($current) === trim($generated)) {
        continue;
    }
    if ($write) {
        file_put_contents($info['path'], substr($text, 0, $a + strlen($start))."\n{$generated}".substr($text, $b));
        continue;
    }
    $errors[] = "{$file}: the Mermaid diagram is stale (run php scripts/check-erd.php --write)";
}

// ---------- E3 convention lint, E4 append-only ----------
foreach ($tables as $t => $info) {
    $kind = $info['kind'];
    if (!in_array($kind, KINDS, true)) {
        $errors[] = "{$t}: unknown kind ".($kind ?? '(none)');
    }
    $cols = $info['cols'];
    if (!array_filter($cols, static fn (array $c): bool => (bool) preg_match('/\bPK\b/', $c['notes']))) {
        $errors[] = "{$t}: no column is marked PK";
    }
    foreach ($cols as $name => $c) {
        if (!in_array($c['null'], ['yes', 'no'], true)) {
            $errors[] = "{$t}.{$name}: Null must be yes or no";
        }
        if (!preg_match(TYPES, $c['type'])) {
            $errors[] = "{$t}.{$name}: type {$c['type']} is not allowed";
        }
        if ($c['ref'] !== '') {
            [$rt, $rc] = array_pad(explode('.', $c['ref'], 2), 2, '');
            if (!has_col($tables, $rt, $rc)) {
                $errors[] = "{$t}.{$name} references {$c['ref']}, which is not in the module files";
            }
        }
    }
    if (in_array($kind, ['package', 'framework'], true)) {
        continue;
    }
    if (($cols['id']['type'] ?? '') !== 'uuid') {
        $errors[] = "{$t}: id must be uuid";
    }
    $hasCurrency = (bool) array_filter(
        $cols,
        static fn (array $c, string $n): bool => str_ends_with($n, 'currency') && $c['type'] === 'char(3)',
        ARRAY_FILTER_USE_BOTH,
    );
    $hasZone = isset($cols['ref_timezone']) || isset($cols['timezone']);
    foreach ($cols as $name => $c) {
        $type = $c['type'];
        $rule = match (true) {
            str_ends_with($name, '_at') && $type !== 'timestamptz' => '*_at columns are timestamptz',
            str_ends_with($name, '_on') && $type !== 'date' => '*_on columns are date',
            str_ends_with($name, '_minor') && $type !== 'bigint' => '*_minor columns are bigint',
            str_ends_with($name, '_minor') && !$hasCurrency => 'money needs a currency char(3) column in the same table',
            str_ends_with($name, '_bps') && $type !== 'integer' => '*_bps columns are integer',
            $type === 'time' && !$hasZone => 'time columns need ref_timezone or timezone in the same table',
            default => null,
        };
        if ($rule !== null) {
            $errors[] = "{$t}.{$name}: {$rule}";
        }
        if (str_ends_with($name, '_id')) {
            $poly = str_contains($c['notes'], 'polymorphic');
            if ($type !== 'uuid') {
                $errors[] = "{$t}.{$name}: *_id columns are uuid";
            } elseif ($c['ref'] === '' && !$poly) {
                $errors[] = "{$t}.{$name}: *_id columns reference a table (or say polymorphic)";
            } elseif ($poly && !isset($cols[substr($name, 0, -3).'_type'])) {
                $errors[] = "{$t}.{$name}: polymorphic id needs ".substr($name, 0, -3).'_type';
            }
        }
        if ((in_array($name, ['status', 'kind', 'type'], true) || str_ends_with($name, '_status'))
            && !preg_match('/values:|TBD\(WP-\d+\.\d+\)/', $c['notes'])) {
            $errors[] = "{$t}.{$name}: list values (values: a|b) or TBD(WP-x.y)";
        }
    }
    if (in_array($kind, ['mutable', 'derived'], true) && (!isset($cols['created_at']) || !isset($cols['updated_at']))) {
        $errors[] = "{$t}: {$kind} tables need created_at and updated_at";
    }
    if ($kind === 'append-only') {
        if (!isset($cols['created_at'])) {
            $errors[] = "{$t}: append-only tables need created_at";
        }
        if (isset($cols['updated_at'])) {
            $errors[] = "{$t}: append-only tables have no updated_at";
        }
    }
}
foreach ($appendOnlyListed as $t) {
    if (!isset($tables[$t])) {
        $errors[] = "{$t} is append-only in data-model-conventions.md but is not in the module files";
    } elseif ($tables[$t]['kind'] !== 'append-only') {
        $errors[] = "{$t} is append-only in data-model-conventions.md but its kind is {$tables[$t]['kind']}";
    }
}
foreach ($tables as $t => $info) {
    if ($info['kind'] === 'append-only' && !in_array($t, $appendOnlyListed, true)) {
        $errors[] = "{$t} is marked append-only but is not in the data-model-conventions.md list";
    }
}

// ---------- E1 spec/14 mapping, E2 migration order ----------
$readme = read($root, ERD_DIR.'/README.md', $errors);
$mapping = section($readme, '## Spec/14 mapping');
if ($readme !== '' && $mapping === null) {
    $errors[] = ERD_DIR.'/README.md: no «## Spec/14 mapping» section';
}
$mapped = [];
foreach (rows($mapping ?? '') as $c) {
    if ($c[0] === 'spec/14 table') {
        continue;
    }
    if (count($c) !== 5) {
        $errors[] = "mapping: cannot read row «{$c[0]}» (".count($c).' cells; mapping rows have 5)';
        continue;
    }
    [$spec, , $disp, $approved, $note] = $c;
    $mapped[$spec] = ($mapped[$spec] ?? 0) + 1;
    if (!in_array($spec, $specTables, true)) {
        $errors[] = "mapping row {$spec} is not a spec/14 table";
    }
    if (!in_array($disp, DISPOSITIONS, true)) {
        $errors[] = "mapping {$spec}: unknown disposition {$disp}";
        continue;
    }
    if ($disp === 'deferred') {
        if (!preg_match('/[A-Z]{2,5}-\d{2,3}/', $note)) {
            $errors[] = "mapping {$spec}: deferred needs the requirement id that brings it";
        }
        continue;
    }
    if (!isset($tables[$approved])) {
        $errors[] = "mapping {$spec} → {$approved}: no such table in the module files";
        continue;
    }
    if ($disp === 'same' && $approved !== $spec) {
        $errors[] = "mapping {$spec}: disposition same needs the approved name {$spec}";
    }
    if ($disp === 'package' && $tables[$approved]['kind'] !== 'package') {
        $errors[] = "mapping {$spec}: disposition package but {$approved} is not a package table";
    }
}
$mappedOnce = 0;
foreach (array_unique($specTables) as $t) {
    $n = $mapped[$t] ?? 0;
    if ($n === 0) {
        $errors[] = "spec/14 table {$t} has no row in the mapping";
    } elseif ($n > 1) {
        $errors[] = "spec/14 table {$t} is mapped {$n} times";
    } else {
        $mappedOnce++;
    }
}

$order = section($readme, '## Migration order');
if ($readme !== '' && $order === null) {
    $errors[] = ERD_DIR.'/README.md: no «## Migration order» section';
}
$position = [];
foreach (rows($order ?? '') as $c) {
    if ($c[0] === '#') {
        continue;
    }
    if (count($c) !== 4) {
        $errors[] = "migration order: cannot read row «{$c[0]}» (".count($c).' cells; order rows have 4)';
        continue;
    }
    [, $t, $module] = $c;
    if (!isset($tables[$t])) {
        $errors[] = "migration order names {$t}, which is not in the module files";
        continue;
    }
    if (isset($position[$t])) {
        $errors[] = "table {$t} appears more than once in the migration order";
        continue;
    }
    if ($module !== $tables[$t]['module']) {
        $errors[] = "migration order row {$t} says module {$module}; the table is in {$tables[$t]['module']}";
    }
    $position[$t] = count($position);
}
foreach ($tables as $t => $info) {
    if (!isset($position[$t])) {
        $errors[] = "table {$t} is missing from the migration order";
        continue;
    }
    foreach ($info['cols'] as $name => $c) {
        $target = $c['ref'] === '' ? null : explode('.', $c['ref'])[0];
        if ($target !== null && $target !== $t && isset($position[$target]) && $position[$target] > $position[$t]) {
            $errors[] = "{$t}.{$name} references {$target}, which comes later in the migration order";
        }
    }
}

// ---------- E5 R1 coverage ----------
$coverage = read($root, ERD_DIR.'/r1-coverage.md', $errors);
$listed = [];
foreach (rows($coverage) as $c) {
    if (!preg_match(REQ_ID, $c[0])) {
        continue;
    }
    $id = $c[0];
    if (count($c) !== 4) {
        $errors[] = "r1-coverage.md: cannot read row {$id} (".count($c).' cells; coverage rows have 4)';
        continue;
    }
    $listed[$id] = ($listed[$id] ?? 0) + 1;
    if (!isset($knownIds[$id])) {
        $errors[] = "r1-coverage.md lists {$id}, which is not in traceability.md";
    }
    preg_match_all('/`([a-z0-9_]+)\.([a-z0-9_]+)`/', $c[2], $m, PREG_SET_ORDER);
    if ($c[1] === 'Y') {
        if ($m === []) {
            $errors[] = "{$id} is marked Y but cites no table.column";
        }
        foreach ($m as [, $t, $col]) {
            if (!has_col($tables, $t, $col)) {
                $errors[] = "{$id} cites {$t}.{$col}, which is not in the module files";
            }
        }
    } elseif ($c[1] === 'N') {
        if ($c[3] === '') {
            $errors[] = "{$id} is marked N without a reason";
        }
    } else {
        $errors[] = "{$id}: Data must be Y or N";
    }
}
foreach ($listed as $id => $n) {
    if ($n > 1) {
        $errors[] = "{$id} is listed {$n} times in r1-coverage.md";
    }
}
$covered = 0;
foreach ($r1 as $id) {
    if (isset($listed[$id])) {
        $covered++;
    } else {
        $errors[] = "R1 requirement {$id} has no row in r1-coverage.md";
    }
}

// ---------- E6 PII registry ----------
$registry = [];
foreach (rows(read($root, ERD_DIR.'/pii-registry.md', $errors)) as $c) {
    if (!preg_match('/^`([a-z0-9_]+)\.([a-z0-9_]+)`$/', $c[0], $m)) {
        continue;
    }
    $key = "{$m[1]}.{$m[2]}";
    $registry[$key] = true;
    if (count($c) !== 5) {
        $errors[] = "pii-registry.md: cannot read row {$key} (".count($c).' cells; registry rows have 5)';
        continue;
    }
    if (!in_array($c[2], PII_ACTIONS, true)) {
        $errors[] = "pii-registry.md {$key}: unknown action {$c[2]}";
    }
    if (!has_col($tables, $m[1], $m[2])) {
        $errors[] = "pii-registry.md names {$key}, which is not in the module files";
    } elseif (!preg_match('/\bPII\b/', $tables[$m[1]]['cols'][$m[2]]['notes'])) {
        $errors[] = "pii-registry.md names {$key}, which is not tagged PII in its module file";
    }
}
foreach ($tables as $t => $info) {
    foreach ($info['cols'] as $name => $c) {
        if (preg_match('/\bPII\b/', $c['notes']) && !isset($registry["{$t}.{$name}"])) {
            $errors[] = "{$t}.{$name} is tagged PII but is not in pii-registry.md";
        }
    }
}

// ---------- report ----------
$specCount = count(array_unique($specTables));
echo 'erd: tables '.count($tables).' (modules '.count($moduleFiles).") · spec/14 mapped {$mappedOnce}/{$specCount}"
    .' · migration order '.count($position)." · R1 covered {$covered}/".count($r1).' · pii columns '.count($registry)."\n";
foreach (array_unique($errors) as $e) {
    echo "✗ {$e}\n";
}
if ($errors !== []) {
    exit(1);
}
echo "✔ erd OK\n";
