<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Support;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(string $message, int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->getCode();
    }
}
