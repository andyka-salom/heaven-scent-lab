<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Master
            'product.view', 'product.create', 'product.edit', 'product.delete',
            'material.view', 'material.create', 'material.edit', 'material.delete',
            'bom.view', 'bom.manage', 'bom.import',
            // Gudang
            'warehouse.view', 'warehouse.create', 'warehouse.edit', 'warehouse.delete',
            // Stok
            'stock.view', 'stock.in', 'stock.adjust', 'stock.set_alert', 'stock.ledger.view',
            // Batch
            'batch.view', 'batch.create', 'batch.edit',
            'batch.release', 'batch.start',
            'batch.record_output', 'batch.record_defect', 'batch.topup',
            'batch.complete', 'batch.cancel',
            // Laporan
            'report.view', 'report.export',
            // Admin
            'user.manage', 'role.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Production Manager
        $manager = Role::firstOrCreate(['name' => 'production_manager']);
        $manager->syncPermissions([
            'product.view', 'material.view', 'bom.view', 'bom.manage', 'bom.import',
            'warehouse.view', 'warehouse.create', 'warehouse.edit', 'warehouse.delete',
            'stock.view', 'stock.ledger.view',
            'batch.view', 'batch.create', 'batch.edit', 'batch.release', 'batch.start',
            'batch.record_output', 'batch.record_defect', 'batch.topup',
            'batch.complete', 'batch.cancel',
            'report.view', 'report.export',
        ]);

        // Production Operator
        $operator = Role::firstOrCreate(['name' => 'production_operator']);
        $operator->syncPermissions([
            'product.view', 'material.view', 'bom.view',
            'warehouse.view',
            'stock.view',
            'batch.view', 'batch.create',
            'batch.record_output', 'batch.record_defect', 'batch.topup',
            'report.view',
        ]);

        // Warehouse Staff
        $warehouse = Role::firstOrCreate(['name' => 'warehouse_staff']);
        $warehouse->syncPermissions([
            'material.view', 'warehouse.view', 'stock.view', 'stock.in', 'stock.adjust',
            'stock.set_alert', 'stock.ledger.view',
            'batch.view', 'batch.release',
            'report.view',
        ]);

        // Viewer
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'product.view', 'material.view', 'bom.view',
            'warehouse.view',
            'stock.view', 'stock.ledger.view',
            'batch.view', 'report.view',
        ]);
    }
}
