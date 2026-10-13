<?php
declare(strict_types=1);
/**
 * Builds the owner's roadmap page (Arabic, RTL) from the repository, so it can never disagree with it:
 *   statuses and the focus block  ← docs/03-plan/status.md
 *   phases, gates, owner track    ← docs/03-plan/master-plan.md
 *   manual test stations          ← docs/03-plan/test-stations.md
 *   R1 progress                   ← docs/03-plan/traceability.md
 *
 *   php scripts/build-roadmap.php --out <file.html>   write the page (then publish it to the roadmap artifact)
 *   php scripts/build-roadmap.php --check             validate the inputs only (verify.sh runs this)
 *   --root <dir>                                      read another tree
 *
 * Republishing the page is part of closing every WP (.claude/skills/sura-wp/SKILL.md, Step 6).
 */
$root = dirname(__DIR__);
$out = null;
$check = false;
for ($i = 1; $i < $argc; $i++) {
    match ($argv[$i]) {
        '--check' => $check = true,
        '--out' => $out = $argv[++$i] ?? '',
        '--root' => $root = rtrim($argv[++$i] ?? '', '/'),
        default => (function () use ($argv, $i): never {
            fwrite(STDERR, "unknown argument: {$argv[$i]}\n");
            exit(2);
        })(),
    };
}
if (!$check && ($out === null || $out === '')) {
    fwrite(STDERR, "usage: php scripts/build-roadmap.php --out <file.html> | --check [--root <dir>]\n");
    exit(2);
}
date_default_timezone_set('Asia/Amman');
$errors = [];

function rows_of(string $text): array
{
    $out = [];
    foreach (explode("\n", $text) as $line) {
        if (str_starts_with($line, '| ') && !preg_match('/^\|\s*-{3}/', $line)) {
            $out[] = array_map('trim', explode('|', trim(trim($line), '|')));
        }
    }
    return $out;
}

function read(string $root, string $rel, array &$errors): string
{
    if (!is_file("{$root}/{$rel}")) {
        $errors[] = "{$rel} is missing";
        return '';
    }
    return (string) file_get_contents("{$root}/{$rel}");
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Inline Markdown used in the plan files: `code`, **bold**, [text](url) and bare URLs. */
function md(string $s): string
{
    $codes = [];
    $s = preg_replace_callback('/`([^`]+)`/u', function ($m) use (&$codes) {
        $codes[] = '<code>' . e($m[1]) . '</code>';
        return "\x00" . (count($codes) - 1) . "\x00";
    }, $s);
    $s = e($s);
    $s = preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', $s);
    $s = preg_replace('/\[([^\]]+)\]\((https?:[^)\s]+)\)/u', '<a href="$2">$1</a>', $s);
    $s = preg_replace('/(?<![">])(https?:\/\/[^\s<]+)/u', '<a href="$1">$1</a>', $s);
    return preg_replace_callback('/\x00(\d+)\x00/', fn ($m) => $codes[(int) $m[1]], $s);
}

const STATUS_AR = [
    'Not started' => ['لم تبدأ', 'idle'],
    'Design' => ['تصميم ينتظر موافقتك', 'ask'],
    'In progress' => ['قيد التنفيذ', 'work'],
    'Blocked' => ['متوقفة', 'stop'],
    'Review' => ['تنتظر الدمج', 'review'],
    'Done' => ['منجزة', 'done'],
];

function status_key(string $s): string
{
    return str_starts_with($s, 'Blocked') ? 'Blocked' : $s;
}

// ---------- status.md ----------
$statusMd = read($root, 'docs/03-plan/status.md', $errors);
$wps = [];
$gateLog = [];
foreach (rows_of($statusMd) as $c) {
    if (count($c) >= 8 && preg_match('/^WP-\d+\.\d+$/', $c[0])) {
        if (!isset(STATUS_AR[status_key($c[5])])) {
            $errors[] = "status.md: {$c[0]} has unknown status «{$c[5]}»";
        }
        $wps[$c[0]] = ['name' => $c[1], 'phase' => $c[2], 'window' => $c[3], 'status' => $c[5], 'pr' => $c[6], 'updated' => $c[7]];
    } elseif (count($c) >= 4 && preg_match('/^(ب\d|الإطلاق)$/u', $c[0])) {
        $gateLog[$c[0]] = ['decision' => $c[2], 'date' => $c[3]];
    }
}
if (!$wps) {
    $errors[] = 'status.md: no work package rows';
}
$focus = preg_match('/<!-- focus:start -->\n(.*?)\n<!-- focus:end -->/su', $statusMd, $m)
    ? array_values(array_filter(explode("\n", $m[1]), 'strlen')) : [];
// The date/phase/week line is shown as numbers on the page already.
$focusShown = array_values(array_filter($focus, fn ($l) => !str_starts_with($l, '**التاريخ:**')));
if (!$focus) {
    $errors[] = 'status.md: no focus block';
}

// ---------- master-plan.md ----------
$planMd = read($root, 'docs/03-plan/master-plan.md', $errors);
$week1 = preg_match('/الأسبوع 1 = (\d{4}-\d{2}-\d{2})/u', $planMd, $m) ? new DateTimeImmutable($m[1]) : null;
if (!$week1) {
    $errors[] = 'master-plan.md: no «الأسبوع 1 = YYYY-MM-DD» line';
}
$phases = [];
$gates = [];
$ownerTrack = [];
foreach (rows_of($planMd) as $c) {
    if (count($c) === 4 && preg_match('/^(\d) (.+)$/u', $c[0], $p) && str_contains($c[1], 'phase-')) {
        $phases[$p[1]] = ['name' => $p[2], 'weeks' => $c[2], 'content' => $c[3]];
    } elseif (count($c) === 5 && preg_match('/^(ب\d|الإطلاق)/u', $c[2], $g)) {
        $gates[$g[1]] = ['title' => $c[2], 'week' => $c[3], 'date' => $c[4], 'track' => $c[0]];
    } elseif (count($c) === 2 && preg_match('/^(\d+)–(\d+)$/u', $c[0], $w)) {
        $ownerTrack[] = ['from' => (int) $w[1], 'to' => (int) $w[2], 'task' => $c[1]];
    }
}
foreach ([[$phases, 'phases table'], [$gates, 'gates table'], [$ownerTrack, 'owner track table']] as [$t, $label]) {
    if (!$t) {
        $errors[] = "master-plan.md: no {$label}";
    }
}

// ---------- traceability.md ----------
$r1 = 0;
$r1Done = 0;
foreach (rows_of(read($root, 'docs/03-plan/traceability.md', $errors)) as $c) {
    if (count($c) === 7 && preg_match('/^[A-Z]{2,5}-\d{2,3}$/', $c[0]) && str_starts_with($c[1], 'R1')) {
        $r1++;
        $r1Done += in_array($c[6], ['Implemented', 'Verified'], true) ? 1 : 0;
    }
}

// ---------- test-stations.md ----------
$stationsMd = read($root, 'docs/03-plan/test-stations.md', $errors);
$artifactUrl = preg_match('/^رابط الصفحة: (https:\/\/claude\.ai\/artifact\/[A-Za-z0-9]{10,})$/mu', $stationsMd, $m) ? $m[1] : null;
$stations = [];
foreach (array_slice(preg_split('/^## /mu', $stationsMd), 1) as $block) {
    $lines = explode("\n", trim($block));
    $s = ['title' => trim(array_shift($lines)), 'fields' => [], 'steps' => []];
    foreach ($lines as $line) {
        if (preg_match('/^- (الحزم|الأسبوع|ما ستراه|أين|اختبرتها|جاهزة منذ): (.+)$/u', $line, $f)) {
            $s['fields'][$f[1]] = trim($f[2]);
        } elseif (preg_match('/^\d+\. (.+)$/u', $line, $f)) {
            $s['steps'][] = $f[1];
        }
    }
    foreach (['الحزم', 'الأسبوع', 'ما ستراه', 'أين', 'اختبرتها'] as $k) {
        if (!isset($s['fields'][$k])) {
            $errors[] = "test-stations.md: «{$s['title']}» has no «- {$k}:» line";
        }
    }
    preg_match_all('/WP-\d+\.\d+/', $s['fields']['الحزم'] ?? '', $w);
    $s['wps'] = $w[0];
    if (!$s['wps']) {
        $errors[] = "test-stations.md: «{$s['title']}» names no work package";
    }
    foreach ($s['wps'] as $wp) {
        if (!isset($wps[$wp])) {
            $errors[] = "test-stations.md: «{$s['title']}» names {$wp}, which is not in status.md";
        }
    }
    // Ready when every WP is in Review/Done, or explicitly («- جاهزة منذ: …») when only part أ of a split WP is done.
    $ready = isset($s['fields']['جاهزة منذ'])
        || ($s['wps'] && !array_filter($s['wps'], fn ($wp) => !in_array($wps[$wp]['status'] ?? '', ['Review', 'Done'], true)));
    $s['state'] = ($s['fields']['اختبرتها'] ?? '—') !== '—' ? 'tested' : ($ready ? 'ready' : 'upcoming');
    if ($s['state'] !== 'upcoming' && !$s['steps']) {
        $errors[] = "test-stations.md: «{$s['title']}» is ready but has no numbered steps";
    }
    $stations[] = $s;
}
if (!$stations) {
    $errors[] = 'test-stations.md: no stations (## headings)';
}
if (!$artifactUrl && $check) { // the first build runs before the page has a URL
    $errors[] = 'test-stations.md: no «رابط الصفحة: https://claude.ai/…» line';
}

if ($errors) {
    foreach ($errors as $err) {
        fwrite(STDERR, "✖ {$err}\n");
    }
    exit(1);
}
if ($check) {
    $ready = count(array_filter($stations, fn ($s) => $s['state'] !== 'upcoming'));
    echo 'roadmap inputs OK · work packages: ' . count($wps) . ' · stations: ' . count($stations) . " ({$ready} ready)\n";
    exit(0);
}

// ---------- derived numbers ----------
$today = new DateTimeImmutable('today');
$week = (int) floor(($today->getTimestamp() - $week1->getTimestamp()) / 604800) + 1;
$week = max(0, $week);
$count = array_count_values(array_map(fn ($w) => status_key($w['status']), $wps));
$done = $count['Done'] ?? 0;
$total = count($wps);
$currentPhase = (string) min(array_map(fn ($w) => (int) $w['phase'], array_filter($wps, fn ($w) => $w['status'] !== 'Done')) ?: [7]);
$nextGate = null;
foreach ($gates as $key => $g) {
    if (($gateLog[$key]['decision'] ?? '—') === '—') {
        $nextGate = $key;
        break;
    }
}
$daysToGate = $nextGate ? (int) $today->diff(new DateTimeImmutable($gates[$nextGate]['date']))->format('%r%a') : null;
$ownerNow = array_filter($ownerTrack, fn ($t) => $week >= $t['from'] && $week <= $t['to']);
$repo = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' remote get-url origin 2>/dev/null'));
$repo = preg_replace('/\.git$/', '', preg_replace('#^git@github\.com:#', 'https://github.com/', $repo));
$commit = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --short HEAD 2>/dev/null'));

function pill(string $status): string
{
    [$label, $cls] = STATUS_AR[status_key($status)];
    return "<span class=\"pill {$cls}\">" . e($label) . '</span>';
}

function notes_link(string $repo, string $wp): string
{
    return $repo === '' ? '' : ' · <a href="' . e("{$repo}/blob/main/specs/{$wp}/notes.md") . '">خطوات التحقق اليدوي</a>';
}

// ---------- page ----------
ob_start();
?>
<title>خريطة تنفيذ سُرى</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Noto+Kufi+Arabic:wght@600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<style>
/* Layout: one reading column for a phone; summary first, then stations, then the full road by phase. */
:root {
  --bg: #f5f6f2; --surface: #ffffff; --fg: #1b2420; --muted: #5a665f; --line: #d8ded9;
  --accent: #1f6b57; --accent-soft: #e2efe9;
  --done: #1f6b57; --done-bg: #e2efe9; --work: #1d5fa8; --work-bg: #e3edf8; --review: #7a4fb0; --review-bg: #efe7f8;
  --ask: #8a5a00; --ask-bg: #fbf0d9; --stop: #a33b2b; --stop-bg: #f8e4e0; --idle: #6b746f; --idle-bg: #eceeeb;
  --font-display: "Noto Kufi Arabic", "IBM Plex Sans Arabic", Tahoma, sans-serif;
  --font-body: "IBM Plex Sans Arabic", Tahoma, "Segoe UI", sans-serif;
  --font-mono: "IBM Plex Mono", ui-monospace, Menlo, Consolas, monospace;
}
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) {
  --bg: #131816; --surface: #1a211e; --fg: #e3eae6; --muted: #98a59e; --line: #2c3632;
  --accent: #6cc3a6; --accent-soft: #1c3129;
  --done: #6cc3a6; --done-bg: #1c3129; --work: #7fb0ea; --work-bg: #17283b; --review: #c3a2ef; --review-bg: #2a2138;
  --ask: #e3b45a; --ask-bg: #33290f; --stop: #ef8b7a; --stop-bg: #3a1f1a; --idle: #9aa39e; --idle-bg: #232a27;
  color-scheme: dark; } }
:root[data-theme="dark"] {
  --bg: #131816; --surface: #1a211e; --fg: #e3eae6; --muted: #98a59e; --line: #2c3632;
  --accent: #6cc3a6; --accent-soft: #1c3129;
  --done: #6cc3a6; --done-bg: #1c3129; --work: #7fb0ea; --work-bg: #17283b; --review: #c3a2ef; --review-bg: #2a2138;
  --ask: #e3b45a; --ask-bg: #33290f; --stop: #ef8b7a; --stop-bg: #3a1f1a; --idle: #9aa39e; --idle-bg: #232a27;
  color-scheme: dark; }
body { background: var(--bg); color: var(--fg); font-family: var(--font-body); font-size: 16px; line-height: 1.75; }
.wrap { max-width: 780px; margin-inline: auto; padding-inline: 16px; padding-block: 24px 56px; display: grid; gap: 30px; }
h1 { font-family: var(--font-display); font-size: 25px; line-height: 1.4; margin: 0; text-wrap: balance; }
h2 { font-family: var(--font-display); font-size: 19px; margin: 0; text-wrap: balance; }
h3 { font-family: var(--font-display); font-size: 16px; margin: 0; }
p { margin: 0; }
section { display: grid; gap: 12px; min-width: 0; }
a { color: var(--accent); }
code { font-family: var(--font-mono); font-size: .86em; background: var(--accent-soft); padding: 1px 5px; border-radius: 4px; direction: ltr; unicode-bidi: isolate; overflow-wrap: anywhere; }
.meta { color: var(--muted); font-size: 13px; }
.now { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 16px; display: grid; gap: 14px; }
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
.stat { display: grid; gap: 2px; }
.stat b { font-family: var(--font-display); font-size: 22px; font-variant-numeric: tabular-nums; }
.stat span { color: var(--muted); font-size: 13px; }
.bar { height: 8px; border-radius: 99px; background: var(--idle-bg); overflow: hidden; display: flex; }
.bar i { display: block; height: 100%; }
.focus { margin: 0; padding-inline-start: 20px; display: grid; gap: 4px; font-size: 15px; }
.pill { display: inline-block; font-size: 12.5px; padding: 0 9px; border-radius: 99px; white-space: nowrap; line-height: 1.9; }
.pill.done { color: var(--done); background: var(--done-bg); } .pill.work { color: var(--work); background: var(--work-bg); }
.pill.review { color: var(--review); background: var(--review-bg); } .pill.ask { color: var(--ask); background: var(--ask-bg); }
.pill.stop { color: var(--stop); background: var(--stop-bg); } .pill.idle { color: var(--idle); background: var(--idle-bg); }
.station { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 14px 16px; display: grid; gap: 8px; }
.station.ready { border-color: var(--done); }
.station.tested { opacity: .85; }
.station header { display: flex; flex-wrap: wrap; gap: 6px 10px; align-items: baseline; justify-content: space-between; }
.station dl { margin: 0; display: grid; grid-template-columns: max-content 1fr; gap: 2px 12px; font-size: 15px; }
.station dt { color: var(--muted); } .station dd { margin: 0; min-width: 0; }
.station ol { margin: 0; padding-inline-start: 22px; display: grid; gap: 6px; font-size: 15px; }
.upcoming { display: grid; gap: 0; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; }
.upcoming > div { display: grid; grid-template-columns: 5.5em 1fr; gap: 10px; padding: 9px 14px; border-bottom: 1px solid var(--line); font-size: 15px; }
.upcoming > div:last-child { border-bottom: 0; }
.upcoming .wk { color: var(--muted); font-variant-numeric: tabular-nums; }
details.phase { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; }
details.phase summary { cursor: pointer; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 4px 12px; align-items: baseline; }
details.phase summary:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
details.phase .count { color: var(--muted); font-size: 13px; font-variant-numeric: tabular-nums; }
.wplist { border-top: 1px solid var(--line); }
.wp { display: grid; grid-template-columns: 1fr auto; gap: 2px 10px; padding: 9px 16px; border-bottom: 1px solid var(--line); font-size: 15px; }
.wp:last-child { border-bottom: 0; }
.wp .id { font-family: var(--font-mono); font-size: 13px; color: var(--muted); }
.wp .sub { grid-column: 1 / -1; color: var(--muted); font-size: 13px; }
.table-wrap { overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; }
table { border-collapse: collapse; width: 100%; font-size: 15px; }
th, td { text-align: start; padding: 8px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
tr:last-child td { border-bottom: 0; }
th { color: var(--muted); font-weight: 600; font-size: 13px; }
td.num { font-variant-numeric: tabular-nums; white-space: nowrap; }
.legend { display: flex; flex-wrap: wrap; gap: 6px; }
</style>

<div class="wrap" dir="rtl" lang="ar">
  <header style="display:grid;gap:4px">
    <h1>خريطة تنفيذ أكاديمية سُرى</h1>
    <p class="meta">آخر تحديث <?= e($today->format('Y-m-d')) ?> · من المستودع (<code><?= e($commit) ?></code>) · تتحدث هذه الصفحة عند إغلاق كل حزمة عمل.</p>
  </header>

  <section>
    <h2>أين نحن الآن</h2>
    <div class="now">
      <div class="stats">
        <div class="stat"><b>المرحلة <?= e($currentPhase) ?></b><span><?= e($phases[$currentPhase]['name'] ?? '') ?></span></div>
        <div class="stat"><b>الأسبوع <?= $week ?></b><span><?= $week === 0 ? 'التحضير، قبل ' . e($week1->format('Y-m-d')) : 'من خطة التنفيذ' ?></span></div>
        <div class="stat"><b><?= $done ?> / <?= $total ?></b><span>حزمة عمل منجزة</span></div>
        <div class="stat"><b><?= $r1Done ?> / <?= $r1 ?></b><span>متطلب R1 منفذ</span></div>
      </div>
      <div class="bar" role="img" aria-label="<?= e("{$done} منجزة من {$total}") ?>">
<?php foreach (['Done' => 'done', 'Review' => 'review', 'In progress' => 'work', 'Design' => 'ask', 'Blocked' => 'stop'] as $k => $cls): if (!empty($count[$k])): ?>
        <i style="width: <?= round(100 * $count[$k] / $total, 2) ?>%; background: var(--<?= $cls ?>)"></i>
<?php endif; endforeach; ?>
      </div>
<?php if ($nextGate): ?>
      <p><b>البوابة القادمة:</b> <?= e($gates[$nextGate]['title']) ?>، الأسبوع <?= e($gates[$nextGate]['week']) ?> (<?= e($gates[$nextGate]['date']) ?>)<?= $daysToGate !== null && $daysToGate >= 0 ? '، بعد ' . $daysToGate . ' يوماً' : '' ?>.</p>
<?php endif; ?>
      <ul class="focus">
<?php foreach ($focusShown as $line): ?>
        <li><?= md($line) ?></li>
<?php endforeach; ?>
      </ul>
    </div>
  </section>

<?php if ($ownerNow): ?>
  <section>
    <h2>مطلوب منك في هذه الفترة</h2>
    <ul class="focus">
<?php foreach ($ownerNow as $t): ?>
      <li><?= md($t['task']) ?> <span class="meta">(الأسابيع <?= $t['from'] ?>–<?= $t['to'] ?>)</span></li>
<?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

  <section>
    <h2>محطات الاختبار اليدوي</h2>
    <p>في كل محطة شيء تراه بعينك وتجربه بنفسك. المحطة تصبح «جاهزة» حين تنتهي حزمها، وبعد أن تجربها أسجّل التاريخ هنا.</p>
<?php foreach ($stations as $s): if ($s['state'] === 'upcoming') { continue; } ?>
    <article class="station <?= $s['state'] ?>">
      <header>
        <h3><?= e($s['title']) ?></h3>
        <?= $s['state'] === 'tested' ? '<span class="pill done">اختبرتها ' . e($s['fields']['اختبرتها']) . '</span>' : '<span class="pill review">جاهزة لتجربتك</span>' ?>
      </header>
      <dl>
        <dt>ما ستراه</dt><dd><?= md($s['fields']['ما ستراه']) ?></dd>
        <dt>أين</dt><dd><?= md($s['fields']['أين']) ?></dd>
        <dt>الحزم</dt><dd><?php foreach ($s['wps'] as $wp) { echo e($wp) . ' ' . pill($wps[$wp]['status']) . notes_link($repo, $wp) . ' '; } ?></dd>
      </dl>
      <ol>
<?php foreach ($s['steps'] as $step): ?>
        <li><?= md($step) ?></li>
<?php endforeach; ?>
      </ol>
    </article>
<?php endforeach; ?>
    <h3>المحطات القادمة</h3>
    <div class="upcoming">
<?php foreach ($stations as $s): if ($s['state'] !== 'upcoming') { continue; } ?>
      <div><span class="wk">أسبوع <?= e($s['fields']['الأسبوع']) ?></span><span><b><?= e($s['title']) ?>:</b> <?= md($s['fields']['ما ستراه']) ?> <span class="meta">(<?= e(implode('، ', $s['wps'])) ?>)</span></span></div>
<?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>المراحل وحزم العمل</h2>
    <div class="legend"><?php foreach (array_keys(STATUS_AR) as $k) { echo pill($k); } ?></div>
<?php foreach ($phases as $n => $p):
    $list = array_filter($wps, fn ($w) => $w['phase'] === (string) $n);
    $pdone = count(array_filter($list, fn ($w) => $w['status'] === 'Done')); ?>
    <details class="phase"<?= (string) $n === $currentPhase ? ' open' : '' ?>>
      <summary><h3>المرحلة <?= e((string) $n) ?>: <?= e($p['name']) ?></h3><span class="count">الأسابيع <?= e($p['weeks']) ?> · <?= $pdone ?> / <?= count($list) ?> منجزة</span></summary>
      <div class="wplist">
<?php foreach ($list as $id => $w): ?>
        <div class="wp"><span><span class="id"><?= e($id) ?></span> <?= e($w['name']) ?></span><?= pill($w['status']) ?>
          <span class="sub">النافذة <?= e($w['window']) ?><?= $w['pr'] !== '—' ? ' · ' . md($w['pr']) : '' ?><?= in_array($w['status'], ['Review', 'Done'], true) ? notes_link($repo, $id) : '' ?></span></div>
<?php endforeach; ?>
      </div>
    </details>
<?php endforeach; ?>
  </section>

  <section>
    <h2>البوابات</h2>
    <p>عند كل بوابة تقرر أنت: نكمل، أو نكمل بتعديل، أو نتوقف.</p>
    <div class="table-wrap"><table>
      <thead><tr><th>البوابة</th><th>الأسبوع</th><th>التاريخ</th><th>القرار</th></tr></thead>
      <tbody>
<?php foreach ($gates as $key => $g): ?>
        <tr><td><?= e($g['title']) ?></td><td class="num"><?= e($g['week']) ?></td><td class="num"><?= e($g['date']) ?></td><td><?= e(($gateLog[$key]['decision'] ?? '—') === '—' ? 'لم يُتخذ بعد' : $gateLog[$key]['decision']) ?></td></tr>
<?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
</div>
<?php
file_put_contents($out, ob_get_clean());
echo "roadmap written to {$out} · publish it to " . ($artifactUrl ?? 'a new artifact, then add «رابط الصفحة: …» to test-stations.md') . "\n";
