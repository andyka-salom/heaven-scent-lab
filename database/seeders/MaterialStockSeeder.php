<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Material;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class MaterialStockSeeder extends Seeder
{
    public function run(StockService $stockService): void
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

        // Target materials to seed stock
        $materialCodes = [
            'MAT-ALCOHOL' => 10000.0, // 10,000 ml
            'MAT-OIL' => 5000.0,      // 5,000 ml
        ];

        foreach ($materialCodes as $code => $qty) {
            $material = Material::where('code', $code)->first();
            if ($material) {
                // Get current balance
                $currentBalance = $stockService->getBalance($warehouse->id, $material->id);
                
                // Only seed if current balance is 0 to make it idempotent
                if ($currentBalance == 0) {
                    $stockService->move(
                        $warehouse->id,
                        $material->id,
                        'in',
                        $qty,
                        'Init',
                        null,
                        "Initial stock seeding for {$material->name}"
                    );
                    $this->command->info("Seeded {$qty} {$material->unit} of stock for {$material->name}.");
                } else {
                    $this->command->info("Stock for {$material->name} already exists ({$currentBalance} {$material->unit}). Skipping.");
                }
            } else {
                $this->command->warn("Material with code {$code} not found.");
            }
        }
    }
}
