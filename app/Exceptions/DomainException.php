<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base exception for all domain/business logic errors.
 */
class DomainException extends RuntimeException
{
    protected string $flashKey = 'error';

    public function getFlashKey(): string
    {
        return $this->flashKey;
    }
}
