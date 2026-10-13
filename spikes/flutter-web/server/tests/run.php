<?php

declare(strict_types=1);

/*
 * Self-tests for the WP-0.5 Flutter web spike server (spike code, not product code).
 *
 *   php spikes/flutter-web/server/tests/run.php [filter]
 *
 * Case names start with `spike` and cite the requirement or NFR row the spike probes. spikes/ is
 * outside check-traceability.php's scan on purpose: these cases are not WP-1.6 coverage.
 * Fixture keys are obviously fake; no network calls are made.
 */

use Spike\FlutterWeb\App;
use Spike\FlutterWeb\Config;
use Spike\FlutterWeb\Http\Request;
use Spike\FlutterWeb\Http\Response;
use Spike\FlutterWeb\Jwt;
use Spike\FlutterWeb\PayApp;

require dirname(__DIR__).'/src/autoload.php';

$filter = $argv[1] ?? '';
$cases = [];
$tmp = sys_get_temp_dir().'/sura-flutter-web-spike-tests-'.getmypid();
@mkdir($tmp, 0700, true);
register_shutdown_function(static function () use ($tmp): void {
    exec('rm -rf '.escapeshellarg($tmp));
});

function test(string $name, Closure $fn): void
{
    $GLOBALS['cases'][$name] = $fn;
}

function check(bool $ok, string $message): void
{
    if (! $ok) {
        throw new RuntimeException($message);
    }
}

function same(mixed $expected, mixed $actual, string $what): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($what.': expected '.var_export($expected, true).', got '.var_export($actual, true));
    }
}

const NOW = 1_793_000_000;
const KEY = 'fixture-run-key-0123456789abcdef';
const APP_ORIGIN = 'https://app.fixture.test';
const PAY_ORIGIN = 'https://pay.fixture.test';

/** A fake Flutter build: index.html with the base placeholder, a few files, precompressed twins. */
function fakeBuild(string $name): string
{
    $dir = $GLOBALS['tmp'].'/build-'.$name;
    if (is_dir($dir)) {
        return $dir;
    }
    mkdir($dir.'/canvaskit', 0700, true);
    file_put_contents($dir.'/index.html', "<!DOCTYPE html><html lang=\"ar\" dir=\"rtl\"><head><base href=\"/\">\n  <!--sura-meta-->\n</head><body></body></html>");
    file_put_contents($dir.'/main.dart.js', str_repeat('console.log(1);', 100));
    file_put_contents($dir.'/main.dart.js.br', 'BROTLI');
    file_put_contents($dir.'/main.dart.js.gz', 'GZIP');
    file_put_contents($dir.'/canvaskit/skwasm.wasm', "\0asm-fixture");
    file_put_contents($dir.'/sw.js', "const BUILD_ID = '__BUILD_ID__';");
    file_put_contents($dir.'/build-id.txt', $name.'-fixture-id');
    file_put_contents($GLOBALS['tmp'].'/secret.txt', 'outside the build');

    return $dir;
}

function config(array $overrides = []): Config
{
    return new Config(...array_merge([
        'builds' => ['wasm' => fakeBuild('wasm'), 'js' => fakeBuild('js')],
        'varDir' => $GLOBALS['tmp'].'/var-'.bin2hex(random_bytes(4)),
        'runKey' => KEY,
        'appOrigins' => [APP_ORIGIN, 'http://127.0.0.1:27200'],
        'payOrigin' => PAY_ORIGIN,
        'liveKitLocal' => ['url' => 'ws://127.0.0.1:7880', 'key' => 'devkey', 'secret' => 'fixture-dev-secret-not-real-0000000'],
        'liveKitCloud' => null,
        'apkDir' => $GLOBALS['tmp'],
    ], $overrides));
}

function app(?Config $config = null): App
{
    return new App($config ?? config(), static fn (): int => NOW);
}

function get(string $uri, array $headers = [], ?App $app = null): Response
{
    return ($app ?? app())->handle(Request::make('GET', $uri, $headers));
}

function post(string $uri, string $body, array $headers = [], ?App $app = null): Response
{
    return ($app ?? app())->handle(Request::make('POST', $uri, $headers + ['x-spike-key' => KEY, 'content-type' => 'application/json'], $body));
}

function header_of(Response $r, string $name): ?string
{
    foreach ($r->headers as $k => $v) {
        if (strcasecmp($k, $name) === 0) {
            return $v;
        }
    }

    return null;
}

function error(Response $r, int $status, string $code): array
{
    same($status, $r->status, 'status ('.$r->body.')');
    $json = json_decode($r->body, true, flags: JSON_THROW_ON_ERROR);
    same($code, $json['code'] ?? null, 'error code');
    check(($json['message_ar'] ?? '') !== '' && ($json['message_en'] ?? '') !== '', 'bilingual message');
    check(isset($json['request_id']), 'request_id');

    return $json;
}

function metrics(array $override = []): string
{
    return json_encode(array_merge([
        'variant' => 'wasm/iso', 'phase' => 'cold', 'run' => 'r-ab12cd', 'device' => 'pc-chrome',
        'network' => 'unthrottled', 'semantics' => true, 'marks' => ['fcp' => 80, 'interactive' => 1500],
        'wasm' => true, 'threads' => 'multi', 'isolated' => true, 'sw' => 'none', 'ua' => 'Mozilla/5.0',
        'vendor' => 'Google Inc.', 'transfer_kb' => 3000, 'encoded_kb' => 2900,
        'engine_files' => ['main.dart.wasm', 'skwasm.wasm'], 'screen' => '1280x800@1',
    ], $override));
}

// --- isolation headers (ADR-010 §3) ---------------------------------------------------------------

test('spike NFR-isolation iso variant sends COOP and COEP', function (): void {
    foreach (['/v/wasm/iso/', '/v/wasm/iso/main.dart.js', '/v/js/iso/canvaskit/skwasm.wasm'] as $uri) {
        $r = get($uri);
        same(200, $r->status, $uri);
        same('same-origin', header_of($r, 'Cross-Origin-Opener-Policy'), $uri.' COOP');
        same('require-corp', header_of($r, 'Cross-Origin-Embedder-Policy'), $uri.' COEP');
        same('same-origin', header_of($r, 'Cross-Origin-Resource-Policy'), $uri.' CORP');
    }
});

test('spike NFR-isolation plain variant sends no isolation headers', function (): void {
    foreach (['/v/wasm/plain/', '/v/js/plain/main.dart.js'] as $uri) {
        $r = get($uri);
        same(200, $r->status, $uri);
        same(null, header_of($r, 'Cross-Origin-Opener-Policy'), $uri.' COOP');
        same(null, header_of($r, 'Cross-Origin-Embedder-Policy'), $uri.' COEP');
    }
    error(get('/v/wasm/half/'), 404, 'not_found');
    error(get('/v/arm/iso/'), 404, 'not_found');
});

// --- CSP (ADR-010 §7, PRV-10) ----------------------------------------------------------------------

test('spike PRV-10 every response carries a CSP limited to our origins', function (): void {
    $cloud = config(['liveKitCloud' => ['url' => 'wss://fixture.livekit.cloud', 'key' => 'k', 'secret' => 's']]);
    foreach (['/v/wasm/iso/', '/v/js/plain/main.dart.js', '/start', '/auto'] as $uri) {
        $r = get($uri, app: app($cloud));
        $csp = (string) header_of($r, 'Content-Security-Policy');
        check(str_contains($csp, "default-src 'self'"), $uri.' default-src self: '.$csp);
        check(str_contains($csp, "object-src 'none'"), $uri.' object-src none');
        check(str_contains($csp, 'report-uri /csp-report'), $uri.' report-uri');
        check(! preg_match('/(^|\s)(\*|https:|http:|data:)(\s|;|$)/', preg_replace('/(img-src|font-src)[^;]*;/', '', $csp)), $uri.' no wildcard sources: '.$csp);
        check(! str_contains($csp, 'gstatic') && ! str_contains($csp, 'googleapis'), $uri.' no Google hosts');
    }
    $csp = (string) header_of(get('/v/wasm/iso/', app: app($cloud)), 'Content-Security-Policy');
    check(str_contains($csp, 'frame-src '.PAY_ORIGIN), 'frame-src = payment origin: '.$csp);
    check(str_contains($csp, 'wss://fixture.livekit.cloud') && str_contains($csp, 'ws://127.0.0.1:7880'), 'connect-src has the LiveKit servers');
    check(str_contains($csp, "'wasm-unsafe-eval'"), 'WebAssembly allowed');
    check(! str_contains($csp, "'unsafe-inline'") && ! str_contains($csp, "'unsafe-eval'"), 'no unsafe-inline/eval');
    $strict = (string) header_of(get('/v/wasm/iso/', app: app($cloud)), 'Content-Security-Policy-Report-Only');
    check($strict !== '' && ! str_contains($strict, 'blob:'), 'strict report-only candidate without blob:');
});

// --- build serving (D-22 measurements) -------------------------------------------------------------

test('spike NFR-perf index html gets the variant base href and run meta', function (): void {
    $r = get('/v/wasm/iso/r-ab12cd/?k='.KEY);
    same(200, $r->status, 'status');
    check(str_contains($r->body, '<base href="/v/wasm/iso/r-ab12cd/">'), 'base href: '.$r->body);
    check(str_contains($r->body, '<meta name="sura-variant" content="wasm/iso">'), 'variant meta');
    check(str_contains($r->body, '<meta name="sura-run" content="r-ab12cd">'), 'run meta');
    check(str_contains($r->body, '<meta name="sura-pay-origin" content="'.PAY_ORIGIN.'">'), 'pay origin meta');
    check(str_contains($r->body, '<meta name="sura-build-id" content="wasm-fixture-id">'), 'build id meta');
    check(str_contains($r->body, '<meta name="sura-force-wasm" content="0">'), 'force-wasm off');
    preg_match('/<meta name="sura-nonce" content="([A-Za-z0-9+\/=]+)">/', $r->body, $m);
    check(isset($m[1]) && str_contains((string) header_of($r, 'Content-Security-Policy'), "'nonce-".$m[1]."'"), 'nonce in meta and CSP');
    same('no-store', header_of($r, 'Cache-Control'), 'html not stored (fresh nonce)');
    check(str_contains(get('/v/wasmx/iso/')->body, '<meta name="sura-force-wasm" content="1">'), 'wasmx forces WebAssembly');
});

test('spike NFR-perf cold run path segment maps to the same build', function (): void {
    same(get('/v/js/plain/main.dart.js')->body, get('/v/js/plain/r-0f9e8d/main.dart.js')->body, 'same file');
    same("const BUILD_ID = 'js-fixture-id';", get('/v/js/plain/r-0f9e8d/sw.js')->body, 'sw build id');
    error(get('/v/js/plain/r-NOT_HEX/main.dart.js'), 404, 'not_found');
});

test('spike NFR-perf precompressed brotli served when accepted', function (): void {
    $br = get('/v/wasm/iso/main.dart.js', ['accept-encoding' => 'gzip, deflate, br']);
    same('BROTLI', $br->body, 'brotli body');
    same('br', header_of($br, 'Content-Encoding'), 'brotli encoding');
    same('Accept-Encoding', header_of($br, 'Vary'), 'vary');
    same('text/javascript; charset=utf-8', header_of($br, 'Content-Type'), 'type kept');
    same('GZIP', get('/v/wasm/iso/main.dart.js', ['accept-encoding' => 'gzip'])->body, 'gzip body');
    $raw = get('/v/wasm/iso/main.dart.js');
    same(null, header_of($raw, 'Content-Encoding'), 'identity');
    same('application/wasm', header_of(get('/v/wasm/iso/canvaskit/skwasm.wasm'), 'Content-Type'), 'wasm type');
});

test('spike NFR-perf unchanged file answers 304 to its etag', function (): void {
    $first = get('/v/js/iso/main.dart.js', ['accept-encoding' => 'br']);
    $etag = (string) header_of($first, 'ETag');
    check($etag !== '', 'etag');
    same('no-cache', header_of($first, 'Cache-Control'), 'revalidate every time');
    $again = get('/v/js/iso/main.dart.js', ['accept-encoding' => 'br', 'if-none-match' => $etag]);
    same(304, $again->status, 'not modified');
    same('', $again->body, 'empty body');
    same('require-corp', header_of($again, 'Cross-Origin-Embedder-Policy'), '304 keeps isolation headers');
});

test('spike PRV-10 fallback font requests are logged and answered 404', function (): void {
    $cfg = config();
    $r = get('/v/wasm/iso/fonts/fallback/notocoloremoji/v32/font.0.ttf', app: app($cfg));
    error($r, 404, 'not_found');
    $log = (string) @file_get_contents($cfg->varDir.'/fallback-fonts.jsonl');
    check(str_contains($log, 'notocoloremoji/v32/font.0.ttf'), 'fallback request logged: '.$log);
});

test('spike security path traversal is blocked', function (): void {
    foreach (['/v/wasm/iso/../secret.txt', '/v/wasm/iso/%2e%2e/secret.txt', '/v/wasm/iso/canvaskit/../../secret.txt'] as $uri) {
        check(get($uri)->status === 404, $uri);
    }
});

// --- POST endpoints ---------------------------------------------------------------------------------

test('spike security POST without run key is rejected', function (): void {
    foreach (['/metrics', '/shots', '/feedback', '/livekit-token'] as $uri) {
        error(app()->handle(Request::make('POST', $uri, ['content-type' => 'application/json'], '{}')), 403, 'bad_run_key');
        error(app()->handle(Request::make('POST', $uri, ['x-spike-key' => 'wrong'], '{}')), 403, 'bad_run_key');
    }
});

test('spike PRV-10 metrics endpoint rejects unknown fields', function (): void {
    $cfg = config();
    same(204, post('/metrics', metrics(), app: app($cfg))->status, 'valid metrics');
    $line = json_decode(trim((string) file_get_contents($cfg->varDir.'/metrics.jsonl')), true);
    same('wasm/iso', $line['variant'] ?? null, 'stored');
    same(NOW, $line['received_at'] ?? null, 'server time');
    error(post('/metrics', metrics(['child_name' => 'x']), app: app($cfg)), 422, 'validation_failed');
    error(post('/metrics', metrics(['device' => 'Sara iPad']), app: app($cfg)), 422, 'validation_failed');
});

test('spike security oversize metrics body is rejected', function (): void {
    error(post('/metrics', metrics(['ua' => str_repeat('x', 20_000)])), 413, 'too_large');
});

test('spike security shot must be a PNG', function (): void {
    $cfg = config();
    $png = "\x89PNG\r\n\x1a\n".str_repeat("\0", 64);
    $q = '?device=pc-chrome&variant=wasm/iso&diacritics=on&scale=1.0';
    same(204, post('/shots'.$q, $png, ['content-type' => 'image/png'], app($cfg))->status, 'png accepted');
    $files = glob($cfg->varDir.'/shots/*.png');
    same(1, count($files), 'one file');
    check(preg_match('#/\d{8}T\d{6}Z-pc-chrome-wasm-iso-on-1\.0-[0-9a-f]{6}\.png$#', $files[0]) === 1, 'server-chosen name: '.$files[0]);
    error(post('/shots'.$q, 'GIF89a', ['content-type' => 'image/png'], app($cfg)), 415, 'not_png');
    error(post('/shots?device=../../x&variant=wasm/iso&diacritics=on&scale=1.0', $png, ['content-type' => 'image/png'], app($cfg)), 422, 'validation_failed');
});

test('spike security feedback accepts only yes/no answers', function (): void {
    $ok = json_encode(['device' => 'ipad-safari', 'variant' => 'js/plain', 'answers' => ['q1' => 'yes', 'q9' => 'no']]);
    same(204, post('/feedback', $ok)->status, 'valid');
    error(post('/feedback', json_encode(['device' => 'ipad-safari', 'variant' => 'js/plain', 'answers' => ['q1' => 'maybe']])), 422, 'validation_failed');
    error(post('/feedback', json_encode(['device' => 'ipad-safari', 'variant' => 'js/plain', 'answers' => ['note' => 'yes']])), 422, 'validation_failed');
});

// --- LiveKit tokens (video under the isolation headers) ---------------------------------------------

test('spike video token bound to the test room and expires in 10 minutes', function (): void {
    $r = post('/livekit-token', json_encode(['target' => 'local', 'room' => 'wp05-isolation', 'identity' => 'tester-1a2b']));
    same(200, $r->status, 'status ('.$r->body.')');
    $body = json_decode($r->body, true);
    same('ws://127.0.0.1:7880', $body['url'], 'local url');
    $claims = Jwt::decode($body['token'], 'fixture-dev-secret-not-real-0000000', NOW);
    same('devkey', $claims['iss'], 'issuer');
    same('tester-1a2b', $claims['sub'], 'identity');
    same(NOW + 600, $claims['exp'], 'ttl');
    same('wp05-isolation', $claims['video']['room'], 'room');
    same(['camera', 'microphone'], $claims['video']['canPublishSources'], 'no screen share');
    check(empty($claims['video']['roomAdmin']), 'no admin');
    error(post('/livekit-token', json_encode(['target' => 'local', 'room' => 'other-room', 'identity' => 'tester-1a2b'])), 422, 'validation_failed');
    error(post('/livekit-token', json_encode(['target' => 'local', 'room' => 'wp05-isolation', 'identity' => 'Sara Ahmad'])), 422, 'validation_failed');
});

test('spike video cloud token needs the owner key file', function (): void {
    error(post('/livekit-token', json_encode(['target' => 'cloud', 'room' => 'wp05-isolation', 'identity' => 'tester-1a2b'])), 503, 'cloud_key_missing');
    $cloud = app(config(['liveKitCloud' => ['url' => 'wss://fixture.livekit.cloud', 'key' => 'fixture-cloud-key', 'secret' => 'fixture-cloud-secret-not-real-00000']]));
    $r = post('/livekit-token', json_encode(['target' => 'cloud', 'room' => 'wp05-isolation', 'identity' => 'tester-1a2b']), app: $cloud);
    same('wss://fixture.livekit.cloud', json_decode($r->body, true)['url'] ?? null, 'cloud url');
});

test('spike video key file parser reads only the LiveKit lines', function (): void {
    $file = $GLOBALS['tmp'].'/keys.env';
    file_put_contents($file, "# comment\nLIVEKIT_URL=wss://fixture.livekit.cloud\nLIVEKIT_API_KEY=\"fixture-key\"\nLIVEKIT_API_SECRET=fixture-secret\nZOOM_SDK_SECRET=must-not-be-read\n");
    same(['url' => 'wss://fixture.livekit.cloud', 'key' => 'fixture-key', 'secret' => 'fixture-secret'], Config::liveKitFromKeyFile($file), 'parsed');
    same(null, Config::liveKitFromKeyFile($GLOBALS['tmp'].'/missing.env'), 'missing file');
    file_put_contents($file, "LIVEKIT_URL=wss://x\nLIVEKIT_API_KEY=\n");
    same(null, Config::liveKitFromKeyFile($file), 'incomplete');
});

// --- fake payment origin -----------------------------------------------------------------------------

function pay(string $uri): Response
{
    return (new PayApp(config()))->handle(Request::make('GET', $uri, []));
}

test('spike payment return only goes back to the app origin', function (): void {
    $back = APP_ORIGIN.'/v/js/iso/?k='.KEY.'&tab=payment';
    $r = pay('/pay/done?status=paid&mode=redirect&return='.rawurlencode($back));
    same(303, $r->status, 'redirect');
    $loc = (string) header_of($r, 'Location');
    check(str_starts_with($loc, $back.'&status=paid&mode=redirect&ref='), 'back to the app: '.$loc);
    $hashed = pay('/pay/done?status=cancelled&mode=redirect&return='.rawurlencode($back.'#'));
    check(str_starts_with((string) header_of($hashed, 'Location'), $back.'&status=cancelled&mode=redirect&ref='), 'status before the fragment: '.header_of($hashed, 'Location'));
    error(pay('/pay/done?status=paid&mode=redirect&return='.rawurlencode('https://evil.test/')), 400, 'bad_return_url');
    error(pay('/pay/done?status=paid&mode=redirect&return='.rawurlencode(APP_ORIGIN.'.evil.test/')), 400, 'bad_return_url');
    error(pay('/pay/done?status=stolen&mode=redirect&return='.rawurlencode($back)), 422, 'validation_failed');
    same(200, pay('/pay?mode=redirect&origin='.rawurlencode(APP_ORIGIN).'&return='.rawurlencode($back))->status, 'payment page');
    error(pay('/popup?mode=popup&origin='.rawurlencode('https://evil.test')), 400, 'bad_return_url');
});

test('spike payment frame-corp mode sends the embedding headers', function (): void {
    $plain = pay('/frame?mode=frame&origin='.rawurlencode(APP_ORIGIN));
    same(200, $plain->status, 'frame');
    same(null, header_of($plain, 'Cross-Origin-Resource-Policy'), 'plain frame: no CORP');
    check(str_contains((string) header_of($plain, 'Content-Security-Policy'), 'frame-ancestors '.APP_ORIGIN), 'only our app may frame it');
    $corp = pay('/frame?mode=frame-corp&origin='.rawurlencode(APP_ORIGIN));
    same('cross-origin', header_of($corp, 'Cross-Origin-Resource-Policy'), 'CORP');
    same('require-corp', header_of($corp, 'Cross-Origin-Embedder-Policy'), 'COEP');
});

// --- runner --------------------------------------------------------------------------------------------

$failed = 0;
$ran = 0;
foreach ($cases as $name => $fn) {
    if ($filter !== '' && ! str_contains($name, $filter)) {
        continue;
    }
    $ran++;
    try {
        $fn();
        echo "  ok  {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "FAIL  {$name}\n      ".$e->getMessage()."\n";
    }
}
echo "\n{$ran} cases, ".($ran - $failed)." passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
