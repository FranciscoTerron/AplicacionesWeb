<?php

namespace App\Domain\Errors;

use Exception;
use Throwable;

abstract class DomainError extends Exception
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    abstract public function getErrorCode(): string;

    abstract public function getUserMessage(): string;
}
