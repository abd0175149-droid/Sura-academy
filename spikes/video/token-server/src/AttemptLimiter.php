<?php

declare(strict_types=1);

namespace Spike\Video;

/**
 * Wrong-code throttle for the public tunnel: after $maxFailures wrong access codes from one client
 * within $windowSeconds, every code-protected request from that client gets 429 until the window
 * passes. The client is Cloudflare's CF-Connecting-IP (all tunnel traffic arrives from 127.0.0.1).
 */
final class AttemptLimiter
{
    public function __construct(
        private readonly string $file,
        private readonly int $maxFailures = 20,
        private readonly int $windowSeconds = 600,
    ) {}

    public function isBlocked(string $client, int $now): bool
    {
        return count($this->recent($this->read()[$client] ?? [], $now)) >= $this->maxFailures;
    }

    public function fail(string $client, int $now): void
    {
        $all = $this->read();
        $all[$client] = [...$this->recent($all[$client] ?? [], $now), $now];
        file_put_contents($this->file, json_encode($all), LOCK_EX);
    }

    private function recent(array $times, int $now): array
    {
        return array_values(array_filter($times, fn (int $t): bool => $now - $t < $this->windowSeconds));
    }

    private function read(): array
    {
        $data = is_file($this->file) ? json_decode((string) file_get_contents($this->file), true) : [];

        return is_array($data) ? $data : [];
    }
}
