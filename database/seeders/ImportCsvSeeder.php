<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ImportCsvSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('list product varian dan bom.csv');
        if (!file_exists($csvPath)) {
            $this->command->error("File CSV tidak ditemukan: {$csvPath}");
            return;
        }

        // Pastikan ada gudang default
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'Gudang Utama'],
            ['code' => 'WH-01', 'is_active' => true]
        );

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $header = array_shift($lines); // skip header

        $currentProduct = null;
        $currentBom = null;

        $this->command->info("Memulai proses import CSV...");
        
        $countProducts = 0;
        $countMaterials = 0;
        $countBomItems = 0;

        foreach ($lines as $index => $line) {
            $cols = explode(';', $line);
            if (count($cols) < 5) continue;

            $itemName = trim($cols[0]);
            $variantName = trim($cols[1]);
            $ingredientName = trim($cols[2]);
            $quantity = (float) str_replace(',', '.', trim($cols[3]));
            $unitRaw = strtolower(trim($cols[4]));
            
            // Map units
            $unit = 'pcs';
            if (str_contains($unitRaw, 'ml') || str_contains($unitRaw, 'millilitre')) {
                $unit = 'ml';
            }

            if (!empty($itemName) || !empty($variantName)) {
                // Item/Varian baru
                $skuBase = strtoupper(Str::slug($itemName . ' ' . $variantName, ''));
                $sku = substr($skuBase, 0, 15);
                
                // Cek agar SKU unik jika ternyata namanya panjang dan sama depannya
                $existingProduct = Product::where('item_name', $itemName)
                                          ->where('variant_name', $variantName)
                                          ->first();
                                          
                if (!$existingProduct) {
                    while(Product::where('sku', $sku)->exists()) {
                        $sku = substr($skuBase, 0, 10) . rand(100, 999);
                    }
                }

                $currentProduct = Product::firstOrCreate(
                    [
                        'item_name' => $itemName,
                        'variant_name' => $variantName,
                    ],
                    [
                        'sku' => $existingProduct ? $existingProduct->sku : $sku,
                        'full_name' => $itemName . ' / ' . $variantName,
                        'unit' => 'pcs',
                        'default_warehouse_id' => $warehouse->id,
                        'is_active' => true,
                    ]
                );
                
                if ($currentProduct->wasRecentlyCreated) {
                    $countProducts++;
                }

                // Buat BOM
                $currentBom = Bom::firstOrCreate(
                    [
                        'product_id' => $currentProduct->id,
                        'version' => 1,
                    ],
                    [
                        'is_active' => true,
                        'notes' => 'Imported from CSV',
                    ]
                );
            }

            if (!$currentProduct || !$currentBom) {
                continue;
            }

            if (!empty($ingredientName)) {
                // Tentukan tipe bahan
                $type = 'other';
                $nameLower = strtolower($ingredientName);
                if (str_contains($nameLower, 'oil')) $type = 'oil';
                elseif (str_contains($nameLower, 'alkohol') || str_contains($nameLower, 'alcohol')) $type = 'alcohol';
                elseif (str_contains($nameLower, 'box')) $type = 'box';
                elseif (str_contains($nameLower, 'spray')) $type = 'spray';
                elseif (str_contains($nameLower, 'atomizer')) $type = 'atomizer';
                elseif (str_contains($nameLower, 'botol') || str_contains($nameLower, 'bottle')) $type = 'bottle';

                $matCode = 'MAT-' . strtoupper(Str::slug(substr($ingredientName, 0, 5), ''));
                
                // Cari atau buat bahan
                $material = Material::where('name', $ingredientName)->first();
                if (!$material) {
                    while (Material::where('code', $matCode)->exists()) {
                        $matCode = 'MAT-' . strtoupper(Str::slug(substr($ingredientName, 0, 5), '')) . rand(10, 99);
                    }
                    $material = Material::create([
                        'name' => $ingredientName,
                        'code' => $matCode,
                        'type' => $type,
                        'unit' => $unit,
                        'is_active' => true,
                    ]);
                    $countMaterials++;
                }

                // Tambah BOM Item
                $bomItem = BomItem::updateOrCreate(
                    [
                        'bom_id' => $currentBom->id,
                        'material_id' => $material->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'unit' => $unit,
                    ]
                );
                
                if ($bomItem->wasRecentlyCreated) {
                    $countBomItems++;
                }
            }
        }

        $this->command->info("Selesai! {$countProducts} Produk, {$countMaterials} Bahan baru, dan {$countBomItems} item BOM berhasil diimport.");
    }
}
