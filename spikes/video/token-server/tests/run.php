<?php

declare(strict_types=1);

/*
 * Self-tests for the WP-0.4 video spike token server (spike code, not product code).
 *
 *   php spikes/video/token-server/tests/run.php [filter]
 *
 * Case names start with `spike` and cite the requirement the spike probes for feasibility.
 * spikes/ is outside check-traceability.php's test scan on purpose: these cases are not
 * WP-3.x coverage. Fixture keys are obviously fake; no network calls are made.
 */

use Spike\Video\App;
use Spike\Video\AttendanceProbe;
use Spike\Video\Config;
use Spike\Video\EventLog;
use Spike\Video\Http\Request;
use Spike\Video\Http\Response;
use Spike\Video\Jwt;
use Spike\Video\RoomLocks;

require dirname(__DIR__).'/src/autoload.php';

$filter = $argv[1] ?? '';
$cases = [];
$tmp = sys_get_temp_dir().'/sura-video-spike-tests-'.getmypid();
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

const NOW = 1_793_000_000; // fixed clock, 2026-10-26T…Z
const LK_SECRET = 'fixture-livekit-secret-not-real-000000';
const ZOOM_SECRET = 'fixture-zoom-sdk-secret-not-real-0000';
const TICKET_SECRET = 'fixture-ticket-secret-not-real-000000';

function config(array $overrides = []): Config
{
    return new Config(...array_merge([
        'liveKitUrl' => 'wss://fixture.livekit.cloud',
        'liveKitApiKey' => 'fixture-lk-key',
        'liveKitApiSecret' => LK_SECRET,
        'zoomSdkKey' => 'fixture-zoom-key',
        'zoomSdkSecret' => ZOOM_SECRET,
        'accessCode' => 'fixture-code',
        'ticketSecret' => TICKET_SECRET,
        'windowOpensAt' => null,
        'windowClosesAt' => null,
    ], $overrides));
}

function app(?Config $config = null, ?RoomLocks $locks = null, int $now = NOW): App
{
    $locks ??= new RoomLocks($GLOBALS['tmp'].'/locks-'.bin2hex(random_bytes(4)).'.json');

    $events = new EventLog($GLOBALS['tmp'].'/events-'.bin2hex(random_bytes(4)).'.jsonl');

    return new App($config ?? config(), $locks, $events, static fn (): int => $now);
}

/** POST /token with sensible defaults; returns the Response. */
function token(array $body = [], ?App $app = null): Response
{
    $body = array_merge([
        'provider' => 'livekit',
        'role' => 'student',
        'identity' => 'student-1',
        'room' => 'spike-room',
        'access_code' => 'fixture-code',
    ], $body);

    return ($app ?? app())->handle(new Request('POST', '/token', ['content-type' => 'application/json'], json_encode($body)));
}

function ok(Response $r): array
{
    same(200, $r->status, 'status ('.$r->body.')');

    return json_decode($r->body, true, flags: JSON_THROW_ON_ERROR);
}

function error(Response $r, int $status, string $code): array
{
    same($status, $r->status, 'status ('.$r->body.')');
    $json = json_decode($r->body, true, flags: JSON_THROW_ON_ERROR);
    same($code, $json['code'] ?? null, 'error code');

    return $json;
}

/** Decodes a LiveKit token issued at NOW and returns its claims. */
function lk(array $issued, int $at = NOW): array
{
    return Jwt::decode($issued['token'], LK_SECRET, $at);
}

// --- JWT -------------------------------------------------------------------------------------

test('spike jwt round trip and tampered token rejected', function (): void {
    $jwt = Jwt::encode(['sub' => 'a', 'exp' => NOW + 10], 'k');
    same('a', Jwt::decode($jwt, 'k', NOW)['sub'], 'sub');
    [$h, , $s] = explode('.', $jwt);
    $forged = $h.'.'.rtrim(strtr(base64_encode('{"sub":"b","exp":9999999999}'), '+/', '-_'), '=').'.'.$s;
    foreach ([[$forged, 'k'], [$jwt, 'other-key']] as [$candidate, $key]) {
        try {
            Jwt::decode($candidate, $key, NOW);
            throw new LogicException('accepted a bad token');
        } catch (InvalidArgumentException) {
        }
    }
});

// --- CLS-03 short-lived, bound tokens --------------------------------------------------------

test('spike CLS-03 livekit token expires within ttl', function (): void {
    $issued = ok(token());
    $claims = lk($issued);
    same(600, $claims['exp'] - $claims['nbf'], 'ttl seconds');
    same(gmdate('Y-m-d\TH:i:s\Z', NOW + 600), $issued['expires_at'], 'expires_at is ISO-8601 UTC');
    try {
        lk($issued, NOW + 601);
        throw new LogicException('expired token accepted');
    } catch (InvalidArgumentException) {
    }
});

test('spike CLS-03 zoom token uses the provider minimum ttl', function (): void {
    $issued = ok(token(['provider' => 'zoom', 'role' => 'teacher', 'identity' => 'teacher-1']));
    $claims = Jwt::decode($issued['token'], ZOOM_SECRET, NOW);
    same(1800, $claims['exp'] - $claims['iat'], 'zoom exp - iat (provider minimum)');
    same('fixture-zoom-key', $claims['app_key'], 'app_key');
    same(1, $claims['version'], 'version');
    same('spike-room', $claims['tpc'], 'tpc');
    same('teacher-1', $claims['user_key'], 'user_key');
    same(1, $claims['role_type'], 'teacher is host');
    same('DE,NL', $claims['geo_regions'], 'EU data centres');
    same('spike-room', $issued['session_name'], 'session_name');
    $student = Jwt::decode(ok(token(['provider' => 'zoom']))['token'], ZOOM_SECRET, NOW);
    same(0, $student['role_type'], 'student is participant');
});

test('spike CLS-03 token bound to one room and identity', function (): void {
    $issued = ok(token(['room' => 'room-a', 'identity' => 'student-2']));
    $claims = lk($issued);
    same('student-2', $claims['sub'], 'sub');
    same('fixture-lk-key', $claims['iss'], 'iss');
    same('room-a', $claims['video']['room'], 'room');
    same(true, $claims['video']['roomJoin'], 'roomJoin');
    same('wss://fixture.livekit.cloud', $issued['server_url'], 'server_url');
    same(['role' => 'student'], json_decode($claims['metadata'], true), 'metadata');
});

// --- CLS-04 roles inside the room ------------------------------------------------------------

test('spike CLS-04 student cannot publish screen share', function (): void {
    same(['camera', 'microphone'], lk(ok(token()))['video']['canPublishSources'], 'student sources');
    same(
        ['camera', 'microphone', 'screen_share', 'screen_share_audio'],
        lk(ok(token(['role' => 'teacher', 'identity' => 'teacher-1'])))['video']['canPublishSources'],
        'teacher sources',
    );
});

test('spike CLS-04 client tokens carry no room admin', function (): void {
    foreach (['teacher', 'student', 'substitute', 'supervisor', 'bot'] as $role) {
        $video = lk(ok(token(['role' => $role, 'identity' => $role.'-1'])))['video'];
        check(! ($video['roomAdmin'] ?? false), "$role token must not be room admin");
        check(! ($video['roomRecord'] ?? false), "$role token must not record");
    }
});

test('spike CLS-04 admin ticket only for teacher and substitute', function (): void {
    foreach (['teacher' => true, 'substitute' => true, 'student' => false, 'supervisor' => false, 'bot' => false] as $role => $host) {
        $issued = ok(token(['role' => $role, 'identity' => $role.'-1']));
        same($host, isset($issued['admin_ticket']), "$role admin ticket");
        if ($host) {
            $ticket = Jwt::decode($issued['admin_ticket'], TICKET_SECRET, NOW);
            same(['spike-admin', 'spike-room', 'livekit', $role], [$ticket['aud'], $ticket['room'], $ticket['provider'], $ticket['role']], 'ticket claims');
        }
    }
});

test('spike CLS-04 locked room refuses new tokens', function (): void {
    $locks = new RoomLocks($GLOBALS['tmp'].'/locks-lock.json');
    $app = app(locks: $locks);
    ok(token(app: $app));
    $locks->lock('spike-room');
    error(token(app: $app), 409, 'room_locked');
    ok(token(['room' => 'other-room'], $app));
    $locks->unlock('spike-room');
    ok(token(app: $app));
});

// --- CLS-02 join window ----------------------------------------------------------------------

test('spike CLS-02 token refused outside join window', function (): void {
    $window = config(['windowOpensAt' => NOW - 60, 'windowClosesAt' => NOW + 60]);
    ok(token(app: app($window)));
    error(token(app: app($window, now: NOW - 61)), 409, 'outside_window');
    error(token(app: app($window, now: NOW + 61)), 409, 'outside_window');
});

// --- CLS-05 no strangers ---------------------------------------------------------------------

test('spike CLS-05 token request without access code is rejected', function (): void {
    error(token(['access_code' => '']), 403, 'forbidden');
    error(token(['access_code' => 'fixture-cod']), 403, 'forbidden');
    $body = json_encode(['provider' => 'livekit', 'role' => 'student', 'identity' => 'student-1', 'room' => 'spike-room']);
    error(app()->handle(new Request('POST', '/token', [], $body)), 403, 'forbidden');
});

test('spike CLS-05 identity must be a pseudonym matching the role', function (): void {
    foreach ([
        ['identity' => 'Sara Ahmad'],
        ['identity' => 'teacher-1'],
        ['identity' => 'student-12'],
        ['role' => 'admin', 'identity' => 'admin-1'],
        ['provider' => 'agora'],
        ['room' => 'Room With Spaces'],
    ] as $bad) {
        error(token($bad), 422, 'validation_failed');
    }
});

// --- CLS-13 substitute and supervisor --------------------------------------------------------

test('spike CLS-13 supervisor is visible and named', function (): void {
    $issued = ok(token(['role' => 'supervisor', 'identity' => 'supervisor-1']));
    $claims = lk($issued);
    same(false, $claims['video']['hidden'], 'hidden');
    same('مشرفة', $claims['name'], 'display name');
    same('مشرفة', $issued['display_name'], 'display_name');
    same(['microphone'], $claims['video']['canPublishSources'], 'supervisor sources');
});

test('spike CLS-13 substitute token only for its room', function (): void {
    $claims = lk(ok(token(['role' => 'substitute', 'identity' => 'substitute-1', 'room' => 'room-b'])));
    same('room-b', $claims['video']['room'], 'room');
    same('بديلة 1', $claims['name'], 'display name');
});

// --- plumbing --------------------------------------------------------------------------------

test('spike provider not configured returns 503', function (): void {
    error(token(app: app(config(['liveKitApiKey' => '', 'liveKitApiSecret' => '']))), 503, 'provider_not_configured');
    error(token(['provider' => 'zoom'], app(config(['zoomSdkKey' => '']))), 503, 'provider_not_configured');
});

test('spike errors use the unified error shape', function (): void {
    $json = error(token(['access_code' => 'x']), 403, 'forbidden');
    same(['code', 'message_ar', 'message_en', 'details', 'request_id'], array_keys($json), 'keys');
    check($json['message_ar'] !== '' && $json['message_en'] !== '' && $json['message_ar'] !== $json['message_en'], 'both messages');
    check((bool) preg_match('/^[0-9a-f]{16}$/', $json['request_id']), 'request_id');
    error(app()->handle(new Request('GET', '/nope', [], '')), 404, 'not_found');
    error(app()->handle(new Request('POST', '/token', [], '{not json')), 422, 'validation_failed');
});

// --- CLS-09 join/leave webhooks → attendance minutes -----------------------------------------

/** A LiveKit webhook request signed the way LiveKit signs it: JWT with the body's SHA-256. */
function liveKitHook(array $event, string $secret = LK_SECRET, ?string $bodyOverride = null): Request
{
    $body = json_encode($event);
    $jwt = Jwt::encode(['iss' => 'fixture-lk-key', 'nbf' => NOW - 1, 'exp' => NOW + 300, 'sha256' => base64_encode(hash('sha256', $body, true))], $secret);

    return new Request('POST', '/webhooks/livekit', ['authorization' => $jwt, 'content-type' => 'application/webhook+json'], $bodyOverride ?? $body);
}

/** A Zoom webhook request: x-zm-signature = v0= hex HMAC-SHA256(secret, "v0:{ts}:{body}"). */
function zoomHook(array $event, int $ts = NOW, string $secret = 'fixture-zoom-webhook-secret'): Request
{
    $body = json_encode($event);
    $signature = 'v0='.hash_hmac('sha256', "v0:{$ts}:{$body}", $secret);

    return new Request('POST', '/webhooks/zoom', ['x-zm-signature' => $signature, 'x-zm-request-timestamp' => (string) $ts], $body);
}

function events(App $app): array
{
    return ok($app->handle(new Request('GET', '/events', ['x-spike-code' => 'fixture-code'], '')));
}

function webhookApp(): App
{
    return app(config(['zoomWebhookSecret' => 'fixture-zoom-webhook-secret']));
}

test('spike CLS-09 livekit webhook signature verified', function (): void {
    $app = webhookApp();
    $joined = ['event' => 'participant_joined', 'id' => 'EV_1', 'createdAt' => (string) NOW,
        'room' => ['name' => 'spike-room', 'sid' => 'RM_1'],
        'participant' => ['identity' => 'student-1', 'name' => 'طالب 1', 'sid' => 'PA_1', 'joinedAt' => (string) NOW]];
    same(200, $app->handle(liveKitHook($joined))->status, 'valid hook accepted');
    $log = events($app)['events'];
    same(1, count($log), 'one event stored');
    same(['provider' => 'livekit', 'type' => 'joined', 'event' => 'participant_joined', 'room' => 'spike-room', 'identity' => 'student-1', 'at' => NOW],
        array_intersect_key($log[0], array_flip(['provider', 'type', 'event', 'room', 'identity', 'at'])), 'normalized event');
    check(! str_contains(json_encode($log), 'طالب'), 'display names are not stored');
});

test('spike CLS-09 livekit tampered body or wrong key rejected', function (): void {
    $app = webhookApp();
    $event = ['event' => 'participant_left', 'createdAt' => (string) NOW, 'room' => ['name' => 'spike-room'], 'participant' => ['identity' => 'student-1']];
    error($app->handle(liveKitHook($event, bodyOverride: json_encode(['event' => 'room_finished']))), 401, 'unauthorized');
    error($app->handle(liveKitHook($event, 'another-secret-not-real-0000000000')), 401, 'unauthorized');
    error($app->handle(new Request('POST', '/webhooks/livekit', [], json_encode($event))), 401, 'unauthorized');
    same([], events($app)['events'], 'nothing stored');
});

test('spike CLS-09 zoom signature verified and stale timestamp rejected', function (): void {
    $app = webhookApp();
    $joined = ['event' => 'session.user_joined', 'event_ts' => NOW * 1000, 'payload' => ['object' => [
        'session_id' => 'S1', 'session_name' => 'spike-room',
        'user' => ['id' => '16778240', 'name' => 'معلمة 1', 'user_key' => 'teacher-1', 'join_time' => gmdate('Y-m-d\TH:i:s\Z', NOW)],
    ]]];
    same(200, $app->handle(zoomHook($joined))->status, 'valid hook accepted');
    error($app->handle(zoomHook($joined, NOW - 301)), 401, 'unauthorized');
    error($app->handle(zoomHook($joined, secret: 'wrong-secret')), 401, 'unauthorized');
    $log = events($app)['events'];
    same(1, count($log), 'only the valid hook stored');
    same(['zoom', 'joined', 'spike-room', 'teacher-1', NOW], [$log[0]['provider'], $log[0]['type'], $log[0]['room'], $log[0]['identity'], $log[0]['at']], 'normalized event');
});

test('spike CLS-09 zoom url validation answered', function (): void {
    $r = webhookApp()->handle(zoomHook(['event' => 'endpoint.url_validation', 'payload' => ['plainToken' => 'qgg8vlvZRS6UYooatFL8Aw']]));
    same([
        'plainToken' => 'qgg8vlvZRS6UYooatFL8Aw',
        'encryptedToken' => hash_hmac('sha256', 'qgg8vlvZRS6UYooatFL8Aw', 'fixture-zoom-webhook-secret'),
    ], ok($r), 'validation answer');
});

test('spike CLS-09 minutes from join leave pairs with reconnect', function (): void {
    $e = static fn (string $type, string $who, int $min): array => ['provider' => 'livekit', 'type' => $type, 'room' => 'spike-room', 'identity' => $who, 'at' => NOW + $min * 60];
    $minutes = AttendanceProbe::minutes([
        $e('joined', 'teacher-1', 0),
        $e('joined', 'student-1', 2),
        $e('left', 'student-1', 20),   // network drop
        $e('joined', 'student-1', 22), // back after 2 minutes
        $e('joined', 'student-2', 5),
        $e('joined', 'student-2', 30), // reconnect: new session joins before the old one leaves
        $e('left', 'student-2', 30),
        $e('left', 'student-2', 70),
        $e('left', 'teacher-1', 75),
    ], 'spike-room', endAt: NOW + 75 * 60);
    same(['student-1' => 71.0, 'student-2' => 65.0, 'teacher-1' => 75.0], $minutes, 'minutes per identity (student-1 still in the room at the end)');
});

// --- runner ----------------------------------------------------------------------------------

$failed = 0;
$ran = 0;
foreach ($cases as $name => $fn) {
    if ($filter !== '' && ! str_contains($name, $filter)) {
        continue;
    }
    $ran++;
    try {
        $fn();
        echo "  ✔ {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "  ✘ {$name}\n      ".get_class($e).': '.$e->getMessage()."\n";
    }
}
echo "\n".($ran - $failed)." passed, {$failed} failed\n";
exit($failed === 0 && $ran > 0 ? 0 : 1);
