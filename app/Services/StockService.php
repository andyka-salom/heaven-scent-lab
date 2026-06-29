<?php

namespace App\Services;

use App\Models\MaterialStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class StockService
{
    private const VALID_TYPES = ['in', 'out', 'adjustment'];

    /**
     * Record a stock movement and update balance.
     *
     * @param int $warehouseId
     * @param int $materialId
     * @param string $type 'in' | 'out' | 'adjustment'
     * @param float $qty Always positive; direction determined by $type
     * @param string|null $refType Morph reference type
     * @param int|null $refId Morph reference ID
     * @param string|null $notes
     *
     * @throws InvalidArgumentException
     */
    public function move(
        int $warehouseId,
        int $materialId,
        string $type,
        float $qty,
        ?string $refType = null,
        ?int $refId = null,
        ?string $notes = null
    ): void {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Invalid stock movement type: '{$type}'. Must be one of: " . implode(', ', self::VALID_TYPES));
        }

        if ($qty <= 0) {
            throw new InvalidArgumentException("Stock movement quantity must be positive, got: {$qty}");
        }

        DB::transaction(function () use ($warehouseId, $materialId, $type, $qty, $refType, $refId, $notes) {
            $stock = MaterialStock::where('warehouse_id', $warehouseId)
                ->where('material_id', $materialId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = MaterialStock::create([
                    'warehouse_id' => $warehouseId,
                    'material_id' => $materialId,
                    'quantity' => 0,
                    'min_alert' => 0,
                ]);
            }

            $delta = $type === 'in' ? $qty : -$qty;
            $stock->quantity += $delta;
            $stock->save();

            StockMovement::create([
                'warehouse_id' => $warehouseId,
                'material_id' => $materialId,
                'type' => $type,
                'quantity' => abs($qty),
                'balance_after' => $stock->quantity,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'notes' => $notes,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            Log::info("Stock movement: {$type} {$qty}", [
                'warehouse_id' => $warehouseId,
                'material_id' => $materialId,
                'new_balance' => $stock->quantity,
                'ref' => $refType . ':' . $refId,
            ]);
        });
    }

    /**
     * Check if stock is sufficient for a given quantity.
     */
    public function hasSufficientStock(int $warehouseId, int $materialId, float $requiredQty): bool
    {
        $balance = $this->getBalance($warehouseId, $materialId);
        return $balance >= $requiredQty;
    }

    /**
     * Get current stock balance.
     */
    public function getBalance(int $warehouseId, int $materialId): float
    {
        return (float) (MaterialStock::where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->value('quantity') ?? 0);
    }
}
