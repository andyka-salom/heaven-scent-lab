<?php

namespace App\Exceptions;

/**
 * Thrown when batch output exceeds planned quantity.
 */
class OutputExceedsPlannedException extends DomainException
{
    public function __construct(float $totalRecorded, float $requested, float $planned)
    {
        parent::__construct(
            "Total output ({$totalRecorded} + {$requested}) melebihi rencana produksi ({$planned})."
        );
    }
}
