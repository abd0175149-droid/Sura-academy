<?php

declare(strict_types=1);

namespace Spike\FlutterWeb\Http;

use JsonException;
use Spike\FlutterWeb\ApiError;

final readonly class Request
{
    /**
     * @param  array<string, string>  $query
     * @param  array<string, string>  $headers  lower-case names
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $headers,
        public string $body,
    ) {}

    /** @param array<string, string> $headers */
    public static function make(string $method, string $uri, array $headers = [], string $body = ''): self
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        return new self(
            $method,
            rawurldecode((string) parse_url($uri, PHP_URL_PATH)),
            array_filter($query, 'is_string'),
            array_change_key_case($headers, CASE_LOWER),
            $body,
        );
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[(string) $name] = (string) $value;
        }

        return self::make(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $headers,
            (string) file_get_contents('php://input', length: 9 * 1024 * 1024),
        );
    }

    public function header(string $name): string
    {
        return $this->headers[strtolower($name)] ?? '';
    }

    public function q(string $name): string
    {
        return $this->query[$name] ?? '';
    }

    /** The JSON object body, or a 422 `validation_failed`. */
    public function json(): array
    {
        try {
            $data = json_decode($this->body, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiError(422, 'validation_failed', ['body' => 'invalid JSON']);
        }
        if (! is_array($data)) {
            throw new ApiError(422, 'validation_failed', ['body' => 'expected a JSON object']);
        }

        return $data;
    }
}
