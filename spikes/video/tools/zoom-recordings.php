<?php

declare(strict_types=1);

/*
 * Zoom cloud recordings for the spike (CLS-10, D-16): copy to our storage, then delete at Zoom.
 *
 *   php spikes/video/tools/zoom-recordings.php list [from Y-m-d] [to Y-m-d]
 *   php spikes/video/tools/zoom-recordings.php fetch <session_id>    download into token-server/var/recordings/zoom/
 *   php spikes/video/tools/zoom-recordings.php delete <session_id>   permanent delete at Zoom (action=delete)
 *
 * Prints file names, sizes and SHA-256 only, never keys or download URLs.
 */

use Spike\Video\ApiError;
use Spike\Video\Config;
use Spike\Video\CurlHttpClient;
use Spike\Video\ZoomRecordings;

$server = dirname(__DIR__).'/token-server';
require $server.'/src/autoload.php';

$config = Config::load($server.'/.env', $server.'/var');
$zoom = new ZoomRecordings($config, new CurlHttpClient(), static fn (): int => time());
[$command, $arg1, $arg2] = [$argv[1] ?? '', $argv[2] ?? '', $argv[3] ?? ''];

try {
    switch ($command) {
        case 'check':
            // Which credential kind is configured, and which header style Zoom accepts. Prints
            // set/empty and HTTP status codes only, never a key.
            $key = $config->zoomApiKey;
            printf("api key: %s · api secret: %s · whitespace or quotes in key: %s\n",
                $key === '' ? 'empty' : 'set ('.strlen($key).' chars)',
                $config->zoomApiSecret === '' ? 'empty' : 'set',
                preg_match('/[\s"\']/', $key) === 1 ? 'yes' : 'no');
            // A short fingerprint tells whether the value changed between attempts without showing it.
            $parts = explode('.', $key);
            $claims = count($parts) === 3 ? json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true) : null;
            printf("fingerprint: %s · looks like a JWT: %s%s\n",
                substr(hash('sha256', $key), 0, 8),
                is_array($claims) ? 'yes' : 'no',
                is_array($claims) && isset($claims['exp']) ? ' (expires '.gmdate('Y-m-d H:i', (int) $claims['exp']).' UTC)' : '');
            // What the server itself uses: a key alone as bearer, or a JWT signed with key + secret.
            try {
                $count = count($zoom->sessions(gmdate('Y-m-d', time() - 7 * 86400), gmdate('Y-m-d')));
                printf("server auth (%s): OK, %d session(s) with recordings in the last 7 days\n", $config->zoomApiSecret === '' ? 'key as bearer' : 'JWT from key + secret', $count);
            } catch (ApiError $e) {
                printf("server auth (%s): %s %s\n", $config->zoomApiSecret === '' ? 'key as bearer' : 'JWT from key + secret', $e->errorCode, json_encode($e->details, JSON_UNESCAPED_SLASHES));
            }
            if ($config->zoomApiSecret !== '') {
                break;
            }
            // Key alone: try both header styles on two endpoints to tell a bad key from a bad endpoint.
            $range = http_build_query(['from' => gmdate('Y-m-d', time() - 7 * 86400), 'to' => gmdate('Y-m-d'), 'page_size' => 5]);
            foreach (['/videosdk/sessions', '/videosdk/recordings'] as $path) {
                foreach (['Authorization: Bearer' => ['Authorization' => 'Bearer '.$key], 'x-api-key' => ['x-api-key' => $key]] as $style => $headers) {
                    [$status, $body] = (new CurlHttpClient())->request('GET', 'https://api.zoom.us/v2'.$path.'?'.$range, $headers);
                    $json = json_decode($body, true) ?: [];
                    printf("%-21s %-22s HTTP %d %s %s\n", $path, $style, $status, $json['code'] ?? '', mb_substr((string) ($json['message'] ?? (isset($json['sessions']) ? count($json['sessions']).' session(s)' : '')), 0, 80));
                }
            }
            break;
        case 'list':
            $sessions = $zoom->sessions($arg1 ?: gmdate('Y-m-d', time() - 7 * 86400), $arg2 ?: gmdate('Y-m-d'));
            foreach ($sessions as $s) {
                printf("%s  %s  files=%d  start=%s\n", $s['session_id'] ?? '?', $s['session_name'] ?? '?', count($s['recording_files'] ?? []), $s['start_time'] ?? '?');
            }
            echo count($sessions)." session(s) with recordings\n";
            break;
        case 'fetch':
            foreach ($zoom->files($arg1) as $file) {
                $target = $server.'/var/recordings/zoom/'.preg_replace('/[^A-Za-z0-9_-]/', '_', $arg1).'-'.($file['id'] ?? 'file').'.'.strtolower((string) ($file['file_type'] ?? 'bin'));
                $bytes = $zoom->download($file, $target);
                printf("%s  %d bytes  sha256=%s\n", basename($target), $bytes, hash_file('sha256', $target));
            }
            break;
        case 'delete':
            $zoom->delete($arg1);
            echo "deleted at Zoom: {$arg1}\n";
            try {
                $left = (string) count($zoom->files($arg1));
            } catch (ApiError $e) {
                $left = 'none (HTTP '.($e->details['status'] ?? '?').')';
            }
            echo "files still listed at Zoom: {$left}\n";
            break;
        default:
            fwrite(STDERR, "usage: zoom-recordings.php list [from] [to] | fetch <session_id> | delete <session_id>\n");
            exit(2);
    }
} catch (ApiError $e) {
    fwrite(STDERR, "{$e->errorCode} ".json_encode($e->details, JSON_UNESCAPED_SLASHES)."\n");
    exit(1);
}
