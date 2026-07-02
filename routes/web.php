<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

// Production module routes
Route::prefix('production')->middleware('auth')->group(function () {

    // Master Data: Products
    Route::middleware('can:product.view')->group(function () {
        Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
        Route::resource('products', ProductController::class)
            ->middleware([
                'store' => 'can:product.create',
                'update' => 'can:product.edit',
                'destroy' => 'can:product.delete',
            ]);
    });

    // Master Data: Materials
    Route::middleware('can:material.view')->group(function () {
        Route::get('materials/data', [MaterialController::class, 'data'])->name('materials.data');
        Route::resource('materials', MaterialController::class)
            ->middleware([
                'store' => 'can:material.create',
                'update' => 'can:material.edit',
                'destroy' => 'can:material.delete',
            ]);
    });

    // Master Data: BOM
    Route::middleware('can:bom.view')->group(function () {
        Route::get('products/{product}/bom', [BomController::class, 'edit'])->name('bom.edit');
        Route::put('products/{product}/bom', [BomController::class, 'update'])
            ->middleware('can:bom.manage')->name('bom.update');
        Route::post('bom/import', [BomController::class, 'import'])
            ->middleware('can:bom.import')->name('bom.import');
        Route::post('products/{product}/bom/duplicate', [BomController::class, 'duplicate'])
            ->middleware('can:bom.manage')->name('bom.duplicate');
    });

    // Warehouses
    Route::middleware('can:warehouse.view')->group(function () {
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('warehouses/create', [WarehouseController::class, 'create'])
            ->middleware('can:warehouse.create')->name('warehouses.create');
        Route::post('warehouses', [WarehouseController::class, 'store'])
            ->middleware('can:warehouse.create')->name('warehouses.store');
        Route::get('warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])
            ->middleware('can:warehouse.edit')->name('warehouses.edit');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])
            ->middleware('can:warehouse.edit')->name('warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
            ->middleware('can:warehouse.delete')->name('warehouses.destroy');
    });

    // Stocks
    Route::middleware('can:stock.view')->group(function () {
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/data', [StockController::class, 'data'])->name('stocks.data');
        Route::post('stocks/in', [StockController::class, 'stockIn'])
            ->middleware('can:stock.in')->name('stocks.in');
        Route::post('stocks/adjust', [StockController::class, 'adjust'])
            ->middleware('can:stock.adjust')->name('stocks.adjust');
        Route::post('stocks/alert', [StockController::class, 'setAlert'])
            ->middleware('can:stock.set_alert')->name('stocks.alert');
        Route::get('stocks/{material}/ledger', [StockController::class, 'ledger'])
            ->middleware('can:stock.ledger.view')->name('stocks.ledger');
        Route::get('stocks/{material}/ledger/data', [StockController::class, 'ledgerData'])
            ->middleware('can:stock.ledger.view')->name('stocks.ledger.data');
    });

    // Batch Produksi
    Route::middleware('can:batch.view')->group(function () {
        Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
        Route::get('batches/data', [BatchController::class, 'data'])->name('batches.data');
        Route::get('batches/create', [BatchController::class, 'create'])
            ->middleware('can:batch.create')->name('batches.create');
        Route::post('batches', [BatchController::class, 'store'])
            ->middleware('can:batch.create')->name('batches.store');
        Route::get('batches/{batch}', [BatchController::class, 'show'])->name('batches.show');

        Route::post('batches/{batch}/release', [BatchController::class, 'release'])
            ->middleware('can:batch.release')->name('batches.release');
        Route::post('batches/{batch}/start', [BatchController::class, 'start'])
            ->middleware('can:batch.start')->name('batches.start');
        Route::post('batches/{batch}/output', [BatchController::class, 'recordOutput'])
            ->middleware('can:batch.record_output')->name('batches.output');
        Route::post('batches/{batch}/defect', [BatchController::class, 'recordDefect'])
            ->middleware('can:batch.record_defect')->name('batches.defect');
        Route::post('batches/{batch}/material', [BatchController::class, 'addMaterial'])
            ->middleware('can:batch.topup')->name('batches.material');
        Route::post('batches/{batch}/complete', [BatchController::class, 'complete'])
            ->middleware('can:batch.complete')->name('batches.complete');
        Route::post('batches/{batch}/cancel', [BatchController::class, 'cancel'])
            ->middleware('can:batch.cancel')->name('batches.cancel');
    });

    // Reports
    Route::middleware('can:report.view')->group(function () {
        Route::get('reports/production', [ReportController::class, 'production'])->name('reports.production');
        Route::get('reports/material', [ReportController::class, 'material'])->name('reports.material');
        Route::get('reports/defect', [ReportController::class, 'defect'])->name('reports.defect');
        Route::get('reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
        Route::get('reports/low-stock/data', [ReportController::class, 'lowStockData'])->name('reports.low-stock.data');
    });

    // Admin: Users
    Route::middleware('can:user.manage')->group(function () {
        Route::get('users/data', [UserController::class, 'data'])->name('users.data');
        Route::resource('users', UserController::class);
    });
});

Route::get('/test-logo', function() {
    $pngExists = file_exists(public_path('Logo HS - black.png'));
    $commit = shell_exec('git log -n 1 --oneline');
    return response()->json([
        'logo_png_exists' => $pngExists,
        'git_commit_on_server' => $commit,
        'blade_has_img' => str_contains(file_get_contents(resource_path('views/layouts/app.blade.php')), 'Logo HS - black.png'),
    ]);
});

Route::get('/test-batch-data', [\App\Http\Controllers\BatchController::class, 'data']);
