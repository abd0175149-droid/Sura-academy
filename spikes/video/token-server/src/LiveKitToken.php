<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * LiveKit access token (https://docs.livekit.io/frontends/reference/tokens-grants/).
 * Client tokens never carry roomAdmin/roomRecord: moderation and recording go through this server.
 */
final class LiveKitToken
{
    public const TTL = 600;

    public static function build(Config $config, Role $role, string $identity, string $name, string $room, int $now): string
    {
        return Jwt::encode([
            'iss' => $config->liveKitApiKey,
            'sub' => $identity,
            'name' => $name,
            'nbf' => $now,
            'exp' => $now + self::TTL,
            'metadata' => json_encode(['role' => $role->value]),
            'video' => [
                'room' => $room,
                'roomJoin' => true,
                'canSubscribe' => true,
                'canPublish' => true,
                'canPublishData' => true,
                'canPublishSources' => $role->liveKitSources(),
                'hidden' => false,
            ],
        ], $config->liveKitApiSecret);
    }

    /** A short server-side token for RoomService/Egress calls on one room. */
    public static function admin(Config $config, string $room, int $now): string
    {
        return Jwt::encode([
            'iss' => $config->liveKitApiKey,
            'sub' => 'spike-server',
            'nbf' => $now,
            'exp' => $now + 60,
            'video' => ['room' => $room, 'roomAdmin' => true, 'roomRecord' => true, 'roomList' => true],
        ], $config->liveKitApiSecret);
    }
}
