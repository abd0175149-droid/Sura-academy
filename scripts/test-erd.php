<?php

declare(strict_types=1);

/*
 * Regression tests for scripts/check-erd.php, WP-0.7.
 *
 *   php scripts/test-erd.php
 *
 * Builds a small fixture repository (spec/14, traceability, data-model conventions and an ERD folder with two
 * module files, the README mapping and migration order, the PII registry and the R1 coverage list) in a
 * throwaway directory, applies one mutation per case, runs the checker with --root against it and asserts the
 * exit code and a fragment of its output. A mutation that matches nothing in the fixture fails its case, so no
 * case can pass by accident.
 */

$root = dirname(__DIR__);
// ERD_CHECKER points at another copy of the checker (used to prove this suite catches a broken check).
$checker = getenv('ERD_CHECKER') ?: $root.'/scripts/check-erd.php';
$php = PHP_BINARY;

$tmp = sys_get_temp_dir().'/sura-erd-tests-'.getmypid();
register_shutdown_function(static function () use ($tmp): void {
    if (is_dir($tmp)) {
        exec('rm -rf '.escapeshellarg($tmp));
    }
});

const SPEC14 = 'docs/01-product/spec/14-data-model-and-states.md';
const TRACE = 'docs/03-plan/traceability.md';
const CONV = 'docs/02-architecture/data-model-conventions.md';
const README = 'docs/02-architecture/erd/README.md';
const IDENTITY = 'docs/02-architecture/erd/identity.md';
const LEDGER = 'docs/02-architecture/erd/ledger.md';
const PII = 'docs/02-architecture/erd/pii-registry.md';
const COVERAGE = 'docs/02-architecture/erd/r1-coverage.md';

/** The consistent fixture repository: path => content. Every case starts from it. */
function fixture(): array
{
    $head = "| Column | Type | Null | References | Notes |\n| --- | --- | --- | --- | --- |\n";

    return [
        SPEC14 => "## نموذج البيانات\n\n### الجداول حسب المجال\n\n| المجال | الجداول |\n| --- | --- |\n"
            ."| الهوية | users، roles |\n"
            ."| الرصيد | sessions، credit_ledger_entries، old_quizzes |\n\n### الكيانات الحرجة\n\nنص.\n",
        TRACE => "# مصفوفة التتبع\n\n## المتطلبات\n\n"
            ."| المعرّف | الأولوية | الميزة | حزمة الخلفية | حزمة التجربة | ملف المواصفات | الحالة |\n| --- | --- | --- | --- | --- | --- | --- |\n"
            ."| ALP-01 | R1 | دخول | WP-1.1 | — | `01-alpha.md` | — |\n"
            ."| ALP-02 | R1 | لوحة | WP-1.1 | — | `01-alpha.md` | — |\n"
            ."| ALP-03 | R2 | اختبارات | WP-6.1 | — | `01-alpha.md` | — |\n\n"
            ."## قواعد العمل\n\n| القاعدة | الحزمة |\n| --- | --- |\n| BR-001 | WP-1.1 |\n\n"
            ."## متطلبات مقترحة (CR-002، غير معتمدة)\n\n| المعرّف | الأولوية المقترحة | الميزة | الحزمة | الحالة |\n| --- | --- | --- | --- | --- |\n"
            ."| OUT-01 | R1 (بيانات، D-26) | نتاجات | WP-2.1 | Proposed |\n",
        CONV => "# اصطلاحات نموذج البيانات\n\n## جداول الإضافة فقط\n\n`credit_ledger_entries`، `audit_logs`.\n\nلكل منها:\n1. بلا `updated_at`.\n",
        README => "# ERD\n\n## Spec/14 mapping\n\n"
            ."| spec/14 table | Domain | Disposition | Approved table | Note |\n| --- | --- | --- | --- | --- |\n"
            ."| users | الهوية | same | users | |\n"
            ."| roles | الهوية | package | roles | spatie/laravel-permission |\n"
            ."| sessions | الرصيد | renamed | class_sessions | Laravel uses `sessions` |\n"
            ."| credit_ledger_entries | الرصيد | same | credit_ledger_entries | |\n"
            ."| old_quizzes | الرصيد | deferred | — | R2: ALP-03 |\n\n"
            ."## Migration order\n\n| # | Table | Module | Notes |\n| --- | --- | --- | --- |\n"
            ."| 1 | users | Identity | |\n"
            ."| 2 | roles | Identity | |\n"
            ."| 3 | audit_logs | Ledger | |\n"
            ."| 4 | class_sessions | Ledger | |\n"
            ."| 5 | credit_ledger_entries | Ledger | |\n",
        IDENTITY => "# ERD — Identity\n\nAccounts.\n\n<!-- erd:diagram:start -->\n```mermaid\nerDiagram\n"
            ."  users {\n    uuid id PK\n  }\n"
            ."  roles {\n    bigint id PK\n  }\n"
            ."```\n<!-- erd:diagram:end -->\n\n"
            ."### `users`\n- Kind: mutable · Spec: users · Reqs: ALP-01 · Scope: none (account)\n\n{$head}"
            ."| id | uuid | no | | PK (v7) |\n"
            ."| email | string | no | | PII |\n"
            ."| status | string | no | | values: active\\|suspended |\n"
            ."| timezone | string | no | | IANA |\n"
            ."| created_at | timestamptz | no | | |\n"
            ."| updated_at | timestamptz | no | | |\n\n"
            ."Constraints and indexes:\n- `UNIQUE (email)`\n\n"
            ."### `roles`\n- Kind: package · Spec: roles · Reqs: ALP-01 · Scope: none\n\n{$head}"
            ."| id | bigint | no | | PK |\n"
            ."| name | string | no | | |\n",
        LEDGER => "# ERD — Ledger\n\nCredits.\n\n<!-- erd:diagram:start -->\n```mermaid\nerDiagram\n"
            ."  audit_logs {\n    uuid id PK\n    uuid actor_id FK\n  }\n"
            ."  class_sessions {\n    uuid id PK\n    uuid teacher_id FK\n  }\n"
            ."  credit_ledger_entries {\n    uuid id PK\n    uuid student_id FK\n    uuid session_id FK\n    uuid reverses_entry_id FK\n  }\n"
            ."  users |o--o{ audit_logs : \"actor_id\"\n"
            ."  users ||--o{ class_sessions : \"teacher_id\"\n"
            ."  users ||--o{ credit_ledger_entries : \"student_id\"\n"
            ."  class_sessions |o--o{ credit_ledger_entries : \"session_id\"\n"
            ."  credit_ledger_entries |o--o{ credit_ledger_entries : \"reverses_entry_id\"\n"
            ."```\n<!-- erd:diagram:end -->\n\n"
            ."### `audit_logs`\n- Kind: append-only · Spec: NEW · Reqs: ALP-02 · Scope: none\n\n{$head}"
            ."| id | uuid | no | | PK |\n"
            ."| actor_id | uuid | yes | users.id | |\n"
            ."| entity_type | string | no | | |\n"
            ."| entity_id | uuid | no | | polymorphic |\n"
            ."| created_at | timestamptz | no | | |\n\n"
            ."### `class_sessions`\n- Kind: mutable · Spec: sessions · Reqs: ALP-02 · Scope: class_id\n\n{$head}"
            ."| id | uuid | no | | PK |\n"
            ."| teacher_id | uuid | no | users.id | |\n"
            ."| local_start | time | no | | |\n"
            ."| ref_timezone | string | no | | IANA |\n"
            ."| starts_at | timestamptz | no | | |\n"
            ."| held_on | date | yes | | |\n"
            ."| outcome | string | yes | | TBD(WP-2.3) |\n"
            ."| created_by | uuid | yes | users.id | |\n"
            ."| created_at | timestamptz | no | | |\n"
            ."| updated_at | timestamptz | no | | |\n\n"
            ."### `credit_ledger_entries`\n- Kind: append-only · Spec: credit_ledger_entries · Reqs: ALP-02 · Scope: student_id\n\n{$head}"
            ."| id | uuid | no | | PK |\n"
            ."| student_id | uuid | no | users.id | |\n"
            ."| session_id | uuid | yes | class_sessions.id | |\n"
            ."| reverses_entry_id | uuid | yes | credit_ledger_entries.id | |\n"
            ."| type | string | no | | values: CONSUMPTION\\|REVERSAL |\n"
            ."| qty | integer | no | | |\n"
            ."| price_minor | bigint | no | | |\n"
            ."| currency | char(3) | no | | ISO 4217 |\n"
            ."| discount_bps | integer | no | | |\n"
            ."| created_at | timestamptz | no | | |\n\n"
            ."Constraints and indexes:\n- `UNIQUE (student_id, session_id, type)`\n",
        PII => "# PII registry\n\n| Column | Subject | Locate by | Action | Retention | Source |\n| --- | --- | --- | --- | --- | --- |\n"
            ."| `users.email` | guardian | `users.id` | pseudonymize | account life | D-14 |\n",
        COVERAGE => "# R1 coverage\n\n| ID | Data | Columns | Note |\n| --- | --- | --- | --- |\n"
            ."| ALP-01 | Y | `users.email`, `users.status` | |\n"
            ."| ALP-02 | N | — | read-only dashboard |\n"
            ."| OUT-01 | Y | `credit_ledger_entries.type` | CR-002 data basis |\n",
    ];
}

/** Mutation: replace the first occurrence of $from in $file; fails the case when $from is absent. */
function swap(string $file, string $from, string $to): Closure
{
    return static function (array $files) use ($file, $from, $to): array {
        if (! isset($files[$file]) || ! str_contains($files[$file], $from)) {
            throw new RuntimeException("mutation matched nothing: {$file} has no «{$from}»");
        }
        $at = strpos($files[$file], $from);
        $files[$file] = substr_replace($files[$file], $to, $at, strlen($from));

        return $files;
    };
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

/** Runs the checker against $dir and returns [exit code, stdout + stderr]. */
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

$addColumn = static fn (string $file, string $after, string $row): Closure => swap($file, $after, $after.$row);
$identityTsRow = "| updated_at | timestamptz | no | | |\n";

// [name, mutation|null, extra args, expected exit code, output fragment]
$cases = [
    ['baseline fixture passes', null, [], 0,
        'erd: tables 5 (modules 2) · spec/14 mapped 5/5 · migration order 5 · R1 covered 2/2 · pii columns 1'],

    // E0 arguments.
    ['E0 unknown argument', null, ['--nope'], 2, 'unknown argument: --nope'],

    // E1 spec/14 mapping.
    ['E1 spec table not mapped', swap(README, "| roles | الهوية | package | roles | spatie/laravel-permission |\n", ''), [], 1,
        'spec/14 table roles has no row in the mapping'],
    ['E1 spec table mapped twice', swap(README, "| users | الهوية | same | users | |\n", "| users | الهوية | same | users | |\n| users | الهوية | same | users | |\n"), [], 1,
        'spec/14 table users is mapped 2 times'],
    ['E1 mapping row for a table not in spec/14', swap(README, "| users | الهوية | same | users | |\n", "| users | الهوية | same | users | |\n| ghosts | الهوية | same | users | |\n"), [], 1,
        'mapping row ghosts is not a spec/14 table'],
    ['E1 target table missing', swap(README, '| sessions | الرصيد | renamed | class_sessions |', '| sessions | الرصيد | renamed | lessons_held |'), [], 1,
        'mapping sessions → lessons_held: no such table in the module files'],
    ['E1 same needs the same name', swap(README, '| users | الهوية | same | users |', '| users | الهوية | same | class_sessions |'), [], 1,
        'mapping users: disposition same needs the approved name users'],
    ['E1 unknown disposition', swap(README, '| sessions | الرصيد | renamed |', '| sessions | الرصيد | moved |'), [], 1,
        'mapping sessions: unknown disposition moved'],
    ['E1 deferred needs a requirement id', swap(README, '| deferred | — | R2: ALP-03 |', '| deferred | — | later |'), [], 1,
        'mapping old_quizzes: deferred needs the requirement id that brings it'],
    ['E1 package disposition needs a package table', swap(README, '| roles | الهوية | package | roles |', '| roles | الهوية | package | users |'), [], 1,
        'mapping roles: disposition package but users is not a package table'],

    // E2 migration order.
    ['E2 table missing from the order', swap(README, "| 2 | roles | Identity | |\n", ''), [], 1,
        'table roles is missing from the migration order'],
    ['E2 unknown table in the order', swap(README, "| 2 | roles | Identity | |\n", "| 2 | roles | Identity | |\n| 2 | phantoms | Identity | |\n"), [], 1,
        'migration order names phantoms, which is not in the module files'],
    ['E2 fk target after the table', all(
        swap(README, "| 4 | class_sessions | Ledger | |\n", ''),
        swap(README, "| 5 | credit_ledger_entries | Ledger | |\n", "| 5 | credit_ledger_entries | Ledger | |\n| 6 | class_sessions | Ledger | |\n"),
    ), [], 1, 'credit_ledger_entries.session_id references class_sessions, which comes later in the migration order'],
    ['E2 module column disagrees', swap(README, '| 1 | users | Identity |', '| 1 | users | Ledger |'), [], 1,
        'migration order row users says module Ledger; the table is in Identity'],

    // E3 convention lint.
    ['E3 _at must be timestamptz', swap(LEDGER, '| starts_at | timestamptz |', '| starts_at | string |'), [], 1,
        'class_sessions.starts_at: *_at columns are timestamptz'],
    ['E3 _on must be date', swap(LEDGER, '| held_on | date |', '| held_on | timestamptz |'), [], 1,
        'class_sessions.held_on: *_on columns are date'],
    ['E3 _minor must be bigint', swap(LEDGER, '| price_minor | bigint |', '| price_minor | integer |'), [], 1,
        'credit_ledger_entries.price_minor: *_minor columns are bigint'],
    ['E3 _minor needs a currency column', swap(LEDGER, "| currency | char(3) | no | | ISO 4217 |\n", ''), [], 1,
        'credit_ledger_entries.price_minor: money needs a currency char(3) column in the same table'],
    ['E3 _bps must be integer', swap(LEDGER, '| discount_bps | integer |', '| discount_bps | bigint |'), [], 1,
        'credit_ledger_entries.discount_bps: *_bps columns are integer'],
    ['E3 _id must reference a table', swap(LEDGER, '| teacher_id | uuid | no | users.id |', '| teacher_id | uuid | no | |'), [], 1,
        'class_sessions.teacher_id: *_id columns reference a table (or say polymorphic)'],
    ['E3 _id must be uuid', swap(LEDGER, '| teacher_id | uuid | no | users.id |', '| teacher_id | bigint | no | users.id |'), [], 1,
        'class_sessions.teacher_id: *_id columns are uuid'],
    ['E3 polymorphic id needs a type column', swap(LEDGER, "| entity_type | string | no | | |\n", ''), [], 1,
        'audit_logs.entity_id: polymorphic id needs entity_type'],
    ['E3 reference to an unknown table', swap(LEDGER, '| teacher_id | uuid | no | users.id |', '| teacher_id | uuid | no | teachers.id |'), [], 1,
        'class_sessions.teacher_id references teachers.id, which is not in the module files'],
    ['E3 reference to an unknown column', swap(LEDGER, '| teacher_id | uuid | no | users.id |', '| teacher_id | uuid | no | users.uid |'), [], 1,
        'class_sessions.teacher_id references users.uid, which is not in the module files'],
    ['E3 float types are banned', swap(LEDGER, '| qty | integer |', '| qty | decimal(8,2) |'), [], 1,
        'credit_ledger_entries.qty: type decimal(8,2) is not allowed'],
    ['E3 timestamp without zone is banned', swap(LEDGER, '| starts_at | timestamptz |', '| starts_at | timestamp |'), [], 1,
        'class_sessions.starts_at: type timestamp is not allowed'],
    ['E3 table needs a uuid id', swap(IDENTITY, '| id | uuid | no | | PK (v7) |', '| id | bigint | no | | PK (v7) |'), [], 1,
        'users: id must be uuid'],
    ['E3 table needs a primary key', swap(IDENTITY, '| id | bigint | no | | PK |', '| id | bigint | no | | |'), [], 1,
        'roles: no column is marked PK'],
    ['E3 local time needs a zone column', swap(LEDGER, "| ref_timezone | string | no | | IANA |\n", ''), [], 1,
        'class_sessions.local_start: time columns need ref_timezone or timezone in the same table'],
    ['E3 status needs values', swap(IDENTITY, '| status | string | no | | values: active\\|suspended |', '| status | string | no | | |'), [], 1,
        'users.status: list values (values: a|b) or TBD(WP-x.y)'],
    ['E3 mutable table needs timestamps', swap(IDENTITY, $identityTsRow, ''), [], 1,
        'users: mutable tables need created_at and updated_at'],
    ['E3 unknown null marker', swap(IDENTITY, '| email | string | no |', '| email | string | maybe |'), [], 1,
        'users.email: Null must be yes or no'],
    ['E3 unknown kind', swap(IDENTITY, '- Kind: mutable · Spec: users', '- Kind: table · Spec: users'), [], 1,
        'users: unknown kind table'],
    ['E3 table defined twice', $addColumn(LEDGER, "Constraints and indexes:\n- `UNIQUE (student_id, session_id, type)`\n",
        "\n### `users`\n- Kind: mutable · Spec: users · Reqs: ALP-01 · Scope: none\n\n| Column | Type | Null | References | Notes |\n| --- | --- | --- | --- | --- |\n| id | uuid | no | | PK |\n"), [], 1,
        'table users is defined in identity.md and ledger.md'],

    // E4 append-only.
    ['E4 listed table not marked append-only', swap(LEDGER, '- Kind: append-only · Spec: credit_ledger_entries', '- Kind: mutable · Spec: credit_ledger_entries'), [], 1,
        'credit_ledger_entries is append-only in data-model-conventions.md but its kind is mutable'],
    ['E4 append-only table has updated_at', swap(LEDGER, "| discount_bps | integer | no | | |\n", "| discount_bps | integer | no | | |\n| updated_at | timestamptz | no | | |\n"), [], 1,
        'credit_ledger_entries: append-only tables have no updated_at'],
    ['E4 marked but not listed', swap(CONV, '`credit_ledger_entries`، `audit_logs`.', '`credit_ledger_entries`.'), [], 1,
        'audit_logs is marked append-only but is not in the data-model-conventions.md list'],
    ['E4 listed table missing', swap(CONV, '`credit_ledger_entries`، `audit_logs`.', '`credit_ledger_entries`، `audit_logs`، `wallet_entries`.'), [], 1,
        'wallet_entries is append-only in data-model-conventions.md but is not in the module files'],

    // E5 R1 coverage.
    ['E5 r1 id missing', swap(COVERAGE, "| ALP-02 | N | — | read-only dashboard |\n", ''), [], 1,
        'R1 requirement ALP-02 has no row in r1-coverage.md'],
    ['E5 id listed twice', swap(COVERAGE, "| ALP-02 | N | — | read-only dashboard |\n", "| ALP-02 | N | — | read-only dashboard |\n| ALP-02 | N | — | again |\n"), [], 1,
        'ALP-02 is listed 2 times in r1-coverage.md'],
    ['E5 unknown id', swap(COVERAGE, "| ALP-02 | N | — | read-only dashboard |\n", "| ALP-02 | N | — | read-only dashboard |\n| ZED-01 | N | — | ghost |\n"), [], 1,
        'r1-coverage.md lists ZED-01, which is not in traceability.md'],
    ['E5 unknown column', swap(COVERAGE, '`users.email`, `users.status`', '`users.email`, `users.nickname`'), [], 1,
        'ALP-01 cites users.nickname, which is not in the module files'],
    ['E5 Y without columns', swap(COVERAGE, '| ALP-01 | Y | `users.email`, `users.status` |', '| ALP-01 | Y | — |'), [], 1,
        'ALP-01 is marked Y but cites no table.column'],
    ['E5 N without reason', swap(COVERAGE, '| ALP-02 | N | — | read-only dashboard |', '| ALP-02 | N | — | |'), [], 1,
        'ALP-02 is marked N without a reason'],
    ['E5 bad data marker', swap(COVERAGE, '| ALP-02 | N |', '| ALP-02 | maybe |'), [], 1,
        'ALP-02: Data must be Y or N'],

    // E6 PII registry.
    ['E6 pii column not registered', swap(PII, "| `users.email` | guardian | `users.id` | pseudonymize | account life | D-14 |\n", ''), [], 1,
        'users.email is tagged PII but is not in pii-registry.md'],
    ['E6 registry column missing', swap(PII, '| `users.email` |', '| `users.mail` |'), [], 1,
        'pii-registry.md names users.mail, which is not in the module files'],
    ['E6 registry column not tagged', swap(IDENTITY, '| email | string | no | | PII |', '| email | string | no | | |'), [], 1,
        'pii-registry.md names users.email, which is not tagged PII in its module file'],
    ['E6 unknown action', swap(PII, '| `users.id` | pseudonymize |', '| `users.id` | shred |'), [], 1,
        'pii-registry.md users.email: unknown action shred'],

    ['E6 registry row needs a locate path', swap(PII, '| guardian | `users.id` |', '| guardian | |'), [], 1,
        'pii-registry.md users.email: Locate by names no table.column'],
    ['E6 locate path must exist', swap(PII, '| guardian | `users.id` |', '| guardian | `users.uuid` |'), [], 1,
        'pii-registry.md users.email: Locate by cites users.uuid, which is not in the module files'],

    // E8 free text is personal data unless stated otherwise.
    ['E8 free text needs PII or a no-PII reason', swap(LEDGER, "| held_on | date | yes | | |\n", "| held_on | date | yes | | |\n| cancel_note | text | yes | | |\n"), [], 1,
        'class_sessions.cancel_note: free text must be tagged PII or say no-PII: <why>'],
    ['E8 no-PII reason passes', swap(LEDGER, "| held_on | date | yes | | |\n", "| held_on | date | yes | | |\n| cancel_note | text | yes | | no-PII: chosen from a fixed list |\n"), [], 0,
        'erd OK'],

    // E7 diagrams.
    ['E7 diagram stale', swap(LEDGER, '| teacher_id | uuid | no | users.id |', '| teacher_id | uuid | yes | users.id |'), [], 1,
        'ledger.md: the Mermaid diagram is stale (run php scripts/check-erd.php --write)'],
    ['E7 audit columns stay out of the diagram', swap(LEDGER, "  users ||--o{ class_sessions : \"teacher_id\"\n", "  users ||--o{ class_sessions : \"teacher_id\"\n  users |o--o{ class_sessions : \"created_by\"\n"), [], 1,
        'ledger.md: the Mermaid diagram is stale'],
    ['E7 diagram markers missing', swap(IDENTITY, '<!-- erd:diagram:start -->', ''), [], 1,
        'identity.md: no <!-- erd:diagram:start --> … <!-- erd:diagram:end --> block'],
    ['E7 write regenerates a stale diagram', swap(LEDGER, '| teacher_id | uuid | no | users.id |', '| teacher_id | uuid | yes | users.id |'), ['--write'], 0,
        'erd: tables 5 (modules 2)'],
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
    $ok = $code === $expect && str_contains($out, $fragment);
    // --write must leave a tree that then passes without --write.
    if ($ok && in_array('--write', $args, true)) {
        [$code2, $out2] = run_checker($php, $checker, $dir, []);
        if ($code2 !== 0) {
            $ok = false;
            $out .= "\n(after --write) exit {$code2}: {$out2}";
        }
    }
    if ($ok) {
        $passed++;

        continue;
    }
    $failures[] = "✗ {$name}: expected exit {$expect} and «{$fragment}», got exit {$code}\n    ".str_replace("\n", "\n    ", $out);
}

echo 'erd tests: '.$passed.'/'.count($cases)." as expected\n";
foreach ($failures as $f) {
    echo "{$f}\n";
}
exit($failures === [] ? 0 : 1);
