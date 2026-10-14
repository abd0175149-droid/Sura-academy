<?php

declare(strict_types=1);

namespace Spike\FlutterWeb;

use RuntimeException;

/** An error rendered in the product's shape: {code, message_ar, message_en, details, request_id}. */
final class ApiError extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, public readonly array $details = [])
    {
        parent::__construct($errorCode);
    }
}
