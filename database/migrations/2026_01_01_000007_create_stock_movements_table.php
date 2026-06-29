<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // in | out | adjustment
            $table->decimal('quantity', 14, 3); // always positive
            $table->decimal('balance_after', 14, 3);
            $table->string('reference_type', 120)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('notes', 255)->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['material_id', 'warehouse_id'], 'idx_stock_movements_mat');
            $table->index(['reference_type', 'reference_id'], 'idx_stock_movements_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
