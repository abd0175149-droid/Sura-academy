<?php

declare(strict_types=1);

namespace Spike\Video\Webhooks;

use InvalidArgumentException;
use Spike\Video\ApiError;
use Spike\Video\Config;
use Spike\Video\Http\Request;
use Spike\Video\Jwt;

/**
 * LiveKit webhooks (https://docs.livekit.io/home/server/webhooks/): the Authorization header is a
 * JWT signed with the project's API secret whose `sha256` claim is the base64 SHA-256 of the body.
 */
final class LiveKitWebhook
{
    private const TYPES = [
        'participant_joined' => 'joined',
        'participant_left' => 'left',
        'participant_connection_aborted' => 'left',
        'egress_ended' => 'recording_ready',
    ];

    /** @return array normalized event */
    public static function receive(Request $request, Config $config, int $now): array
    {
        $jwt = preg_replace('/^Bearer\s+/i', '', $request->header('authorization')) ?? '';
        try {
            $claims = Jwt::decode($jwt, $config->liveKitApiSecret, $now);
        } catch (InvalidArgumentException) {
            throw new ApiError(401, 'unauthorized');
        }
        $digest = base64_encode(hash('sha256', $request->body, true));
        if ($config->liveKitApiSecret === '' || ($claims['iss'] ?? null) !== $config->liveKitApiKey
            || ! hash_equals($digest, (string) ($claims['sha256'] ?? ''))) {
            throw new ApiError(401, 'unauthorized');
        }

        $event = $request->json();
        $name = (string) ($event['event'] ?? '');
        $egress = $event['egressInfo'] ?? [];

        return array_filter([
            'provider' => 'livekit',
            'type' => self::TYPES[$name] ?? 'other',
            'event' => $name,
            'room' => $event['room']['name'] ?? $egress['roomName'] ?? null,
            'identity' => $event['participant']['identity'] ?? null,
            'at' => (int) ($event['createdAt'] ?? $now),
            'provider_event_id' => $event['id'] ?? null,
            'participant_sid' => $event['participant']['sid'] ?? null,
            'egress' => $egress === [] ? null : [
                'id' => $egress['egressId'] ?? null,
                'status' => $egress['status'] ?? null,
                'files' => array_map(static fn (array $f): array => array_intersect_key($f, array_flip(['filename', 'location', 'size', 'duration'])), $egress['fileResults'] ?? []),
            ],
        ], static fn (mixed $v): bool => $v !== null);
    }
}
