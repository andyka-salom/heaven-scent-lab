<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 40)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('planned_qty');
            $table->integer('good_qty')->default(0);
            $table->integer('defect_qty')->default(0);
            $table->string('status', 20)->default('draft');
            $table->date('production_date');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_batches_status');
            $table->index('production_date', 'idx_batches_date');
            $table->index('product_id', 'idx_batches_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
