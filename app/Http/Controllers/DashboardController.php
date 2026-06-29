<?php

namespace App\Http\Controllers;

use App\Models\MaterialStock;
use App\Models\ProductionBatch;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $activeBatches = ProductionBatch::whereIn('status', ['draft', 'released', 'in_progress'])->count();

        // Single query for all completed batch statistics
        $stats = \Illuminate\Support\Facades\DB::table('production_batches')
            ->join('production_batch_products', 'production_batches.id', '=', 'production_batch_products.production_batch_id')
            ->where('production_batches.status', 'completed')
            ->selectRaw("
                COALESCE(SUM(production_batch_products.good_qty), 0) as total_good,
                COALESCE(SUM(production_batch_products.defect_qty), 0) as total_defect,
                AVG(CASE WHEN production_batch_products.planned_qty > 0 THEN (production_batch_products.good_qty::decimal / production_batch_products.planned_qty) * 100 END) as avg_yield
            ")
            ->first();

        $totalGood = (float) $stats->total_good;
        $totalDefect = (float) $stats->total_defect;
        $avgYield = round($stats->avg_yield ?? 0, 1);
        $defectRate = ($totalGood + $totalDefect) > 0
            ? round($totalDefect / ($totalGood + $totalDefect) * 100, 1)
            : 0;

        $lowStockCount = MaterialStock::where('min_alert', '>', 0)
            ->whereRaw('quantity <= min_alert')
            ->count();

        // Single chart query: get both good and defect in one GROUP BY
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $chartRows = \Illuminate\Support\Facades\DB::table('production_batches')
            ->join('production_batch_products', 'production_batches.id', '=', 'production_batch_products.production_batch_id')
            ->whereBetween('production_batches.production_date', [$startDate, $endDate])
            ->selectRaw("DATE(production_batches.production_date) as date, COALESCE(SUM(production_batch_products.good_qty), 0) as good, COALESCE(SUM(production_batch_products.defect_qty), 0) as defect")
            ->groupByRaw('DATE(production_batches.production_date)')
            ->get()
            ->keyBy('date');

        // Build arrays for the last 7 days (fill zeros for missing dates)
        $dates = [];
        $goodData = [];
        $defectData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dates[] = Carbon::parse($date)->format('d/m');
            $goodData[] = (int) ($chartRows[$date]->good ?? 0);
            $defectData[] = (int) ($chartRows[$date]->defect ?? 0);
        }

        // Recent batches with eager loading to prevent N+1
        $recentBatches = ProductionBatch::with(['products.product:id,full_name', 'warehouse:id,name', 'creator:id,name'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'activeBatches',
            'avgYield',
            'defectRate',
            'lowStockCount',
            'dates',
            'goodData',
            'defectData',
            'recentBatches'
        ));
    }
}
