<?php

declare(strict_types=1);

namespace Spike\Video;

use Closure;
use Spike\Video\Http\Request;
use Spike\Video\Http\Response;
use Spike\Video\Webhooks\LiveKitWebhook;
use Spike\Video\Webhooks\ZoomWebhook;
use Throwable;

/** The spike's HTTP surface. Pure request → response so the self-tests need no server. */
final class App
{
    /** @param Closure(): int $clock */
    public function __construct(
        private readonly Config $config,
        private readonly RoomLocks $locks,
        private readonly EventLog $events,
        private readonly Closure $clock,
        private readonly HttpClient $http,
        private readonly ?EventLog $metrics = null,
        /** @var array<string, string> URL prefix => directory (Flutter web build, Zoom web probe, APK) */
        private readonly array $staticRoots = [],
        private readonly ?AttemptLimiter $limiter = null,
    ) {}

    private const CODE_PROTECTED = ['/token', '/events', '/metrics'];

    public function handle(Request $request): Response
    {
        $requestId = bin2hex(random_bytes(8));
        $client = $request->header('cf-connecting-ip') ?: 'local';
        $protected = in_array($request->path, self::CODE_PROTECTED, true);
        try {
            if ($protected && $this->limiter?->isBlocked($client, ($this->clock)())) {
                throw new ApiError(429, 'too_many_attempts');
            }
            return match ([$request->method, $request->path]) {
                ['GET', '/health'] => Response::json(200, ['ok' => true]),
                ['POST', '/token'] => Response::json(200, (new TokenIssuer($this->config, $this->locks))->issue($request->json(), ($this->clock)())),
                ['POST', '/webhooks/livekit'] => $this->stored(LiveKitWebhook::receive($request, $this->config, ($this->clock)())),
                ['POST', '/webhooks/zoom'] => $this->zoomHook($request),
                ['GET', '/events'] => $this->eventsView($request),
                ['POST', '/metrics'] => $this->metric($request),
                default => match (true) {
                    str_starts_with($request->path, '/admin/') && $request->method === 'POST' => $this->admin($request, substr($request->path, strlen('/admin/'))),
                    $request->method === 'GET' => StaticFiles::serve($this->staticRoots, $request->path),
                    default => throw new ApiError(404, 'not_found'),
                },
            };
        } catch (ApiError $e) {
            if ($protected && $e->status === 403) {
                $this->limiter?->fail($client, ($this->clock)());
            }
            if (str_starts_with($request->path, '/webhooks/') || $request->path === '/metrics' || $e->errorCode === 'provider_error') {
                // Rejections and provider errors are logged with their reason; the caller only gets
                // the code (no hint about which webhook check failed, no provider response body).
                error_log("[{$requestId}] {$request->path} {$e->status} {$e->errorCode} ".json_encode($e->details));
                $e = new ApiError($e->status, $e->errorCode, $e->errorCode === 'provider_error' ? array_intersect_key($e->details, array_flip(['provider', 'status'])) : []);
            }

            return self::error($e, $requestId);
        } catch (Throwable $e) {
            error_log("[{$requestId}] ".get_class($e).': '.$e->getMessage());

            return self::error(new ApiError(500, 'internal_error'), $requestId);
        }
    }

    private function stored(array $event): Response
    {
        $this->events->append($event + ['received_at' => ($this->clock)()]);
        error_log("[webhook] {$event['provider']} {$event['event']} accepted");

        return Response::json(200, ['ok' => true]);
    }

    private function zoomHook(Request $request): Response
    {
        [$event, $validation] = ZoomWebhook::receive($request, $this->config, ($this->clock)());

        if ($validation !== null) {
            error_log('[webhook] zoom endpoint.url_validation answered');

            return Response::json(200, $validation);
        }

        return $this->stored($event);
    }

    /** GET /events: the normalized log plus minutes per identity for each room seen. */
    private function eventsView(Request $request): Response
    {
        $this->requireAccessCode($request);
        $events = $this->events->all();
        $rooms = array_values(array_unique(array_filter(array_column($events, 'room'))));
        $now = ($this->clock)();

        return Response::json(200, [
            'events' => $events,
            'attendance_minutes' => array_combine($rooms, array_map(static fn (string $room): array => AttendanceProbe::minutes($events, $room, $now), $rooms)) ?: (object) [],
        ]);
    }

    /** Host-only moderation (CLS-04) and recording (CLS-10), authorized by the server-signed admin ticket. */
    private function admin(Request $request, string $action): Response
    {
        $body = $request->json();
        $room = is_string($body['room'] ?? null) ? $body['room'] : '';
        try {
            $ticket = Jwt::decode($request->header('x-admin-ticket'), $this->config->ticketSecret, ($this->clock)());
        } catch (\InvalidArgumentException) {
            throw new ApiError(403, 'forbidden');
        }
        if (($ticket['aud'] ?? null) !== 'spike-admin' || ($ticket['room'] ?? null) !== $room
            || ! in_array($ticket['role'] ?? null, ['teacher', 'substitute'], true)) {
            throw new ApiError(403, 'forbidden');
        }

        if ($action === 'lock' || $action === 'unlock') {
            $action === 'lock' ? $this->locks->lock($room) : $this->locks->unlock($room);

            return Response::json(200, ['room' => $room, 'locked' => $action === 'lock']);
        }
        if (($ticket['provider'] ?? null) !== 'livekit') {
            throw new ApiError(422, 'validation_failed', ['provider' => 'Zoom moderation and recording run in the host client SDK']);
        }

        $liveKit = new LiveKitAdmin($this->config, $this->http, ($this->clock)());
        $identity = is_string($body['identity'] ?? null) ? $body['identity'] : '';

        return match ($action) {
            'participants' => Response::json(200, ['participants' => array_map(
                static fn (array $p): array => ['identity' => $p['identity'] ?? null, 'tracks' => count($p['tracks'] ?? [])],
                $liveKit->participants($room),
            )]),
            'mute-all' => Response::json(200, ['muted' => $liveKit->muteAll($room)]),
            'remove' => preg_match('/^(student|supervisor|bot|substitute|teacher)-[1-9]$/', $identity) === 1
                ? (static function () use ($liveKit, $room, $identity): Response {
                    $liveKit->remove($room, $identity);

                    return Response::json(200, ['removed' => $identity]);
                })()
                : throw new ApiError(422, 'validation_failed', ['fields' => ['identity']]),
            'record/start' => Response::json(200, ['egress_id' => $liveKit->startRecording($room)]),
            'record/status' => Response::json(200, ['recordings' => $liveKit->recordings($room)]),
            'record/stop' => (static function () use ($liveKit, $room, $body): Response {
                // Only a recording of this ticket's room can be stopped.
                if (! in_array((string) ($body['egress_id'] ?? ''), array_column($liveKit->recordings($room), 'egress_id'), true)) {
                    throw new ApiError(404, 'not_found');
                }
                $liveKit->stopRecording($room, (string) ($body['egress_id'] ?? ''));

                return Response::json(200, ['egress_id' => (string) ($body['egress_id'] ?? '')]);
            })(),
            default => throw new ApiError(404, 'not_found'),
        };
    }

    /** POST /metrics: one join-time trial (NFR «الدخول للحصة»). Whitelisted fields only, no names. */
    private function metric(Request $request): Response
    {
        $this->requireAccessCode($request);
        $in = $request->json();
        $int = static fn (mixed $v): bool => $v === null || (is_int($v) && $v >= 0 && $v < 600_000);
        $checks = [
            'provider' => in_array($in['provider'] ?? null, ['livekit', 'zoom', 'zoom-web'], true),
            'platform' => is_string($in['platform'] ?? null) && preg_match('/^[A-Za-z]{2,12}$/', $in['platform']) === 1,
            'device' => is_string($in['device'] ?? null) && preg_match('/^[a-z0-9-]{2,30}$/', $in['device']) === 1,
            'network' => in_array($in['network'] ?? null, ['normal', 'limited-1.5mbit'], true),
            'role' => is_string($in['role'] ?? null) && Role::tryFrom($in['role']) !== null,
            'room' => is_string($in['room'] ?? null) && preg_match('/^[a-z0-9][a-z0-9-]{2,39}$/', $in['room']) === 1,
            'mode' => in_array($in['mode'] ?? null, ['auto', 'manual'], true),
            'trial' => is_int($in['trial'] ?? null) && $in['trial'] >= 0 && $in['trial'] <= 1000,
            'ok' => is_bool($in['ok'] ?? null),
            't_token_ms' => $int($in['t_token_ms'] ?? null),
            't_connected_ms' => $int($in['t_connected_ms'] ?? null),
            't_first_video_ms' => $int($in['t_first_video_ms'] ?? null),
        ];
        $bad = array_keys(array_filter($checks, static fn (bool $ok): bool => ! $ok));
        if ($bad !== []) {
            throw new ApiError(422, 'validation_failed', ['fields' => $bad]);
        }
        $row = array_intersect_key($in, $checks + ['error' => true]);
        if (isset($row['error'])) {
            $row['error'] = mb_substr((string) $row['error'], 0, 200);
        }
        $this->metrics?->append($row + ['received_at' => ($this->clock)()]);

        return new Response(204);
    }

    private function requireAccessCode(Request $request): void
    {
        if ($this->config->accessCode === '' || ! hash_equals($this->config->accessCode, $request->header('x-spike-code'))) {
            throw new ApiError(403, 'forbidden');
        }
    }

    private static function error(ApiError $e, string $requestId): Response
    {
        return Response::json($e->status, [
            'code' => $e->errorCode,
            'message_ar' => Lang::get('ar', 'error.'.$e->errorCode),
            'message_en' => Lang::get('en', 'error.'.$e->errorCode),
            'details' => (object) $e->details,
            'request_id' => $requestId,
        ]);
    }
}
