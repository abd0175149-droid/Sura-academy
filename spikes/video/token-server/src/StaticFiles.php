<?php

declare(strict_types=1);

namespace Spike\Video;

use Spike\Video\Http\Response;

/**
 * Serves the Flutter web build, the Zoom web probe and the Android APK from one origin, so a
 * single tunnel URL works on every device. Paths are resolved with realpath() inside their root.
 */
final class StaticFiles
{
    private const TYPES = [
        'html' => 'text/html; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json',
        'wasm' => 'application/wasm',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'woff2' => 'font/woff2',
        'bin' => 'application/octet-stream',
        'frag' => 'application/octet-stream',
        'symbols' => 'application/octet-stream',
        'map' => 'application/json',
        'apk' => 'application/vnd.android.package-archive',
    ];

    /** @param array<string, string> $roots URL prefix => directory */
    public static function serve(array $roots, string $path): Response
    {
        uksort($roots, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($roots as $prefix => $dir) {
            if (! str_starts_with($path, $prefix) && $path.'/' !== $prefix) {
                continue;
            }
            $relative = substr($path, strlen($prefix)) ?: '';
            $base = realpath($dir);
            $file = $base === false ? false : realpath($base.'/'.($relative === '' || str_ends_with($relative, '/') ? $relative.'index.html' : $relative));
            if ($base === false || $file === false || ! is_file($file) || ! str_starts_with($file, $base.DIRECTORY_SEPARATOR)) {
                break;
            }
            $headers = [
                'Content-Type' => self::TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream',
                'Cache-Control' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ];
            if ($prefix === '/zoom-web/') {
                // SharedArrayBuffer for Zoom's WebAssembly video (gallery view, 720p).
                $headers += ['Cross-Origin-Opener-Policy' => 'same-origin', 'Cross-Origin-Embedder-Policy' => 'require-corp', 'Cross-Origin-Resource-Policy' => 'same-origin'];
            }

            return new Response(200, (string) file_get_contents($file), $headers);
        }

        throw new ApiError(404, 'not_found');
    }
}
