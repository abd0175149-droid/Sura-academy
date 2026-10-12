<?php

declare(strict_types=1);

namespace Spike\Video;

use Closure;
use Spike\Video\Http\Request;
use Spike\Video\Http\Response;
use Throwable;

/** The spike's HTTP surface. Pure request → response so the self-tests need no server. */
final class App
{
    /** @param Closure(): int $clock */
    public function __construct(
        private readonly Config $config,
        private readonly RoomLocks $locks,
        private readonly Closure $clock,
    ) {}

    public function handle(Request $request): Response
    {
        $requestId = bin2hex(random_bytes(8));
        try {
            return match ([$request->method, $request->path]) {
                ['GET', '/health'] => Response::json(200, ['ok' => true]),
                ['POST', '/token'] => Response::json(200, (new TokenIssuer($this->config, $this->locks))->issue($request->json(), ($this->clock)())),
                default => throw new ApiError(404, 'not_found'),
            };
        } catch (ApiError $e) {
            return self::error($e, $requestId);
        } catch (Throwable $e) {
            error_log("[{$requestId}] ".get_class($e).': '.$e->getMessage());

            return self::error(new ApiError(500, 'internal_error'), $requestId);
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
