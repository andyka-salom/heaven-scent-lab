<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('min_alert', 14, 3)->default(0);
            $table->unique(['warehouse_id', 'material_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_stocks');
    }
};
