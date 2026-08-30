<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class SorobanRpcException extends RuntimeException
{
    public function __construct(
        string $message,
        private bool $retryable = false,
        private ?int $httpStatus = null,
        private ?int $rpcCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $httpStatus ?? $rpcCode ?? 0, $previous);
    }

    public static function forHttpStatus(int $status): self
    {
        return new self(
            "Soroban RPC HTTP {$status} response.",
            $status === 429 || ($status >= 500 && $status <= 599),
            $status
        );
    }

    public static function forRpcError(string $message, ?int $code = null): self
    {
        $retryable = $code !== null && $code <= -32000 && $code >= -32099;

        return new self($message, $retryable, null, $code);
    }

    public static function transient(string $message, ?Throwable $previous = null): self
    {
        return new self($message, true, null, null, $previous);
    }

    public function retryable(): bool
    {
        return $this->retryable;
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function rpcCode(): ?int
    {
        return $this->rpcCode;
    }
}
