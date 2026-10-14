<?php

declare(strict_types=1);

namespace Spike\FlutterWeb\Http;

final readonly class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public int $status,
        public string $body = '',
        public array $headers = [],
    ) {}

    public static function json(int $status, array $data): self
    {
        return new self(
            $status,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'no-store'],
        );
    }

    /** @param array<string, string> $headers */
    public static function html(string $body, array $headers = []): self
    {
        return new self(200, $body, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store'] + $headers);
    }

    /** @param array<string, string> $headers added, replacing existing names */
    public function with(array $headers): self
    {
        return new self($this->status, $this->body, $headers + $this->headers);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name.': '.$value);
        }
        echo $this->body;
    }
}
