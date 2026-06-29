<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('planned_qty');
            $table->integer('good_qty')->default(0);
            $table->integer('defect_qty')->default(0);
            $table->timestamps();
        });

        // Data migration
        $batches = DB::table('production_batches')->get();
        foreach ($batches as $batch) {
            DB::table('production_batch_products')->insert([
                'production_batch_id' => $batch->id,
                'product_id' => $batch->product_id,
                'planned_qty' => $batch->planned_qty,
                'good_qty' => $batch->good_qty,
                'defect_qty' => $batch->defect_qty,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('batch_outputs', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
        });
        
        $outputs = DB::table('batch_outputs')->get();
        foreach ($outputs as $output) {
            $batch = DB::table('production_batches')->where('id', $output->production_batch_id)->first();
            if ($batch) {
                DB::table('batch_outputs')->where('id', $output->id)->update(['product_id' => $batch->product_id]);
            }
        }
        
        Schema::table('batch_defects', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
        });
        
        $defects = DB::table('batch_defects')->get();
        foreach ($defects as $defect) {
            $batch = DB::table('production_batches')->where('id', $defect->production_batch_id)->first();
            if ($batch) {
                DB::table('batch_defects')->where('id', $defect->id)->update(['product_id' => $batch->product_id]);
            }
        }

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex('idx_batches_product');
            $table->dropColumn(['product_id', 'planned_qty', 'good_qty', 'defect_qty']);
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('planned_qty')->default(0);
            $table->integer('good_qty')->default(0);
            $table->integer('defect_qty')->default(0);
            $table->index('product_id', 'idx_batches_product');
        });

        // Data restore (approximate, gets the first product for the batch)
        $batchProducts = DB::table('production_batch_products')->get();
        foreach ($batchProducts as $bp) {
            DB::table('production_batches')
                ->where('id', $bp->production_batch_id)
                ->update([
                    'product_id' => $bp->product_id,
                    'planned_qty' => $bp->planned_qty,
                    'good_qty' => $bp->good_qty,
                    'defect_qty' => $bp->defect_qty,
                ]);
        }

        Schema::table('batch_outputs', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('batch_defects', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::dropIfExists('production_batch_products');
    }
};
