<?php

namespace App\Services;

use App\Models\ProductionBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BatchNumberGenerator
{
    /**
     * Generate batch number in format: BTH-YYYYMMDD-###
     * Resets daily. Uses lockForUpdate to prevent race conditions.
     *
     * MUST be called inside a DB::transaction().
     */
    public static function generate(): string
    {
        $today = Carbon::now();
        $prefix = 'BTH-' . $today->format('Ymd') . '-';

        $lastBatch = ProductionBatch::whereDate('created_at', $today->toDateString())
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($lastBatch) {
            $lastNumber = (int) substr($lastBatch->batch_number, -3);
            $sequence = $lastNumber + 1;
        }

        return $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
