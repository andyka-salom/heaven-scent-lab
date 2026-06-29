<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::firstOrCreate(
            ['code' => 'WH-001'],
            [
                'name' => 'Gudang Utama',
                'location' => 'Area Produksi',
                'is_active' => true,
                'allow_negative_stock' => false,
            ]
        );
    }
}
