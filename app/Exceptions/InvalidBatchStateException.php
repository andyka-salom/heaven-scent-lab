<?php

namespace App\Exceptions;

/**
 * Thrown when a batch state transition is invalid.
 */
class InvalidBatchStateException extends DomainException
{
    public function __construct(string $currentStatus, string $expectedStatus)
    {
        parent::__construct(
            "Transisi batch tidak valid. Status saat ini: '{$currentStatus}', diharapkan: '{$expectedStatus}'."
        );
    }
}
