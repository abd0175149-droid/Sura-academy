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
    ) {}

    public function handle(Request $request): Response
    {
        $requestId = bin2hex(random_bytes(8));
        try {
            return match ([$request->method, $request->path]) {
                ['GET', '/health'] => Response::json(200, ['ok' => true]),
                ['POST', '/token'] => Response::json(200, (new TokenIssuer($this->config, $this->locks))->issue($request->json(), ($this->clock)())),
                ['POST', '/webhooks/livekit'] => $this->stored(LiveKitWebhook::receive($request, $this->config, ($this->clock)())),
                ['POST', '/webhooks/zoom'] => $this->zoomHook($request),
                ['GET', '/events'] => $this->eventsView($request),
                default => throw new ApiError(404, 'not_found'),
            };
        } catch (ApiError $e) {
            return self::error($e, $requestId);
        } catch (Throwable $e) {
            error_log("[{$requestId}] ".get_class($e).': '.$e->getMessage());

            return self::error(new ApiError(500, 'internal_error'), $requestId);
        }
    }

    private function stored(array $event): Response
    {
        $this->events->append($event + ['received_at' => ($this->clock)()]);

        return Response::json(200, ['ok' => true]);
    }

    private function zoomHook(Request $request): Response
    {
        [$event, $validation] = ZoomWebhook::receive($request, $this->config, ($this->clock)());

        return $validation !== null ? Response::json(200, $validation) : $this->stored($event);
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
