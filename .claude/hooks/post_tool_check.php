<?php
declare(strict_types=1);
// PostToolUse (Sura Academy): best-effort formatting + project rules that lint cannot express.
// Exit 2 => the findings are shown to Claude, which must fix them before continuing.

$in = json_decode((string) stream_get_contents(STDIN), true) ?: [];
$ti = (array) ($in['tool_input'] ?? []);
$root = rtrim(str_replace('\\', '/', getenv('CLAUDE_PROJECT_DIR') ?: getcwd()), '/');
$abs = str_replace('\\', '/', (string) ($ti['file_path'] ?? ''));
if ($abs === '' || !is_file($abs)) {
    exit(0);
}
$rel = str_starts_with($abs, $root . '/') ? substr($abs, strlen($root) + 1) : $abs;
$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$isGenerated = str_contains($rel, '/generated/') || str_ends_with($rel, '.g.dart') || str_ends_with($rel, '.freezed.dart');

// 1) Formatting (silent, never blocks).
if ($ext === 'dart' && !$isGenerated) {
    @exec('dart format ' . escapeshellarg($abs) . ' 2>&1');
}
if ($ext === 'php' && str_starts_with($rel, 'apps/api/') && is_file($root . '/apps/api/vendor/bin/pint')) {
    @exec('cd ' . escapeshellarg($root . '/apps/api') . ' && vendor/bin/pint ' . escapeshellarg($abs) . ' 2>&1');
}

// 2) Project checks.
$lines = explode("\n", str_replace("\r\n", "\n", (string) file_get_contents($abs)));
$problems = [];
$check = static function (string $re, string $msg, string $suppress) use ($lines, &$problems, $rel): void {
    foreach ($lines as $i => $line) {
        if (preg_match($re, $line) && !str_contains($line, $suppress)) {
            $problems[] = sprintf('%s:%d: %s  ->  %s', $rel, $i + 1, trim($line), $msg);
        }
    }
};
$isTest = (bool) preg_match('#(^|/)(test|tests|integration_test|spikes)/#', $rel);

if ($ext === 'dart' && !$isGenerated) {
    $rtl = 'use directional APIs (EdgeInsetsDirectional, AlignmentDirectional, PositionedDirectional, TextAlign.start/end, BorderRadiusDirectional). Suppress only with // rtl-ok: <reason>';
    $check('/EdgeInsets\.only\([^)]*\b(left|right)\s*:/', $rtl, 'rtl-ok');
    $check('/EdgeInsets\.fromLTRB\(/', $rtl, 'rtl-ok');
    $check('/\bAlignment\.(centerLeft|centerRight|topLeft|topRight|bottomLeft|bottomRight)\b/', $rtl, 'rtl-ok');
    $check('/\bPositioned\([^)]*\b(left|right)\s*:/', $rtl, 'rtl-ok');
    $check('/\bTextAlign\.(left|right)\b/', $rtl, 'rtl-ok');
    $check('/BorderRadius\.only\([^)]*\b(topLeft|topRight|bottomLeft|bottomRight)\s*:/', $rtl, 'rtl-ok');
    if (!$isTest) {
        $check('/\bText\(\s*[\'"]/', 'hardcoded UI string: use AppLocalizations (ARB). Suppress only with // l10n-ok: <reason>', 'l10n-ok');
        $check('/[\'"][+-]0?\d:00[\'"]/', 'hardcoded UTC offset: use IANA zones (constitution art. 6). Suppress with // tz-ok: <reason>', 'tz-ok');
    }
    if (preg_match('#^(packages/sura_kids|apps/kids|apps/family/lib/features/child)/#', $rel)) {
        $check('/import\s+[\'"]package:(posthog|firebase_analytics|google_mobile_ads|facebook|mixpanel|amplitude|appsflyer|adjust|segment)/', 'tracking/ads SDK in a child surface is forbidden (constitution art. 8)', '@@never@@');
        $check('/\blaunchUrl(String)?\(/', 'external links in child surfaces must go through ParentalGate. Suppress only inside the gate with // parental-gate-ok', 'parental-gate-ok');
    }
}

if (str_starts_with($rel, 'apps/api/resources/') && preg_match('/\.(php|html|css|js)$/', $rel)) {
    $check('/(?<![\w-])(ml|mr|pl|pr)-(\d|px|auto|\[)|(?<![\w-])-?(left|right)-(\d|px|auto|full|\[)|(?<![\w-])text-(left|right)\b|(?<![\w-])rounded-(l|r|tl|tr|bl|br)(\b|-)|(?<![\w-])border-(l|r)(\b|-)|(?<![\w-])float-(left|right)\b/', 'physical Tailwind utility: use logical ones (ms-/me-/ps-/pe-/start-/end-/text-start/text-end/rounded-s/e/border-s/e/float-start/end). Suppress with rtl-ok', 'rtl-ok');
}

if ($ext === 'php' && str_starts_with($rel, 'apps/api/app/')) {
    $check('/(?<![\w>:])env\(/', 'env() outside config/: read config() instead (config caching breaks env())', 'env-ok');
    if (!$isTest) {
        $check('/[\'"][+-]0?\d:00[\'"]/', 'hardcoded UTC offset: use IANA zones (constitution art. 6). Suppress with tz-ok', 'tz-ok');
    }
    if (preg_match('#^apps/api/app/Modules/(Ledger|Billing|Teachers)/#', $rel)) {
        $check('/(CreditLedgerEntry|WalletEntry|AuditLog|SubscriptionEvent)::[^;]*->(update|delete|forceDelete)\(|\$(entry|ledgerEntry|walletEntry|auditLog)->(update|delete|forceDelete)\(/', 'append-only model mutated: write a REVERSAL or a new entry (constitution art. 5)', '@@never@@');
        $check('/\bfloat\b|\(float\)|floatval\(/', 'float near money or credits: use integer minor units / Money (constitution art. 5). Suppress with float-ok: <reason>', 'float-ok');
    }
}

// P-3: query-builder writes to append-only tables, matched across lines (Pint splits chains).
if ($ext === 'php' && preg_match('#^apps/api/(app|database)/#', $rel)) {
    $appendOnly = 'credit_ledger_entries|audit_logs|wallet_entries|subscription_events|enrollment_history|consents|setting_versions|rule_versions|participant_events|delivery_logs';
    $src = implode("\n", $lines);
    if (preg_match_all('/DB::table\(\s*[\'"](' . $appendOnly . ')[\'"]\s*\)(?:(?!;).)*?->(update|delete|truncate|upsert|updateOrInsert|increment|decrement)\(/s', $src, $mm, PREG_OFFSET_CAPTURE)) {
        foreach ($mm[0] as [$hit, $offset]) {
            $problems[] = sprintf('%s:%d: DB::table(\'%s\')->…  ->  append-only table mutated through the query builder: write a REVERSAL or a new row (constitution art. 5)', $rel, substr_count($src, "\n", 0, $offset) + 1, $mm[1][0][0]);
        }
    }
}

if ($problems) {
    fwrite(STDERR, "Project checks failed. Fix these before continuing:\n" . implode("\n", array_slice($problems, 0, 30)) . "\n");
    exit(2);
}
exit(0);
