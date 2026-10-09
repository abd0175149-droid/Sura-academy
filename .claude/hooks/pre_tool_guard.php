<?php
declare(strict_types=1);
// PreToolUse guard (Sura Academy). Exit 2 blocks the tool call; stderr is shown to Claude.

$in = json_decode((string) stream_get_contents(STDIN), true) ?: [];
$tool = (string) ($in['tool_name'] ?? '');
$ti = (array) ($in['tool_input'] ?? []);
$root = rtrim(str_replace('\\', '/', getenv('CLAUDE_PROJECT_DIR') ?: getcwd()), '/');

function block(string $msg): never
{
    fwrite(STDERR, "BLOCKED by .claude/hooks/pre_tool_guard.php: {$msg}\n");
    exit(2);
}

function relpath(string $path, string $root): string
{
    $p = str_replace('\\', '/', $path);
    if (str_starts_with($p, $root . '/')) {
        return substr($p, strlen($root) + 1);
    }
    return preg_replace('#^\./#', '', $p) ?? $p;
}

const APPEND_ONLY = 'credit_ledger_entries|audit_logs|wallet_entries|subscription_events|enrollment_history|consents|setting_versions|rule_versions|participant_events|delivery_logs';

if (in_array($tool, ['Edit', 'Write', 'MultiEdit', 'NotebookEdit'], true)) {
    $path = relpath((string) ($ti['file_path'] ?? $ti['notebook_path'] ?? ''), $root);

    $protected = [
        '#^docs/01-product/(spec|source)/#' => 'spec files are read-only. Propose a change request (docs/00-governance/change-control.md).',
        '#^docs/00-governance/constitution\.md$#' => 'the constitution is human-only.',
        '#^\.claude/(settings\.json|hooks/)#' => 'Claude Code settings and hooks are human-only. Propose the change in chat.',
        '#(^|/)\.env(\.(?!example$)[^/]+)?$#' => 'never write .env files (only .env.example).',
        '#^packages/sura_api/lib/src/generated/#' => 'generated code. Change contracts/openapi and run bash scripts/gen-api-client.sh.',
    ];
    foreach ($protected as $re => $why) {
        if (preg_match($re, $path)) {
            block("{$path} — {$why}");
        }
    }

    $texts = [];
    foreach (['content', 'new_string', 'new_source'] as $k) {
        if (isset($ti[$k])) {
            $texts[] = (string) $ti[$k];
        }
    }
    foreach ((array) ($ti['edits'] ?? []) as $e) {
        if (is_array($e) && isset($e['new_string'])) {
            $texts[] = (string) $e['new_string'];
        }
    }
    $content = implode("\n", $texts);

    $secrets = [
        '/AKIA[0-9A-Z]{16}/' => 'an AWS access key',
        '/-----BEGIN (RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/' => 'a private key',
        '/\bsk_live_[0-9a-zA-Z]{16,}/' => 'a live secret key',
        '/\bxox[baprs]-[0-9A-Za-z-]{10,}/' => 'a Slack token',
        '/\bgh[pousr]_[0-9A-Za-z]{30,}/' => 'a GitHub token',
    ];
    foreach ($secrets as $re => $what) {
        if (preg_match($re, $content)) {
            block("the content looks like {$what}. Secrets live in the secrets manager or a local .env, never in tracked files.");
        }
    }

    if (preg_match('/\b(UPDATE|DELETE\s+FROM|TRUNCATE(\s+TABLE)?)\s+"?(' . APPEND_ONLY . ')\b/i', $content, $m)) {
        block("'{$m[0]}' targets an append-only table (constitution art. 5). Write a REVERSAL entry or a new row instead.");
    }
}

if ($tool === 'Bash') {
    $cmd = (string) ($ti['command'] ?? '');
    $deny = [
        '/\bgit\s+reset\s+--hard\b/' => 'git reset --hard can destroy work; ask the user',
        '/\bgit\s+(filter-branch|filter-repo)\b/' => 'history rewriting is forbidden',
        '/\brm\s+-[a-zA-Z]*r[a-zA-Z]*\s+(\/|~|\$HOME|\.|\*)(\s|$)/' => 'dangerous recursive delete',
        '/\bartisan\s+(migrate:fresh|migrate:reset|db:wipe)\b.*--env=(production|staging)/' => 'destructive migration on a shared environment',
        '/\bartisan\s+migrate\b.*--force/' => 'migrate --force is for CI/CD only',
        '/\b(cat|less|more|head|tail|type|bat)\s+[^|;&]*\.env(\.(?!example)\w+)?(\s|$)/' => 'reading .env is forbidden',
        '/\bprintenv\b|\benv\s*$|\benv\s*\|/' => 'dumping environment variables can leak secrets',
        '/\bDROP\s+(DATABASE|SCHEMA)\b/i' => 'dropping databases or schemas is forbidden',
        '/\bterraform\s+(apply|destroy)\b/' => 'infrastructure changes are applied by the human',
    ];
    // git push: no force, and never to main/master in any syntax — except the very first push
    // that creates main on an empty remote (repository bootstrap), which is still behind the
    // "ask" permission in settings.json.
    if (preg_match('/\bgit\s+push\b(.*)$/s', $cmd, $pm)) {
        $rest = (string) preg_replace('/[;&|].*$/s', '', $pm[1]);
        if (preg_match('/(^|\s)(--force(-with-lease)?|--force-if-includes|-f)(\s|=|$)/', $rest) || preg_match('/(^|\s)\+\S+/', $rest)) {
            block("`{$cmd}` — force-push is forbidden.");
        }
        $args = array_values(array_filter(preg_split('/\s+/', trim($rest)) ?: [], fn ($t) => $t !== '' && $t[0] !== '-'));
        $remote = $args[0] ?? 'origin';
        $refs = array_slice($args, 1);
        $targetsMain = false;
        foreach ($refs as $r) {
            $dst = str_contains($r, ':') ? substr($r, strrpos($r, ':') + 1) : $r;
            if (preg_match('#^(refs/heads/)?(main|master)$#', $dst)) {
                $targetsMain = true;
            }
        }
        if (!$refs) {
            $current = trim((string) @shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --abbrev-ref HEAD 2>/dev/null'));
            $targetsMain = in_array($current, ['main', 'master'], true);
        }
        if ($targetsMain) {
            exec('git -C ' . escapeshellarg($root) . ' ls-remote --exit-code --heads ' . escapeshellarg($remote) . ' main master 2>/dev/null', $out, $code);
            if ($code !== 2) { // 2 = remote reachable and has no main/master yet; anything else blocks
                block("`{$cmd}` — never push to main; push the WP branch and open a PR. (Only the first push that creates main on an empty remote is allowed.)");
            }
        }
    }

    foreach ($deny as $re => $why) {
        if (preg_match($re, $cmd)) {
            block("`{$cmd}` — {$why}.");
        }
    }
    if (preg_match('/\b(UPDATE|DELETE\s+FROM|TRUNCATE)\s+"?(' . APPEND_ONLY . ')\b/i', $cmd)) {
        block("`{$cmd}` — append-only table (constitution art. 5).");
    }
}

exit(0);
