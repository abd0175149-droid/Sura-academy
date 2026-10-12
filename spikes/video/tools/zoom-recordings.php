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

$zoom = new ZoomRecordings(Config::load($server.'/.env', $server.'/var'), new CurlHttpClient(), static fn (): int => time());
[$command, $arg1, $arg2] = [$argv[1] ?? '', $argv[2] ?? '', $argv[3] ?? ''];

try {
    switch ($command) {
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
