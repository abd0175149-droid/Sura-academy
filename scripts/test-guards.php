<?php

declare(strict_types=1);

/*
 * Regression tests for the Claude Code guard hooks (.claude/hooks/*.php), WP-0.2.
 *
 *   php scripts/test-guards.php
 *
 * Feeds the hooks the same JSON Claude Code sends (tool_name + tool_input) and asserts the exit
 * code: 2 = blocked/reported, 0 = allowed. Runs offline in throwaway directories: git remotes are
 * local bare repos, and post-hook probe files live under a temporary project root.
 *
 * Cases marked `gap` document current behaviour that a proposal in specs/WP-0.2/proposals.md
 * would change. When a hook edit changes that behaviour, the case fails on purpose: update its
 * `expect` and drop the `gap` tag in the same change.
 *
 * Secret-looking strings and append-only SQL are assembled at runtime so this file does not trip
 * the very guards it tests.
 */

$root = dirname(__DIR__);
// GUARD_HOOKS_DIR points at another copy of the hooks (used to prove this suite catches a broken hook).
$hooks = getenv('GUARD_HOOKS_DIR') ?: $root.'/.claude/hooks';
$pre = $hooks.'/pre_tool_guard.php';
$post = $hooks.'/post_tool_check.php';
$php = PHP_BINARY;

$tmp = sys_get_temp_dir().'/sura-guard-tests-'.getmypid();
register_shutdown_function(static function () use ($tmp): void {
    if (is_dir($tmp)) {
        exec('rm -rf '.escapeshellarg($tmp));
    }
});

/** Runs a hook with the given payload and project dir; returns [exitCode, stderr]. */
function run_hook(string $php, string $hook, array $payload, string $projectDir): array
{
    $proc = proc_open(
        [$php, $hook],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $projectDir,
        ['CLAUDE_PROJECT_DIR' => $projectDir, 'PATH' => (string) getenv('PATH'), 'HOME' => (string) getenv('HOME')],
    );
    fwrite($pipes[0], json_encode($payload, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    $err = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($proc), trim($err)];
}

function sh(string $cmd): void
{
    exec($cmd.' 2>&1', $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, "setup failed: {$cmd}\n".implode("\n", $out)."\n");
        exit(1);
    }
}

// ---------------------------------------------------------------- fixtures
// A git work tree whose `origin` already has `main`, one whose `origin` is empty, and a project
// root for post-hook probe files.
$withMain = "{$tmp}/repo-with-main";
$emptyRemote = "{$tmp}/repo-empty-remote";
$postRoot = "{$tmp}/post-root";
foreach (["{$tmp}/remote-main.git", "{$tmp}/remote-empty.git"] as $bare) {
    sh('git init -q --bare '.escapeshellarg($bare));
}
foreach ([[$withMain, "{$tmp}/remote-main.git", true], [$emptyRemote, "{$tmp}/remote-empty.git", false]] as [$dir, $remote, $seed]) {
    sh('git init -q -b main '.escapeshellarg($dir));
    sh('git -C '.escapeshellarg($dir).' -c user.email=t@t -c user.name=t commit -q --allow-empty -m init');
    sh('git -C '.escapeshellarg($dir).' remote add origin '.escapeshellarg($remote));
    if ($seed) {
        sh('git -C '.escapeshellarg($dir).' push -q origin main');
    }
}
mkdir($postRoot, 0777, true);

// ---------------------------------------------------------------- strings assembled at runtime
$awsKey = 'AKIA'.'IOSFODNN7EXAMPLE';
$privateKey = '-----BEGIN '.'RSA PRIVATE KEY-----';
$ghToken = 'gh'.'p_'.str_repeat('a1B2', 9);
$slackToken = 'xox'.'b-'.str_repeat('1', 12);
$liveKey = 'sk_'.'live_'.str_repeat('Z', 20);
$sql = static fn (string $verb, string $table): string => $verb.' '.$table;

$w = static fn (string $path, string $content = "x\n"): array => ['tool_name' => 'Write', 'tool_input' => ['file_path' => $path, 'content' => $content]];
$e = static fn (string $path, string $new): array => ['tool_name' => 'Edit', 'tool_input' => ['file_path' => $path, 'old_string' => 'a', 'new_string' => $new]];
$b = static fn (string $cmd): array => ['tool_name' => 'Bash', 'tool_input' => ['command' => $cmd]];

// ---------------------------------------------------------------- pre_tool_guard cases
// [name, payload, expect ('block'|'allow'), message fragment or null, project dir or null, gap id or null]
$preCases = [
    // Protected paths (Write/Edit)
    ['spec file, new (G1)', $w("{$root}/docs/01-product/spec/zz-probe.md"), 'block', 'spec files are read-only', null, null],
    ['spec file, edit (G1b)', $e("{$root}/docs/01-product/spec/00-priorities-and-baseline-decisions.md", 'x'), 'block', 'spec files are read-only', null, null],
    ['source file', $w("{$root}/docs/01-product/source/x.md"), 'block', 'spec files are read-only', null, null],
    ['constitution', $e("{$root}/docs/00-governance/constitution.md", 'x'), 'block', 'human-only', null, null],
    ['settings.json', $w("{$root}/.claude/settings.json", '{}'), 'block', 'human-only', null, null],
    ['hooks dir', $w("{$root}/.claude/hooks/x.php"), 'block', 'human-only', null, null],
    ['settings.local.json is allowed', $w("{$root}/.claude/settings.local.json", '{}'), 'allow', null, null, null],
    ['.env', $w("{$root}/apps/api/.env"), 'block', '.env', null, null],
    ['.env.production', $w("{$root}/apps/api/.env.production"), 'block', '.env', null, null],
    ['.env.example is allowed', $w("{$root}/apps/api/.env.example"), 'allow', null, null, null],
    ['generated client', $w("{$root}/packages/sura_api/lib/src/generated/x.dart"), 'block', 'generated code', null, null],
    ['relative path', $w('docs/01-product/spec/x.md'), 'block', 'spec files are read-only', null, null],
    ['decisions.md is allowed (append rows)', $e("{$root}/docs/00-governance/decisions.md", 'x'), 'allow', null, null, null],

    // Secrets in content
    ['AWS key (G4)', $w("{$root}/specs/x.txt", "id = {$awsKey}\n"), 'block', 'AWS access key', null, null],
    ['private key', $w("{$root}/specs/x.txt", $privateKey), 'block', 'private key', null, null],
    ['GitHub token', $w("{$root}/specs/x.txt", $ghToken), 'block', 'GitHub token', null, null],
    ['Slack token', $w("{$root}/specs/x.txt", $slackToken), 'block', 'Slack token', null, null],
    ['live secret key', $w("{$root}/specs/x.txt", $liveKey), 'block', 'live secret key', null, null],
    ['secret in Edit new_string', $e("{$root}/specs/x.txt", $awsKey), 'block', 'AWS access key', null, null],
    ['secret in MultiEdit', ['tool_name' => 'MultiEdit', 'tool_input' => ['file_path' => "{$root}/specs/x.txt", 'edits' => [['old_string' => 'a', 'new_string' => $ghToken]]]], 'block', 'GitHub token', null, null],
    ['ordinary text is allowed', $w("{$root}/specs/x.txt", "hello\n"), 'allow', null, null, null],

    // Append-only tables in content
    ['SQL write on the ledger table (G5)', $w("{$root}/specs/x.sql", $sql('UPDATE', 'credit_ledger_entries').' SET credits = 0;'), 'block', 'append-only', null, null],
    ['SQL delete on the audit table', $w("{$root}/specs/x.sql", $sql('DELETE FROM', 'audit_logs').';'), 'block', 'append-only', null, null],
    ['SQL TRUNCATE TABLE on the consents table', $w("{$root}/specs/x.sql", $sql('TRUNCATE TABLE', 'consents').';'), 'block', 'append-only', null, null],
    ['SQL with a quoted table name', $w("{$root}/specs/x.sql", $sql('update', '"wallet_entries"').' set x = 1;'), 'block', 'append-only', null, null],
    ['SQL on an ordinary table is allowed', $w("{$root}/specs/x.sql", $sql('UPDATE', 'users').' SET name = 1;'), 'allow', null, null, null],
    ['query builder code passes the pre-hook (checked after writing, see post cases)', $w("{$root}/apps/api/app/Modules/Ledger/Actions/X.php", "DB::table('credit_ledger_entries')->update(['x' => 1]);"), 'allow', null, null, null],

    // Bash: git push
    ['force push (G3)', $b('git push --force --dry-run origin HEAD:refs/heads/x'), 'block', 'force-push', null, null],
    ['force push -f (G3b)', $b('git push -f origin x'), 'block', 'force-push', null, null],
    ['force push +refspec (G3c)', $b('git push origin +HEAD:refs/heads/x'), 'block', 'force-push', null, null],
    ['force-with-lease', $b('git push --force-with-lease origin x'), 'block', 'force-push', null, null],
    ['push to main, explicit (G3d)', $b('git push origin HEAD:main'), 'block', 'never push to main', $withMain, null],
    ['push to main, implicit from main', $b('git push'), 'block', 'never push to main', $withMain, null],
    ['push to refs/heads/master', $b('git push origin x:refs/heads/master'), 'block', 'never push to main', $withMain, null],
    ['push feature branch is allowed', $b('git push -u origin wp/WP-9.9-x'), 'allow', null, $withMain, null],
    ['first push creating main on empty remote is allowed', $b('git push -u origin main'), 'allow', null, $emptyRemote, null],

    // Bash: other denials
    ['reset --hard', $b('git reset --hard HEAD~1'), 'block', 'reset --hard', null, null],
    ['filter-branch', $b('git filter-branch --tree-filter x'), 'block', 'history rewriting', null, null],
    ['rm -rf /', $b('rm -rf /'), 'block', 'recursive delete', null, null],
    ['rm -rf ~', $b('rm -rf ~'), 'block', 'recursive delete', null, null],
    ['rm -rf .', $b('rm -rf .'), 'block', 'recursive delete', null, null],
    ['rm -rf build dir is allowed', $b('rm -rf apps/family/build'), 'allow', null, null, null],
    ['migrate --force', $b('php artisan migrate --force'), 'block', 'CI/CD only', null, null],
    ['migrate is allowed', $b('php artisan migrate'), 'allow', null, null, null],
    ['migrate:fresh on production', $b('php artisan migrate:fresh --env=production'), 'block', 'destructive migration', null, null],
    ['cat .env', $b('cat apps/api/.env'), 'block', '.env', null, null],
    ['tail .env.local', $b('tail -n 5 apps/api/.env.local'), 'block', '.env', null, null],
    ['create .env from the template is allowed', $b('cp -n .env.example .env && php artisan key:generate'), 'allow', null, null, null],
    ['cat .env.example is allowed', $b('cat apps/api/.env.example'), 'allow', null, null, null],
    ['printenv', $b('printenv'), 'block', 'environment variables', null, null],
    ['env piped', $b('env | sort'), 'block', 'environment variables', null, null],
    ['drop database', $b('psql -c "DROP DATABASE sura"'), 'block', 'dropping databases', null, null],
    ['terraform apply', $b('terraform apply'), 'block', 'applied by the human', null, null],
    ['SQL via psql on the ledger table', $b('psql -c "'.$sql('DELETE FROM', 'credit_ledger_entries').'"'), 'block', 'append-only', null, null],
    ['ordinary command is allowed', $b('git status --short'), 'allow', null, null, null],
    ['reading the spec through a pipe is allowed', $b('grep -rn RBAC-01 docs/01-product/spec | head -5'), 'allow', null, null, null],
    ['reading the spec with stderr discarded is allowed', $b("sed -n '1,20p' docs/01-product/spec/00-priorities-and-baseline-decisions.md 2>/dev/null"), 'allow', null, null, null],
    ['git status on the spec dir is allowed', $b('git status --short docs/01-product'), 'allow', null, null, null],

    // Known gaps and false positives (proposals.md)
    ['Bash redirect into spec (G6)', $b('printf x > docs/01-product/spec/zz.md'), 'allow', null, null, 'P-2'],
    ['Bash sed -i on constitution', $b('sed -i s/a/b/ docs/00-governance/constitution.md'), 'allow', null, null, 'P-2'],
    ['Bash cp into hooks', $b('cp /tmp/x.php .claude/hooks/x.php'), 'allow', null, null, 'P-2'],
    ['grep ending in .env (blocked only by the env-dump rule matching ".env" at the end)', $b('grep KEY apps/api/.env'), 'block', 'environment variables', null, 'P-4'],
    ['grep .env with redirected output', $b('grep KEY apps/api/.env > /tmp/k'), 'allow', null, null, 'P-4'],
    ['python reads .env', $b("python3 -c \"print(open('apps/api/.env').read())\""), 'allow', null, null, 'P-4'],
    ['heredoc mentioning the env template copy (false positive)', $b("cat > ci.yml <<'EOF'\nrun: cp .env.example .env\nEOF"), 'block', 'reading .env', null, 'P-4'],
    ['heredoc quoting a force push (false positive)', $b("python3 - <<'EOF'\nnotes = 'git push --force was blocked'\nEOF"), 'block', 'force-push', null, 'P-4'],
    ['echo naming a SQL verb and a ledger table (false positive)', $b('echo "'.$sql('truncate', 'consents').' renamed"'), 'block', 'append-only', null, 'P-4'],
];

// ---------------------------------------------------------------- post_tool_check cases
// [name, relative path under the temp root, file content, expect, message fragment, gap]
$postCases = [
    ['EdgeInsets.only(left:) (G2)', 'apps/family/lib/a.dart', "const p = EdgeInsets.only(left: 8);\n", 'block', 'directional APIs', null],
    ['EdgeInsets.fromLTRB', 'apps/family/lib/a.dart', "const p = EdgeInsets.fromLTRB(1, 2, 3, 4);\n", 'block', 'directional APIs', null],
    ['Alignment.centerLeft', 'apps/family/lib/a.dart', "const a = Alignment.centerLeft;\n", 'block', 'directional APIs', null],
    ['TextAlign.right', 'apps/family/lib/a.dart', "const t = TextAlign.right;\n", 'block', 'directional APIs', null],
    ['rtl-ok suppression', 'apps/family/lib/a.dart', "const p = EdgeInsets.only(left: 8); // rtl-ok: test fixture\n", 'allow', null, null],
    ['EdgeInsetsDirectional is allowed', 'apps/family/lib/a.dart', "const p = EdgeInsetsDirectional.only(start: 8);\n", 'allow', null, null],
    ['generated dart is skipped', 'apps/family/lib/l10n/generated/a.dart', "const p = EdgeInsets.only(left: 8);\n", 'allow', null, null],
    ['hardcoded Text in lib', 'apps/family/lib/a.dart', "final w = Text('Hello');\n", 'block', 'hardcoded UI string', null],
    ['hardcoded Text in test is allowed', 'apps/family/test/a_test.dart', "final w = Text('Hello');\n", 'allow', null, null],
    ['hardcoded UTC offset in dart', 'apps/family/lib/a.dart', "const z = '+03:00';\n", 'block', 'IANA zones', null],
    ['analytics in sura_kids', 'packages/sura_kids/lib/a.dart', "import 'package:firebase_analytics/firebase_analytics.dart';\n", 'block', 'child surface', null],
    ['launchUrl in sura_kids', 'packages/sura_kids/lib/a.dart', "void f() { launchUrl(u); }\n", 'block', 'ParentalGate', null],
    // The hook runs `dart format` before checking, so the marker must sit on the launchUrl line of the formatted code.
    ['launchUrl inside the gate', 'packages/sura_kids/lib/a.dart', "void f() {\n  launchUrl(u); // parental-gate-ok\n}\n", 'allow', null, null],
    ['gate marker moved off the line by dart format', 'packages/sura_kids/lib/a.dart', "void f() { launchUrl(u); } // parental-gate-ok\n", 'block', 'ParentalGate', null],
    ['physical Tailwind in Blade', 'apps/api/resources/views/a.blade.php', "<div class=\"ml-4 text-left\"></div>\n", 'block', 'logical ones', null],
    ['logical Tailwind is allowed', 'apps/api/resources/views/a.blade.php', "<div class=\"ms-4 text-start\"></div>\n", 'allow', null, null],
    ['env() in app/', 'apps/api/app/Support/A.php', "<?php \$x = env('X');\n", 'block', 'env() outside config', null],
    ['float in Billing', 'apps/api/app/Modules/Billing/A.php', "<?php function f(float \$x) {}\n", 'block', 'float near money', null],
    ['ledger model mutation', 'apps/api/app/Modules/Ledger/A.php', "<?php CreditLedgerEntry::query()->where('id', 1)->update(['x' => 1]);\n", 'block', 'append-only model', null],
    ['clean php is allowed', 'apps/api/app/Modules/Ledger/A.php', "<?php \$x = 1;\n", 'allow', null, null],
    ['query builder write on the ledger table, multi-line chain (G5b)', 'apps/api/app/Modules/Ledger/A.php', "<?php\nDB::table('credit_ledger_entries')\n    ->where('id', 1)\n    ->update(['x' => 1]);\n", 'allow', null, 'P-3'],
];

// ---------------------------------------------------------------- run
$failures = [];
$passed = 0;
$gaps = [];

$check = static function (string $name, int $code, string $err, string $expect, ?string $fragment, ?string $gap) use (&$failures, &$passed, &$gaps): void {
    $blocked = $code === 2;
    $ok = ($expect === 'block') === $blocked && ($fragment === null || ! $blocked || str_contains($err, $fragment));
    if ($code !== 0 && $code !== 2) {
        $ok = false;
        $err = "unexpected exit code {$code}: {$err}";
    }
    if ($ok) {
        $passed++;
        if ($gap !== null) {
            $gaps[] = "{$gap}: {$name} (currently ".($blocked ? 'blocked' : 'allowed').')';
        }

        return;
    }
    $hint = $gap !== null ? " — behaviour changed for known gap {$gap}: update `expect` and drop the gap tag" : '';
    $failures[] = "✗ {$name}: expected {$expect}, got ".($blocked ? 'block' : 'allow')."{$hint}".($err !== '' ? "\n    {$err}" : '');
};

foreach ($preCases as [$name, $payload, $expect, $fragment, $dir, $gap]) {
    [$code, $err] = run_hook($php, $pre, $payload, $dir ?? $root);
    $check("pre: {$name}", $code, $err, $expect, $fragment, $gap);
}

foreach ($postCases as [$name, $rel, $content, $expect, $fragment, $gap]) {
    $file = "{$postRoot}/{$rel}";
    if (! is_dir(dirname($file))) {
        mkdir(dirname($file), 0777, true);
    }
    file_put_contents($file, $content);
    [$code, $err] = run_hook($php, $post, ['tool_name' => 'Write', 'tool_input' => ['file_path' => $file]], $postRoot);
    $check("post: {$name}", $code, $err, $expect, $fragment, $gap);
    unlink($file);
}

$total = count($preCases) + count($postCases);
echo "guard tests: {$passed}/{$total} as expected\n";
foreach ($gaps as $g) {
    echo "  ⚠ known {$g}\n";
}
foreach ($failures as $f) {
    echo "{$f}\n";
}
exit($failures === [] ? 0 : 1);
