<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Health;

final class Result
{
    private function __construct(
        public readonly Status $status,
        public readonly string $message,
    ) {}

    /**
     * Create an "ok" result.
     */
    public static function ok(string $message = ''): self
    {
        return new self(Status::Ok, $message);
    }

    /**
     * Create a "warning" result.
     */
    public static function warning(string $message = ''): self
    {
        return new self(Status::Warning, $message);
    }

    /**
     * Create a "failed" result.
     */
    public static function failed(string $message = ''): self
    {
        return new self(Status::Failed, $message);
    }
}
