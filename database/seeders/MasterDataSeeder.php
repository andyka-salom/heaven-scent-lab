<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Material;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::first();
        $warehouseId = $warehouse ? $warehouse->id : null;

        // Seed Products
        $products = [
            [
                'sku' => 'PF-50-INTENSE',
                'item_name' => 'Parfum 50',
                'variant_name' => 'Intense',
                'unit' => 'pcs',
                'default_warehouse_id' => $warehouseId,
                'is_active' => true,
            ],
            [
                'sku' => 'PF-50-REGULAR',
                'item_name' => 'Parfum 50',
                'variant_name' => 'Regular',
                'unit' => 'pcs',
                'default_warehouse_id' => $warehouseId,
                'is_active' => true,
            ],
            [
                'sku' => 'PF-50-VERYINTENSE',
                'item_name' => 'Parfum 50',
                'variant_name' => 'Very Intense',
                'unit' => 'pcs',
                'default_warehouse_id' => $warehouseId,
                'is_active' => true,
            ],
        ];

        foreach ($products as $prodData) {
            Product::firstOrCreate(
                ['sku' => $prodData['sku']],
                [
                    'item_name' => $prodData['item_name'],
                    'variant_name' => $prodData['variant_name'],
                    'unit' => $prodData['unit'],
                    'default_warehouse_id' => $prodData['default_warehouse_id'],
                    'is_active' => $prodData['is_active'],
                ]
            );
        }

        // Seed Materials
        $materials = [
            [
                'code' => 'MAT-ALCOHOL',
                'name' => 'Alkohol',
                'type' => 'alcohol',
                'unit' => 'ml',
                'is_active' => true,
            ],
            [
                'code' => 'MAT-OIL',
                'name' => 'Oil',
                'type' => 'oil',
                'unit' => 'ml',
                'is_active' => true,
            ],
        ];

        foreach ($materials as $matData) {
            Material::firstOrCreate(
                ['code' => $matData['code']],
                [
                    'name' => $matData['name'],
                    'type' => $matData['type'],
                    'unit' => $matData['unit'],
                    'is_active' => $matData['is_active'],
                ]
            );
        }
    }
}
