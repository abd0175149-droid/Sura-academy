<?php

declare(strict_types=1);

namespace Spike\Video\Webhooks;

use Spike\Video\ApiError;
use Spike\Video\Config;
use Spike\Video\Http\Request;

/**
 * Zoom webhooks (https://developers.zoom.us/docs/api/webhooks/): x-zm-signature is
 * `v0=` + hex HMAC-SHA256(secret, "v0:{x-zm-request-timestamp}:{body}"). Requests older than
 * five minutes are refused (replay). `endpoint.url_validation` is answered with the HMAC of plainToken.
 */
final class ZoomWebhook
{
    private const MAX_AGE = 300;

    private const TYPES = [
        'session.user_joined' => 'joined',
        'session.user_left' => 'left',
        'session.recording_completed' => 'recording_ready',
    ];

    /** @return array{0: array|null, 1: array|null} [normalized event or null, validation answer or null] */
    public static function receive(Request $request, Config $config, int $now): array
    {
        $ts = $request->header('x-zm-request-timestamp');
        $expected = 'v0='.hash_hmac('sha256', "v0:{$ts}:{$request->body}", $config->zoomWebhookSecret);
        $reason = match (true) {
            $config->zoomWebhookSecret === '' => 'no webhook secret configured',
            ! ctype_digit($ts) => 'missing x-zm-request-timestamp',
            abs($now - (int) $ts) > self::MAX_AGE => 'timestamp outside 5 minutes',
            ! hash_equals($expected, $request->header('x-zm-signature')) => 'signature mismatch',
            default => null,
        };
        if ($reason !== null) {
            throw new ApiError(401, 'unauthorized', ['reason' => $reason]);
        }

        $event = $request->json();
        $name = (string) ($event['event'] ?? '');
        if ($name === 'endpoint.url_validation') {
            $plain = (string) ($event['payload']['plainToken'] ?? '');

            return [null, ['plainToken' => $plain, 'encryptedToken' => hash_hmac('sha256', $plain, $config->zoomWebhookSecret)]];
        }

        $object = $event['payload']['object'] ?? [];
        $user = $object['user'] ?? [];
        $type = self::TYPES[$name] ?? 'other';
        $time = match ($type) {
            'joined' => $user['join_time'] ?? null,
            'left' => $user['leave_time'] ?? null,
            default => null,
        };

        return [array_filter([
            'provider' => 'zoom',
            'type' => $type,
            'event' => $name,
            'room' => $object['session_name'] ?? null,
            'identity' => $user['user_key'] ?? null,
            'at' => $time !== null ? (int) strtotime((string) $time) : intdiv((int) ($event['event_ts'] ?? $now * 1000), 1000),
            'session_id' => $object['session_id'] ?? null,
            'zoom_user_id' => $user['id'] ?? null,
            'leave_reason' => $user['leave_reason'] ?? null,
            'recording_files' => isset($object['recording_files']) ? array_map(
                static fn (array $f): array => array_intersect_key($f, array_flip(['id', 'file_type', 'file_size', 'recording_start', 'recording_end'])),
                $object['recording_files'],
            ) : null,
        ], static fn (mixed $v): bool => $v !== null), null];
    }
}
