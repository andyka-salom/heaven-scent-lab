<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BomImportService
{
    /**
     * Import BOM data from CSV rows.
     *
     * @param array $rows CSV rows (header already stripped)
     * @return array{imported: int, errors: array<string>}
     */
    public function import(array $rows): array
    {
        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$imported, &$errors) {
            $currentItem = null;
            $currentVariant = null;
            $currentBom = null;

            // Pre-load all materials indexed by code and name for O(1) lookup
            $materialsByCode = Material::all()->keyBy('code');
            $materialsByName = Material::all()->keyBy('name');

            foreach ($rows as $i => $row) {
                if (count($row) < 3) {
                    continue;
                }

                // Forward-fill item/variant columns
                $itemName = !empty(trim($row[0])) ? trim($row[0]) : $currentItem;
                $variantName = !empty(trim($row[1])) ? trim($row[1]) : $currentVariant;
                $materialCode = trim($row[2] ?? '');
                $quantity = floatval(str_replace(',', '.', $row[3] ?? '0'));

                if (!$itemName || !$variantName) {
                    continue;
                }

                $currentItem = $itemName;
                $currentVariant = $variantName;

                try {
                    // Find or create product
                    $product = Product::firstOrCreate(
                        ['item_name' => $itemName, 'variant_name' => $variantName],
                        [
                            'sku' => 'IMP-' . strtoupper(substr(md5($itemName . $variantName), 0, 8)),
                            'full_name' => "{$itemName} / {$variantName}",
                        ]
                    );

                    // Find material by code first, then by name
                    $material = $materialsByCode->get($materialCode)
                        ?? $materialsByName->get($materialCode);

                    if (!$material) {
                        $errors[] = "Baris " . ($i + 2) . ": Bahan '{$materialCode}' tidak ditemukan";
                        continue;
                    }

                    // Get/create active BOM (only query when product changes)
                    if (!$currentBom || $currentBom->product_id !== $product->id) {
                        $currentBom = Bom::firstOrCreate(
                            ['product_id' => $product->id, 'is_active' => true],
                            ['version' => 1]
                        );
                    }

                    BomItem::updateOrCreate(
                        ['bom_id' => $currentBom->id, 'material_id' => $material->id],
                        ['quantity' => $quantity, 'unit' => $material->unit]
                    );

                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Baris " . ($i + 2) . ": " . $e->getMessage();
                    Log::warning('BOM import row failed', ['row' => $i + 2, 'error' => $e->getMessage()]);
                }
            }
        });

        return ['imported' => $imported, 'errors' => $errors];
    }
}
