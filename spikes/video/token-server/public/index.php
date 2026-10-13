<?php

declare(strict_types=1);

/*
 * Router for `php -S 127.0.0.1:27100 public/index.php` (see spikes/video/README.md).
 * Keys: the owner's local key file next to `.env.example`; Claude never reads it.
 */

use Spike\Video\App;
use Spike\Video\AttemptLimiter;
use Spike\Video\Config;
use Spike\Video\CurlHttpClient;
use Spike\Video\EventLog;
use Spike\Video\Http\Request;
use Spike\Video\RoomLocks;

$root = dirname(__DIR__);
require $root.'/src/autoload.php';

$config = Config::load($root.'/.env', $root.'/var');
$app = new App(
    $config,
    new RoomLocks($root.'/var/locks.json'),
    new EventLog($root.'/var/events.jsonl'),
    static fn (): int => time(),
    new CurlHttpClient(),
    metrics: new EventLog($root.'/var/metrics.jsonl'),
    limiter: new AttemptLimiter($root.'/var/attempts.json'),
    staticRoots: [
        '/' => dirname($root).'/app/build/web',
        '/zoom-web/' => dirname($root).'/zoom-web',
        '/downloads/' => dirname($root).'/app/build/app/outputs/flutter-apk',
    ],
);
$app->handle(Request::fromGlobals())->send();
