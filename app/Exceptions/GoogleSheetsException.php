<?php

namespace App\Exceptions;

use RuntimeException;

class GoogleSheetsException extends RuntimeException
{
    protected ?string $errorType;
    protected ?int $statusCode;

    public function __construct(string $message, ?string $errorType = null, ?int $statusCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode ?? 0, $previous);
        $this->errorType = $errorType;
        $this->statusCode = $statusCode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public static function rateLimited(string $message = 'Google Sheets API quota exceeded / rate limited'): self
    {
        return new self($message, 'RATE_LIMIT', 429);
    }

    public static function timeout(string $message = 'Google Sheets API connection timeout'): self
    {
        return new self($message, 'TIMEOUT', 408);
    }

    public static function notFound(string $message = 'Spreadsheet or range not found'): self
    {
        return new self($message, 'NOT_FOUND', 404);
    }

    public static function unauthenticated(string $message = 'Service account credentials missing or invalid'): self
    {
        return new self($message, 'UNAUTHENTICATED', 401);
    }

    public static function general(string $message, ?int $statusCode = null): self
    {
        return new self($message, 'API_ERROR', $statusCode);
    }
}
