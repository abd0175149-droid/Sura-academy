<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * Append-only JSON Lines log of normalized provider events. Stores pseudonym identities and
 * provider ids only: no display names, IPs or user agents (child-privacy habit, ADR-009).
 */
final class EventLog
{
    public function __construct(private readonly string $file) {}

    public function append(array $event): void
    {
        $dir = dirname($this->file);
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        file_put_contents($this->file, json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND | LOCK_EX);
    }

    public function all(): array
    {
        if (! is_file($this->file)) {
            return [];
        }
        $lines = file($this->file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_values(array_filter(array_map(static fn (string $l): mixed => json_decode($l, true), $lines), 'is_array'));
    }
}
