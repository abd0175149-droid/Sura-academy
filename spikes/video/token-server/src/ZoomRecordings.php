<?php

declare(strict_types=1);

namespace Spike\Video;

use Closure;

/**
 * Zoom Video SDK cloud recordings (https://developers.zoom.us/docs/api/video-sdk/): list a
 * session's files, download them to our storage, then delete them at Zoom (D-16). Auth is a JWT
 * signed with the Video SDK *API* key/secret (not the SDK key used for join tokens).
 */
final class ZoomRecordings
{
    private const BASE = 'https://api.zoom.us/v2/videosdk';

    /** @param Closure(): int $clock */
    public function __construct(private readonly Config $config, private readonly HttpClient $http, private readonly Closure $clock) {}

    public function files(string $sessionId): array
    {
        return $this->json('GET', '/sessions/'.self::sessionPath($sessionId).'/recordings')['recording_files'] ?? [];
    }

    /** Sessions with recordings between two dates (UTC, Y-m-d). */
    public function sessions(string $from, string $to): array
    {
        return $this->json('GET', '/recordings?'.http_build_query(['from' => $from, 'to' => $to, 'page_size' => 50]))['sessions'] ?? [];
    }

    /** Writes the file to $target; returns the bytes written. */
    public function download(array $file, string $target): int
    {
        [$status, $body] = $this->http->request('GET', (string) $file['download_url'], $this->auth());
        if ($status !== 200) {
            throw new ApiError(502, 'provider_error', ['provider' => 'zoom', 'status' => $status]);
        }
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0700, true);
        }

        return (int) file_put_contents($target, $body);
    }

    /** `action=delete` removes permanently; the default would only move it to a 30-day trash. */
    public function delete(string $sessionId): void
    {
        $this->json('DELETE', '/sessions/'.self::sessionPath($sessionId).'/recordings?action=delete');
    }

    /** Zoom: a session UUID that starts with "/" or contains "//" must be double-encoded. */
    private static function sessionPath(string $id): string
    {
        return str_starts_with($id, '/') || str_contains($id, '//') ? rawurlencode(rawurlencode($id)) : rawurlencode($id);
    }

    private function json(string $method, string $path): array
    {
        [$status, $body] = $this->http->request($method, self::BASE.$path, $this->auth());
        if ($status < 200 || $status >= 300) {
            throw new ApiError(502, 'provider_error', ['provider' => 'zoom', 'status' => $status, 'body' => mb_substr($body, 0, 300)]);
        }

        return $body === '' ? [] : (json_decode($body, true) ?: []);
    }

    private function auth(): array
    {
        if ($this->config->zoomApiKey === '' || $this->config->zoomApiSecret === '') {
            throw new ApiError(503, 'provider_not_configured', ['provider' => 'zoom api']);
        }
        $now = ($this->clock)();

        return ['Authorization' => 'Bearer '.Jwt::encode(['iss' => $this->config->zoomApiKey, 'iat' => $now, 'exp' => $now + 300], $this->config->zoomApiSecret)];
    }
}
