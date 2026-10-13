<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

use Spike\FlutterWeb\Http\Request;
use Spike\FlutterWeb\Http\Response;

/**
 * Serves the two Flutter web builds under `/v/<build>/<headers>/[r-<run>/]<file>`:
 * build `wasm` (WebAssembly + JS fallback), `wasmx` (same files, WebAssembly allowed on every
 * engine), `js` (JavaScript only); headers `iso` (COOP/COEP) or `plain`. The optional run segment
 * gives each cold run fresh URLs (empty HTTP and compiled-code caches) for the same files.
 */
final class BuildFiles
{
    public const ROUTE = '#^/v/(wasmx|wasm|js)/(iso|plain)/(?:(r-[0-9a-f]{4,32})/)?(.*)$#';

    private const TYPES = [
        'html' => 'text/html; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json',
        'wasm' => 'application/wasm',
        'png' => 'image/png',
        'ico' => 'image/x-icon',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'bin' => 'application/octet-stream',
        'frag' => 'application/octet-stream',
        'symbols' => 'application/octet-stream',
    ];

    /** @param \Closure(): int $clock */
    public function __construct(private readonly Config $config, private readonly \Closure $clock) {}

    public function serve(Request $request, array $m): Response
    {
        [, $build, $headers, $run, $file] = $m + [3 => '', 4 => ''];
        $isolated = $headers === 'iso';
        $dir = $this->config->builds[$build === 'wasmx' ? 'wasm' : $build] ?? '';
        $base = realpath($dir);
        $common = Headers::isolation($isolated);

        if (str_starts_with($file, 'fonts/fallback/')) {
            // Flutter asks here for glyphs no bundled font has (fontFallbackBaseUrl). Logged, not served.
            Store::append($this->config->varDir.'/fallback-fonts.jsonl', [
                'at' => ($this->clock)(), 'variant' => "{$build}/{$headers}", 'path' => substr($file, strlen('fonts/fallback/')),
            ]);
            throw new ApiError(404, 'not_found');
        }

        $path = $base === false ? false : realpath($base.'/'.($file === '' ? 'index.html' : $file));
        if ($base === false || $path === false || ! is_file($path) || ! str_starts_with($path, $base.DIRECTORY_SEPARATOR)) {
            throw new ApiError(404, 'not_found');
        }
        $type = self::TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';

        if (basename($path) === 'index.html') {
            $nonce = base64_encode(random_bytes(16));
            $body = $this->index((string) file_get_contents($path), $build, $headers, $run, $nonce, $base);

            return Response::html($body, $common + Headers::csp($this->config, $nonce));
        }

        $common += Headers::csp($this->config) + ['Content-Type' => $type, 'Cache-Control' => 'no-cache'];
        if (basename($path) === 'sw.js') {
            $body = str_replace('__BUILD_ID__', $this->buildId($base), (string) file_get_contents($path));

            return new Response(200, $body, $common + ['Service-Worker-Allowed' => '/v/']);
        }

        [$served, $encoding] = $this->pickEncoding($path, $request->header('accept-encoding'));
        $etag = '"'.md5($served.'|'.filemtime($served).'|'.filesize($served)).'"';
        $common += ['ETag' => $etag, 'Vary' => 'Accept-Encoding'];
        if ($encoding !== null) {
            $common['Content-Encoding'] = $encoding;
        }
        if (in_array($etag, array_map('trim', explode(',', $request->header('if-none-match'))), true)) {
            return new Response(304, '', $common);
        }

        return new Response(200, (string) file_get_contents($served), $common);
    }

    /** @return array{0: string, 1: ?string} file to send and its Content-Encoding */
    private function pickEncoding(string $path, string $accept): array
    {
        $accepted = array_map(static fn (string $p): string => strtolower(trim(explode(';', $p)[0])), explode(',', $accept));
        foreach (['br' => '.br', 'gzip' => '.gz'] as $encoding => $suffix) {
            if (in_array($encoding, $accepted, true) && is_file($path.$suffix)) {
                return [$path.$suffix, $encoding];
            }
        }

        return [$path, null];
    }

    private function index(string $html, string $build, string $headers, ?string $run, string $nonce, string $base): string
    {
        $href = "/v/{$build}/{$headers}/".($run !== null && $run !== '' ? $run.'/' : '');
        $meta = [
            'variant' => "{$build}/{$headers}",
            'run' => (string) $run,
            'pay-origin' => $this->config->payOrigin,
            'build-id' => $this->buildId($base),
            'force-wasm' => $build === 'wasmx' ? '1' : '0',
            'nonce' => $nonce,
        ];
        $tags = '';
        foreach ($meta as $name => $value) {
            $tags .= sprintf('<meta name="sura-%s" content="%s">', $name, htmlspecialchars($value, ENT_QUOTES)).PHP_EOL.'  ';
        }
        $html = (string) preg_replace('#<base href="[^"]*">#', '<base href="'.$href.'">', $html, 1);

        return str_replace('<!--sura-meta-->', rtrim($tags), $html);
    }

    private function buildId(string $base): string
    {
        $file = $base.'/build-id.txt';

        return is_file($file) ? trim((string) file_get_contents($file)) : (string) filemtime($base.'/index.html');
    }
}
