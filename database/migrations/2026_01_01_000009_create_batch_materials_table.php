<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('planned_qty', 14, 3);
            $table->decimal('issued_qty', 14, 3)->default(0);
            $table->string('unit', 10);
            $table->timestamps();

            $table->index('production_batch_id', 'idx_batch_materials_bat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_materials');
    }
};
