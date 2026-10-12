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
use Spike\Video\HttpClient;
use Spike\Video\Jwt;
use Spike\Video\RoomLocks;
use Spike\Video\ZoomRecordings;

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

    return new App($config ?? config(), $locks, $events, static fn (): int => $now, new FakeHttp());
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
    error(token(['role' => 'supervisor', 'identity' => 'supervisor-1'], $app), 409, 'room_locked');
    ok(token(['role' => 'teacher', 'identity' => 'teacher-1'], $app));
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

// --- CLS-04 moderation and CLS-10 recording through the server --------------------------------

/** Records outgoing provider calls; answers with $responder(method, url, body) → [status, body]. */
final class FakeHttp implements HttpClient
{
    public array $calls = [];

    public function __construct(private readonly ?Closure $responder = null) {}

    public function request(string $method, string $url, array $headers, ?string $body = null): array
    {
        $this->calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body === null ? null : json_decode($body, true)];

        return $this->responder !== null ? ($this->responder)($method, $url, $body) : [200, '{}'];
    }

    /** Twirp method names called, in order. */
    public function methods(): array
    {
        return array_map(static fn (array $c): string => basename((string) parse_url($c['url'], PHP_URL_PATH)), $this->calls);
    }
}

const EGRESS = ['endpoint' => 'https://minio.fixture.example', 'bucket' => 'spike-recordings', 'access_key' => 'fixture-egress-user', 'secret' => 'fixture-egress-pass', 'region' => 'eu-central-1'];

function adminApp(FakeHttp $http, ?RoomLocks $locks = null): App
{
    $locks ??= new RoomLocks($GLOBALS['tmp'].'/locks-'.bin2hex(random_bytes(4)).'.json');
    $config = config(['egressS3' => EGRESS, 'zoomApiKey' => 'fixture-zoom-api-key', 'zoomApiSecret' => 'fixture-zoom-api-secret']);

    return new App($config, $locks, new EventLog($GLOBALS['tmp'].'/events-admin.jsonl'), static fn (): int => NOW, $http);
}

/** POST /admin/{action} with a host ticket for $room (issued through /token). */
function admin(App $app, string $action, array $body = [], string $ticketRole = 'teacher', string $ticketRoom = 'spike-room'): Response
{
    $ticket = ok(token(['role' => $ticketRole, 'identity' => $ticketRole.'-1', 'room' => $ticketRoom], $app))['admin_ticket'] ?? '';

    return $app->handle(new Request('POST', '/admin/'.$action, ['x-admin-ticket' => $ticket], json_encode($body + ['room' => 'spike-room'])));
}

function participants(): Closure
{
    return static fn (string $m, string $url): array => str_ends_with($url, '/ListParticipants') ? [200, json_encode(['participants' => [
        ['identity' => 'teacher-1', 'tracks' => [['sid' => 'TR_t_mic', 'type' => 'AUDIO', 'source' => 'MICROPHONE']]],
        ['identity' => 'student-1', 'tracks' => [['sid' => 'TR_s1_mic', 'type' => 'AUDIO', 'source' => 'MICROPHONE'], ['sid' => 'TR_s1_cam', 'type' => 'VIDEO', 'source' => 'CAMERA']]],
        ['identity' => 'student-2', 'tracks' => [['sid' => 'TR_s2_mic', 'type' => 'AUDIO', 'source' => 'MICROPHONE', 'muted' => true]]],
    ]])] : [200, '{}'];
}

test('spike CLS-04 mute all mutes every student microphone but not the teacher', function (): void {
    $http = new FakeHttp(participants());
    same(['muted' => ['student-1']], ok(admin(adminApp($http), 'mute-all')), 'result');
    same(['ListParticipants', 'MutePublishedTrack'], $http->methods(), 'twirp calls');
    same(['room' => 'spike-room', 'identity' => 'student-1', 'track_sid' => 'TR_s1_mic', 'muted' => true], $http->calls[1]['body'], 'mute body');
    same('https://fixture.livekit.cloud/twirp/livekit.RoomService/MutePublishedTrack', $http->calls[1]['url'], 'url from wss');
    $bearer = substr($http->calls[1]['headers']['Authorization'], strlen('Bearer '));
    $grant = Jwt::decode($bearer, LK_SECRET, NOW)['video'];
    same(['spike-room', true], [$grant['room'], $grant['roomAdmin']], 'server-side admin token for this room only');
});

test('spike CLS-04 remove participant through the server', function (): void {
    $http = new FakeHttp();
    ok(admin(adminApp($http), 'remove', ['identity' => 'student-2']));
    same(['RemoveParticipant'], $http->methods(), 'twirp calls');
    same(['room' => 'spike-room', 'identity' => 'student-2'], $http->calls[0]['body'], 'remove body');
    error(admin(adminApp($http), 'remove', ['identity' => 'Sara']), 422, 'validation_failed');
});

test('spike CLS-04 admin actions need a host ticket for the same room', function (): void {
    $http = new FakeHttp(participants());
    $app = adminApp($http);
    error($app->handle(new Request('POST', '/admin/mute-all', [], json_encode(['room' => 'spike-room']))), 403, 'forbidden');
    error(admin($app, 'mute-all', ticketRole: 'student'), 403, 'forbidden');
    error(admin($app, 'mute-all', ticketRoom: 'other-room'), 403, 'forbidden');
    ok(admin($app, 'mute-all', ticketRole: 'substitute'));
});

test('spike CLS-04 lock through the server stops new tokens', function (): void {
    $app = adminApp(new FakeHttp());
    ok(admin($app, 'lock'));
    error(token(app: $app), 409, 'room_locked');
    ok(admin($app, 'unlock'));
    ok(token(app: $app));
});

test('spike CLS-10 livekit recording uploads straight to our bucket', function (): void {
    $http = new FakeHttp(static fn (): array => [200, json_encode(['egressId' => 'EG_1', 'status' => 'EGRESS_STARTING'])]);
    same(['egress_id' => 'EG_1'], ok(admin(adminApp($http), 'record/start')), 'start result');
    same('https://fixture.livekit.cloud/twirp/livekit.Egress/StartRoomCompositeEgress', $http->calls[0]['url'], 'egress url');
    $req = $http->calls[0]['body'];
    same('spike-room', $req['room_name'], 'room');
    $s3 = $req['file_outputs'][0]['s3'];
    same([EGRESS['bucket'], EGRESS['endpoint'], true, EGRESS['region']], [$s3['bucket'], $s3['endpoint'], $s3['force_path_style'], $s3['region']], 's3 target');
    check(str_starts_with($req['file_outputs'][0]['filepath'], 'spike-room/'), 'object key per room');
    ok(admin(adminApp($http), 'record/stop', ['egress_id' => 'EG_1']));
    same(['egress_id' => 'EG_1'], $http->calls[1]['body'], 'stop body');
    error(admin(adminApp(new FakeHttp(static fn (): array => [400, '{"code":"invalid_argument"}'])), 'record/start'), 502, 'provider_error');
});

test('spike CLS-10 zoom recordings are listed downloaded and deleted permanently', function (): void {
    $files = ['recording_files' => [['id' => 'F1', 'file_type' => 'MP4', 'file_size' => 3, 'download_url' => 'https://zoom.fixture/rec/F1']]];
    $http = new FakeHttp(static fn (string $m, string $url): array => match (true) {
        $m === 'GET' && str_contains($url, '/rec/F1') => [200, 'mp4'],
        $m === 'GET' => [200, json_encode($files)],
        default => [204, ''],
    });
    $zoom = new ZoomRecordings(config(['zoomApiKey' => 'fixture-zoom-api-key', 'zoomApiSecret' => 'fixture-zoom-api-secret']), $http, static fn (): int => NOW);
    same($files['recording_files'], $zoom->files('/abc==//x'), 'files');
    same('https://api.zoom.us/v2/videosdk/sessions/'.rawurlencode(rawurlencode('/abc==//x')).'/recordings', $http->calls[0]['url'], 'double-encoded session id');
    $api = Jwt::decode(substr($http->calls[0]['headers']['Authorization'], 7), 'fixture-zoom-api-secret', NOW);
    same('fixture-zoom-api-key', $api['iss'], 'API JWT issuer');
    $target = $GLOBALS['tmp'].'/zoom-F1.mp4';
    same(3, $zoom->download($files['recording_files'][0], $target), 'bytes written');
    same('mp4', file_get_contents($target), 'file content');
    $zoom->delete('abc');
    same(['DELETE', 'https://api.zoom.us/v2/videosdk/sessions/abc/recordings?action=delete'], [end($http->calls)['method'], end($http->calls)['url']], 'permanent delete');
});

// --- join-time metrics and static files ------------------------------------------------------

test('spike NFR-join metrics stored without names and only with the access code', function (): void {
    $file = $GLOBALS['tmp'].'/metrics.jsonl';
    $app = new App(config(), new RoomLocks($GLOBALS['tmp'].'/l-m.json'), new EventLog($GLOBALS['tmp'].'/e-m.jsonl'), static fn (): int => NOW, new FakeHttp(), metrics: new EventLog($file));
    $trial = ['provider' => 'livekit', 'platform' => 'web', 'device' => 'ipad-safari', 'network' => 'normal', 'role' => 'student', 'room' => 'spike-room',
        'trial' => 3, 't_token_ms' => 210, 't_connected_ms' => 1300, 't_first_video_ms' => 2100, 'ok' => true, 'mode' => 'auto', 'name' => 'Sara'];
    same(204, $app->handle(new Request('POST', '/metrics', ['x-spike-code' => 'fixture-code'], json_encode($trial)))->status, 'stored');
    error($app->handle(new Request('POST', '/metrics', [], json_encode($trial))), 403, 'forbidden');
    error($app->handle(new Request('POST', '/metrics', ['x-spike-code' => 'fixture-code'], json_encode(['device' => 'Sara iPad'] + $trial))), 422, 'validation_failed');
    $stored = json_decode(trim((string) file_get_contents($file)), true);
    check(! isset($stored['name']), 'unknown fields are dropped');
    same([2100, 'ipad-safari', NOW], [$stored['t_first_video_ms'], $stored['device'], $stored['received_at']], 'stored fields');
});

test('spike static files are served from the web build without path traversal', function (): void {
    $web = $GLOBALS['tmp'].'/web';
    @mkdir($web.'/assets', 0700, true);
    file_put_contents($web.'/index.html', '<html>spike</html>');
    file_put_contents($web.'/main.dart.js', 'js');
    file_put_contents($GLOBALS['tmp'].'/secret.txt', 'nope');
    $app = new App(config(), new RoomLocks($GLOBALS['tmp'].'/l-s.json'), new EventLog($GLOBALS['tmp'].'/e-s.jsonl'), static fn (): int => NOW, new FakeHttp(), staticRoots: ['/' => $web]);
    $index = $app->handle(new Request('GET', '/', [], ''));
    same([200, '<html>spike</html>'], [$index->status, $index->body], 'index');
    same('text/html; charset=utf-8', $index->headers['Content-Type'], 'html type');
    same('text/javascript; charset=utf-8', $app->handle(new Request('GET', '/main.dart.js', [], ''))->headers['Content-Type'], 'js type');
    error($app->handle(new Request('GET', '/../secret.txt', [], '')), 404, 'not_found');
    error($app->handle(new Request('GET', '/assets/../../secret.txt', [], '')), 404, 'not_found');
});

test('spike zoom web probe gets cross-origin isolation headers', function (): void {
    $probe = $GLOBALS['tmp'].'/zoom-web';
    @mkdir($probe, 0700, true);
    file_put_contents($probe.'/index.html', 'zoom');
    $app = new App(config(), new RoomLocks($GLOBALS['tmp'].'/l-z.json'), new EventLog($GLOBALS['tmp'].'/e-z.jsonl'), static fn (): int => NOW, new FakeHttp(), staticRoots: ['/zoom-web/' => $probe]);
    $r = $app->handle(new Request('GET', '/zoom-web/', [], ''));
    same(['same-origin', 'require-corp'], [$r->headers['Cross-Origin-Opener-Policy'] ?? null, $r->headers['Cross-Origin-Embedder-Policy'] ?? null], 'COOP/COEP');
});

// --- results: join-time summary and cost model ------------------------------------------------

test('spike NFR-join summary groups automatic trials and counts failures against p95', function (): void {
    $rows = [];
    foreach ([1200, 1500, 900, 2000, 1100, 1300, 1400, 1000, 1600, 1700, 1800, 1900, 2100, 2200, 2300, 2400, 2500, 2600, 2700] as $i => $ms) {
        $rows[] = ['provider' => 'livekit', 'platform' => 'web', 'device' => 'ipad-safari', 'network' => 'normal', 'mode' => 'auto', 'trial' => $i + 1, 'ok' => true, 't_first_video_ms' => $ms, 't_connected_ms' => $ms - 300, 't_token_ms' => 200];
    }
    $rows[] = ['provider' => 'livekit', 'platform' => 'web', 'device' => 'ipad-safari', 'network' => 'normal', 'mode' => 'auto', 'trial' => 20, 'ok' => false, 't_first_video_ms' => null];
    $rows[] = ['provider' => 'livekit', 'platform' => 'web', 'device' => 'ipad-safari', 'network' => 'normal', 'mode' => 'manual', 'trial' => 0, 'ok' => true, 't_first_video_ms' => 50];
    $summary = Spike\Video\Results::joinSummary($rows);
    same(1, count($summary), 'one group (manual joins excluded)');
    $g = $summary[0];
    same([20, 19, 1.8, 2.7, true], [$g['n'], $g['ok'], $g['p50_s'], $g['p95_s'], $g['meets_nfr']], 'n, ok, p50, p95, NFR (< 10 s at p95)');
    $rows[] = ['provider' => 'livekit', 'platform' => 'web', 'device' => 'ipad-safari', 'network' => 'normal', 'mode' => 'auto', 'trial' => 21, 'ok' => false, 't_first_video_ms' => null];
    same([null, false], [Spike\Video\Results::joinSummary($rows)[0]['p95_s'], Spike\Video\Results::joinSummary($rows)[0]['meets_nfr']], 'two failures in 21 → p95 unmeasurable → NFR not met');
});

test('spike cost model multiplies the WP-0.4 load literally', function (): void {
    $load = Spike\Video\Results::load(classes: 100, participants: 4, minutes: 75, sessionsPerMonth: 8);
    same(['room_minutes' => 60_000, 'participant_minutes' => 240_000], $load, 'load');
    $zoom = Spike\Video\Results::zoomCost($load, recordedShare: 1.0);
    same([840.0, 600.0], [$zoom['sessions_usd'], $zoom['recording_usd']], 'zoom: 0.0035/participant-min, 0.01/recorded min');
    $lk = Spike\Video\Results::liveKitCost($load, recordedShare: 0.0, downMbpsPerParticipant: 1.0, plan: 'ship');
    same(50.0 + 90_000 * 0.0005, $lk['base_and_minutes_usd'], 'livekit ship: $50 incl. 150k, then $0.0005');
    same(round((240_000 * 60 * 1.0 / 8 / 1000 - 250) * 0.12, 2), $lk['bandwidth_usd'], 'bandwidth over 250 GB at $0.12/GB');
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
