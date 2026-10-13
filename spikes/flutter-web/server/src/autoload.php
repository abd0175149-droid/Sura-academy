<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Spike\\FlutterWeb\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }
});
