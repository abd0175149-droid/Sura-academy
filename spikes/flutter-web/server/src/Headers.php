<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

/** Response headers: isolation (ADR-010 §3) and the content security policy (ADR-010 §7). */
final class Headers
{
    /** @return array<string, string> */
    public static function isolation(bool $isolated): array
    {
        return ($isolated ? [
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Embedder-Policy' => 'require-corp',
        ] : []) + self::base();
    }

    /** @return array<string, string> */
    public static function base(): array
    {
        return [
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'camera=(self), microphone=(self), geolocation=(), payment=()',
        ];
    }

    /**
     * Enforced policy: what the spike needs to run. Report-only policy: the strictest candidate
     * (no blob: workers or media), so its violations show what Flutter really requires.
     *
     * @return array<string, string>
     */
    public static function csp(Config $config, ?string $nonce = null): array
    {
        $n = $nonce === null ? '' : " 'nonce-{$nonce}'";
        $liveKit = [self::origin($config->liveKitLocal['url']), self::http($config->liveKitLocal['url'])];
        if ($config->liveKitCloud !== null) {
            $cloud = $config->liveKitCloud['url'];
            $liveKit[] = self::origin($cloud);
            $liveKit[] = self::http($cloud);
            if (str_ends_with((string) parse_url($cloud, PHP_URL_HOST), '.livekit.cloud')) {
                // Regional edge hosts the client may be sent to by LiveKit Cloud's region settings.
                array_push($liveKit, 'wss://*.livekit.cloud', 'https://*.livekit.cloud');
            }
        }
        $connect = implode(' ', array_unique(array_filter($liveKit)));
        $pay = $config->payOrigin;
        $policy = static fn (string $blob): string => implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'wasm-unsafe-eval'{$n}",
            "worker-src 'self'{$blob}",
            "style-src 'self'{$n}",
            "img-src 'self' data:{$blob}",
            "font-src 'self' data:",
            "connect-src 'self' {$connect}",
            "media-src 'self'{$blob}",
            "frame-src {$pay}",
            "form-action 'self' {$pay}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            "manifest-src 'self'",
            'report-uri /csp-report',
        ]);

        return [
            'Content-Security-Policy' => $policy(' blob:'),
            'Content-Security-Policy-Report-Only' => $policy(''),
        ];
    }

    private static function origin(string $url): string
    {
        $p = parse_url($url);
        if (! isset($p['scheme'], $p['host'])) {
            return '';
        }

        return $p['scheme'].'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '');
    }

    /** The http(s) twin of a ws(s) URL (LiveKit's validate and region endpoints). */
    private static function http(string $url): string
    {
        return self::origin((string) preg_replace(['/^wss:/', '/^ws:/'], ['https:', 'http:'], $url));
    }
}
