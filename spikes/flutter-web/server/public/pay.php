<?php

declare(strict_types=1);

/* Fake payment origin router: `php -S 127.0.0.1:27201 public/pay.php` (tools/serve.sh starts it). */

use Spike\FlutterWeb\Config;
use Spike\FlutterWeb\Http\Request;
use Spike\FlutterWeb\PayApp;

$root = dirname(__DIR__);
require $root.'/src/autoload.php';

(new PayApp(Config::fromEnvironment($root)))->handle(Request::fromGlobals())->send();
