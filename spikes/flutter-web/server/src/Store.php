<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

/** Runtime files under server/var/ (git-ignored): JSON lines and screenshots. */
final class Store
{
    public static function append(string $file, array $record): void
    {
        @mkdir(dirname($file), 0700, true);
        file_put_contents($file, json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** Writes bytes under a server-chosen name in $dir; returns the path. */
    public static function put(string $dir, string $name, string $bytes): string
    {
        @mkdir($dir, 0700, true);
        $path = $dir.'/'.$name;
        file_put_contents($path, $bytes, LOCK_EX);

        return $path;
    }
}
