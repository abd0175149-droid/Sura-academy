<?php

declare(strict_types=1);

namespace Spike\Video;

/** Arabic and English strings from token-server/lang/{ar,en}.php; `:n` placeholders. */
final class Lang
{
    private static array $cache = [];

    public static function get(string $locale, string $key, array $replace = []): string
    {
        self::$cache[$locale] ??= require dirname(__DIR__).'/lang/'.$locale.'.php';
        $text = self::$cache[$locale][$key] ?? $key;
        foreach ($replace as $name => $value) {
            $text = str_replace(':'.$name, (string) $value, $text);
        }

        return $text;
    }
}
