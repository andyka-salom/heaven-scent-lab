<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DummyDataSeeder extends Seeder
{
    public function run(BatchService $batchService, StockService $stockService): void
    {
        $admin = User::first();
        if (!$admin) {
            $this->command->warn('No user found, please run UserSeeder first.');
            return;
        }

        Auth::login($admin);

        $warehouse = Warehouse::first();
        if (!$warehouse) {
            $this->command->warn('No warehouse found, please run WarehouseSeeder first.');
            return;
        }

        $this->command->info('Adding initial stock to all materials...');
        $materials = Material::all();
        foreach ($materials as $material) {
            $stockService->move(
                $warehouse->id,
                $material->id,
                'in',
                10000,
                'Init',
                null,
                'Initial Stock for testing'
            );
        }

        // (Batch seeding is disabled by request)
        $this->command->info('Dummy data seeding completed!');
    }
}
