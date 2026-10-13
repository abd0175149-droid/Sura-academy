<?php

declare(strict_types=1);

namespace Spike\Video\Http;

use JsonException;
use Spike\Video\ApiError;

final readonly class Request
{
    /** @param array<string, string> $headers lower-case names */
    public function __construct(
        public string $method,
        public string $path,
        public array $headers,
        public string $body,
    ) {}

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[strtolower((string) $name)] = (string) $value;
        }

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH),
            $headers,
            (string) file_get_contents('php://input'),
        );
    }

    public function header(string $name): string
    {
        return $this->headers[strtolower($name)] ?? '';
    }

    /** The JSON object body, or a 422 `validation_failed`. */
    public function json(): array
    {
        try {
            $data = json_decode($this->body, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiError(422, 'validation_failed', ['body' => 'invalid JSON']);
        }
        if (! is_array($data)) {
            throw new ApiError(422, 'validation_failed', ['body' => 'expected a JSON object']);
        }

        return $data;
    }
}
