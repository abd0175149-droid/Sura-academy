<?php

declare(strict_types=1);

namespace Spike\Video;

use InvalidArgumentException;

/** HS256 JSON Web Tokens: the format LiveKit and Zoom both use for join tokens. */
final class Jwt
{
    public static function encode(array $claims, string $secret): string
    {
        $segments = [
            self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
            self::b64(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ];
        $segments[] = self::b64(hash_hmac('sha256', implode('.', $segments), $secret, true));

        return implode('.', $segments);
    }

    /** Verifies signature, `exp` and `nbf` at $now; returns the claims or throws InvalidArgumentException. */
    public static function decode(string $jwt, string $secret, int $now): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('malformed token');
        }
        [$header, $payload, $signature] = $parts;
        $expected = self::b64(hash_hmac('sha256', $header.'.'.$payload, $secret, true));
        if (! hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('bad signature');
        }
        $alg = json_decode(self::unb64($header), true)['alg'] ?? null;
        $claims = json_decode(self::unb64($payload), true);
        if ($alg !== 'HS256' || ! is_array($claims)) {
            throw new InvalidArgumentException('unsupported token');
        }
        if (! isset($claims['exp'])) {
            throw new InvalidArgumentException('token without exp');
        }
        if ($now >= (int) $claims['exp']) {
            throw new InvalidArgumentException('token expired');
        }
        if (isset($claims['nbf']) && $now < (int) $claims['nbf']) {
            throw new InvalidArgumentException('token not yet valid');
        }

        return $claims;
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function unb64(string $text): string
    {
        return (string) base64_decode(strtr($text, '-_', '+/'), true);
    }
}
