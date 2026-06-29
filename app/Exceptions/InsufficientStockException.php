<?php

namespace App\Exceptions;

/**
 * Thrown when stock is insufficient for a requested operation.
 */
class InsufficientStockException extends DomainException
{
    /** @var array<int, array{material: string, need: float, have: float, short: float}> */
    protected array $shortages;

    public function __construct(array $shortages)
    {
        $this->shortages = $shortages;

        $details = collect($shortages)
            ->map(fn ($s) => "{$s['material']} (butuh: {$s['need']}, tersedia: {$s['have']})")
            ->implode(', ');

        parent::__construct("Stok tidak mencukupi: {$details}");
    }

    public function getShortages(): array
    {
        return $this->shortages;
    }
}
