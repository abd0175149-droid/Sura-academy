<?php

declare(strict_types=1);

/*
 * App origin router: `php -S 127.0.0.1:27200 public/index.php` (tools/serve.sh starts it).
 * Settings from the environment (SPIKE_APP_ORIGIN, SPIKE_PAY_ORIGIN, LIVEKIT_KEY_FILE).
 */

use Spike\FlutterWeb\App;
use Spike\FlutterWeb\Config;
use Spike\FlutterWeb\Http\Request;

$root = dirname(__DIR__);
require $root.'/src/autoload.php';

(new App(Config::fromEnvironment($root), static fn (): int => time()))->handle(Request::fromGlobals())->send();
