<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * Zoom Video SDK JWT (https://developers.zoom.us/docs/video-sdk/auth/). Zoom requires
 * `exp` ≥ `iat` + 1800 s, so the shortest possible lifetime is 30 minutes (finding for CLS-03).
 */
final class ZoomToken
{
    public const TTL = 1800;

    /** Clock-skew allowance Zoom recommends: iat slightly in the past. */
    private const SKEW = 30;

    public static function build(Config $config, Role $role, string $identity, string $room, int $now): string
    {
        $iat = $now - self::SKEW;

        return Jwt::encode([
            'app_key' => $config->zoomSdkKey,
            'version' => 1,
            'tpc' => $room,
            'role_type' => $role->isHost() ? 1 : 0,
            'user_key' => $identity,
            'geo_regions' => 'DE,NL',
            'iat' => $iat,
            'exp' => $iat + self::TTL,
        ], $config->zoomSdkSecret);
    }
}
