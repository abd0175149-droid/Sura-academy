<?php

declare(strict_types=1);

// PSR-4 autoloader for the spike (no Composer packages): Spike\Video\X\Y → src/X/Y.php.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Spike\\Video\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
