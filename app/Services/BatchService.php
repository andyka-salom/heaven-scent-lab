<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidBatchStateException;
use App\Exceptions\OutputExceedsPlannedException;
use App\Models\BatchDefect;
use App\Models\BatchMaterial;
use App\Models\BatchMaterialAddition;
use App\Models\BatchOutput;
use App\Models\Bom;
use App\Models\MaterialStock;
use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchService
{
    public function __construct(private StockService $stock) {}

    /**
     * Create a new production batch with BOM explode.
     */
    public function create(array $data): ProductionBatch
    {
        return DB::transaction(function () use ($data) {
            $batch = ProductionBatch::create([
                'batch_number' => BatchNumberGenerator::generate(),
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
                'production_date' => $data['production_date'],
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['products'] as $p) {
                $batch->products()->create([
                    'product_id' => $p['product_id'],
                    'planned_qty' => $p['planned_qty'],
                    'good_qty' => 0,
                    'defect_qty' => 0,
                ]);
            }

            $this->explodeBom($batch, $data['products']);

            Log::info("Batch created: {$batch->batch_number}", ['batch_id' => $batch->id]);

            return $batch;
        });
    }

    /**
     * Release batch: issue materials from stock.
     */
    public function release(ProductionBatch $batch): void
    {
        $this->assertStatus($batch, 'draft');

        $batch->load('materials.material');
        $warehouse = $batch->warehouse;

        if (!$warehouse->allow_negative_stock) {
            $this->validateStockSufficiency($batch);
        }

        DB::transaction(function () use ($batch) {
            foreach ($batch->materials as $bm) {
                $this->stock->move(
                    $batch->warehouse_id,
                    $bm->material_id,
                    'out',
                    $bm->planned_qty,
                    'ProductionBatch',
                    $batch->id,
                    "Issuance batch #{$batch->batch_number}"
                );
                $bm->update(['issued_qty' => $bm->planned_qty]);
            }
            $batch->update(['status' => 'released']);
        });

        Log::info("Batch released: {$batch->batch_number}");
    }

    /**
     * Start production: released -> in_progress.
     */
    public function start(ProductionBatch $batch): void
    {
        $this->assertStatus($batch, 'released');
        $batch->update(['status' => 'in_progress']);

        Log::info("Batch started: {$batch->batch_number}");
    }

    /**
     * Record good output.
     */
    public function recordOutput(ProductionBatch $batch, int $productId, int $qty): void
    {
        $this->assertStatus($batch, 'in_progress');
        $this->assertOutputWithinPlanned($batch, $productId, $qty);

        DB::transaction(function () use ($batch, $productId, $qty) {
            BatchOutput::create([
                'production_batch_id' => $batch->id,
                'product_id' => $productId,
                'good_qty' => $qty,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            $batch->products()->where('product_id', $productId)->increment('good_qty', $qty);
        });

        Log::info("Output recorded: batch {$batch->batch_number}, product {$productId}, qty {$qty}");
    }

    /**
     * Record defect.
     */
    public function recordDefect(ProductionBatch $batch, int $productId, int $qty, string $reason, ?string $notes = null): void
    {
        $this->assertStatus($batch, 'in_progress');
        $this->assertOutputWithinPlanned($batch, $productId, $qty);

        DB::transaction(function () use ($batch, $productId, $qty, $reason, $notes) {
            BatchDefect::create([
                'production_batch_id' => $batch->id,
                'product_id' => $productId,
                'defect_qty' => $qty,
                'reason' => $reason,
                'notes' => $notes,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            $batch->products()->where('product_id', $productId)->increment('defect_qty', $qty);
        });

        Log::info("Defect recorded: batch {$batch->batch_number}, product {$productId}, qty {$qty}, reason: {$reason}");
    }

    /**
     * Top-up material (additional issuance).
     */
    public function addMaterial(ProductionBatch $batch, int $materialId, float $qty, string $reason, ?int $productId = null, string $type = 'topup'): void
    {
        $this->assertStatus($batch, 'in_progress');

        $warehouse = $batch->warehouse;
        if (!$warehouse->allow_negative_stock) {
            $stockQty = $this->stock->getBalance($batch->warehouse_id, $materialId);
            if ($stockQty < $qty) {
                throw new InsufficientStockException([[
                    'material' => 'Material #' . $materialId,
                    'need' => $qty,
                    'have' => $stockQty,
                    'short' => $qty - $stockQty,
                ]]);
            }
        }

        DB::transaction(function () use ($batch, $materialId, $qty, $reason, $productId, $type) {
            $addition = BatchMaterialAddition::create([
                'production_batch_id' => $batch->id,
                'product_id' => $productId,
                'material_id' => $materialId,
                'type' => $type,
                'quantity' => $qty,
                'reason' => $reason,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->stock->move(
                $batch->warehouse_id,
                $materialId,
                'out',
                $qty,
                'BatchMaterialAddition',
                $addition->id,
                ($type === 'defect' ? "Penggantian bahan rusak batch #" : "Top-up batch #") . $batch->batch_number
            );
        });

        Log::info("Material added: batch {$batch->batch_number}, material #{$materialId}, qty {$qty}, type {$type}");
    }

    /**
     * Complete batch with final results.
     */
    public function complete(ProductionBatch $batch, array $results = []): void
    {
        $this->assertStatus($batch, 'in_progress');

        DB::transaction(function () use ($batch, $results) {
            if (!empty($results)) {
                foreach ($results as $res) {
                    $bp = $batch->products()->where('product_id', $res['product_id'])->first();
                    if ($bp) {
                        // The output exceeds planned check is not strictly needed here since this is final entry, 
                        // but we just update the final quantities directly.
                        $bp->update([
                            'good_qty' => $res['good_qty'],
                            'defect_qty' => $res['defect_qty']
                        ]);

                        // Optionally log to BatchOutput and BatchDefect if we want to keep history
                        if ($res['good_qty'] > 0) {
                            BatchOutput::create([
                                'production_batch_id' => $batch->id,
                                'product_id' => $res['product_id'],
                                'good_qty' => $res['good_qty'],
                                'created_by' => auth()->id(),
                                'created_at' => now(),
                            ]);
                        }
                        if ($res['defect_qty'] > 0) {
                            BatchDefect::create([
                                'production_batch_id' => $batch->id,
                                'product_id' => $res['product_id'],
                                'defect_qty' => $res['defect_qty'],
                                'reason' => 'other',
                                'notes' => 'Final input on complete',
                                'created_by' => auth()->id(),
                                'created_at' => now(),
                            ]);
                        }
                    }
                }
            }

            $batch->update([
                'status' => 'completed',
                'closed_at' => now(),
            ]);
        });

        $batch->load('products');
        Log::info("Batch completed: {$batch->batch_number}", [
            'good_qty' => $batch->products->sum('good_qty'),
            'defect_qty' => $batch->products->sum('defect_qty'),
            'yield' => $batch->yield,
        ]);
    }

    /**
     * Cancel batch and return materials to stock.
     */
    public function cancel(ProductionBatch $batch): void
    {
        if (!in_array($batch->status, ['draft', 'released', 'in_progress'])) {
            throw new InvalidBatchStateException($batch->status, 'draft/released/in_progress');
        }

        DB::transaction(function () use ($batch) {
            if (in_array($batch->status, ['released', 'in_progress'])) {
                $batch->load(['materials', 'additions']);
                $this->returnIssuedMaterials($batch);
                $this->returnTopUpMaterials($batch);
            }

            $batch->update([
                'status' => 'cancelled',
                'closed_at' => now(),
            ]);
        });

        Log::info("Batch cancelled: {$batch->batch_number}");
    }

    /**
     * Get material preview with stock availability.
     * Uses batch query for stock balances instead of N+1 per-material queries.
     */
    public function getMaterialPreview(ProductionBatch $batch): array
    {
        $batch->load('materials.material:id,code,name,unit');

        if ($batch->materials->isEmpty()) {
            return [];
        }

        // Single query: load all stock balances for this warehouse at once
        $materialIds = $batch->materials->pluck('material_id')->unique()->values()->all();
        $stockMap = MaterialStock::where('warehouse_id', $batch->warehouse_id)
            ->whereIn('material_id', $materialIds)
            ->pluck('quantity', 'material_id')
            ->map(fn ($qty) => (float) $qty)
            ->toArray();

        return $batch->materials->map(function ($bm) use ($stockMap) {
            $stockQty = $stockMap[$bm->material_id] ?? 0;
            return [
                'material_id' => $bm->material_id,
                'material_name' => $bm->material?->name ?? '-',
                'material_code' => $bm->material?->code ?? '-',
                'unit' => $bm->unit,
                'planned_qty' => (float) $bm->planned_qty,
                'stock_qty' => $stockQty,
                'is_sufficient' => $stockQty >= $bm->planned_qty,
            ];
        })->toArray();
    }

    // ──────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────

    /**
     * Assert batch is in the expected status.
     */
    private function assertStatus(ProductionBatch $batch, string $expected): void
    {
        if ($batch->status !== $expected) {
            throw new InvalidBatchStateException($batch->status, $expected);
        }
    }

    /**
     * Assert that adding $qty won't exceed planned quantity.
     */
    private function assertOutputWithinPlanned(ProductionBatch $batch, int $productId, int $qty): void
    {
        $batchProduct = $batch->products()->where('product_id', $productId)->firstOrFail();
        $totalRecorded = $batchProduct->good_qty + $batchProduct->defect_qty;
        if ($totalRecorded + $qty > $batchProduct->planned_qty) {
            throw new OutputExceedsPlannedException($totalRecorded, $qty, $batchProduct->planned_qty);
        }
    }

    /**
     * Validate stock sufficiency for all batch materials.
     * Uses single query for all stock balances instead of N+1.
     */
    private function validateStockSufficiency(ProductionBatch $batch): void
    {
        $materialIds = $batch->materials->pluck('material_id')->unique()->values()->all();

        // Single query to get all stock balances
        $stockMap = MaterialStock::where('warehouse_id', $batch->warehouse_id)
            ->whereIn('material_id', $materialIds)
            ->pluck('quantity', 'material_id')
            ->map(fn ($qty) => (float) $qty)
            ->toArray();

        $shortages = [];

        foreach ($batch->materials as $bm) {
            $stockQty = $stockMap[$bm->material_id] ?? 0;
            if ($stockQty < $bm->planned_qty) {
                $shortages[] = [
                    'material' => $bm->material?->name ?? "Material #{$bm->material_id}",
                    'need' => (float) $bm->planned_qty,
                    'have' => $stockQty,
                    'short' => $bm->planned_qty - $stockQty,
                ];
            }
        }

        if (!empty($shortages)) {
            throw new InsufficientStockException($shortages);
        }
    }

    /**
     * BOM explode: create batch_materials from active BOM.
     */
    private function explodeBom(ProductionBatch $batch, array $products): void
    {
        $materialReqs = [];

        foreach ($products as $p) {
            $bom = Bom::where('product_id', $p['product_id'])
                ->where('is_active', true)
                ->with('items.material:id,code,name,unit')
                ->first();

            if (!$bom) {
                Log::warning("No active BOM found for product #{$p['product_id']}", ['batch' => $batch->batch_number]);
                continue;
            }

            foreach ($bom->items as $item) {
                $reqQty = $item->quantity * $p['planned_qty'];
                if (!isset($materialReqs[$item->material_id])) {
                    $materialReqs[$item->material_id] = [
                        'planned_qty' => 0,
                        'unit' => $item->unit,
                    ];
                }
                $materialReqs[$item->material_id]['planned_qty'] += $reqQty;
            }
        }

        foreach ($materialReqs as $matId => $req) {
            BatchMaterial::create([
                'production_batch_id' => $batch->id,
                'material_id' => $matId,
                'planned_qty' => $req['planned_qty'],
                'issued_qty' => 0,
                'unit' => $req['unit'],
            ]);
        }
    }

    /**
     * Return issued materials to stock on cancellation.
     */
    private function returnIssuedMaterials(ProductionBatch $batch): void
    {
        foreach ($batch->materials as $bm) {
            if ($bm->issued_qty > 0) {
                $this->stock->move(
                    $batch->warehouse_id,
                    $bm->material_id,
                    'in',
                    $bm->issued_qty,
                    'ProductionBatch',
                    $batch->id,
                    "Return cancel batch #{$batch->batch_number}"
                );
            }
        }
    }

    /**
     * Return top-up additions to stock on cancellation.
     */
    private function returnTopUpMaterials(ProductionBatch $batch): void
    {
        foreach ($batch->additions as $add) {
            $this->stock->move(
                $batch->warehouse_id,
                $add->material_id,
                'in',
                $add->quantity,
                'BatchMaterialAddition',
                $add->id,
                "Return top-up cancel batch #{$batch->batch_number}"
            );
        }
    }
}
