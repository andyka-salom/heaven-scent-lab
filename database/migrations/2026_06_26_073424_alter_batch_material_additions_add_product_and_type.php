<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_material_additions', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('production_batch_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->default('topup')->after('material_id'); // 'topup' or 'defect'
        });
    }

    public function down(): void
    {
        Schema::table('batch_material_additions', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'type']);
        });
    }
};
