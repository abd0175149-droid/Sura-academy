<?php

declare(strict_types=1);

namespace Spike\Video;

/** Outgoing calls to the providers; the self-tests swap in a recording fake. */
interface HttpClient
{
    /**
     * @param array<string, string> $headers
     * @return array{0: int, 1: string} [status, body]
     */
    public function request(string $method, string $url, array $headers, ?string $body = null): array;
}
