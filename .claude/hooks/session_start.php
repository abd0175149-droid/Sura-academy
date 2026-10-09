<?php
// SessionStart: inject the current focus block from docs/03-plan/status.md into context.
$root = getenv('CLAUDE_PROJECT_DIR') ?: getcwd();
$file = $root . '/docs/03-plan/status.md';
if (!is_file($file)) {
    exit(0);
}
$s = (string) file_get_contents($file);
if (preg_match('/<!-- focus:start -->(.*?)<!-- focus:end -->/s', $s, $m)) {
    $lines = array_slice(explode("\n", str_replace("\r\n", "\n", trim($m[1]))), 0, 12);
    echo "## Sura Academy — current focus (docs/03-plan/status.md)\n";
    echo implode("\n", $lines), "\n";
    echo "Work only through /sura-wp <WP-id>. /sura-status shows the full picture.\n";
}
exit(0);
