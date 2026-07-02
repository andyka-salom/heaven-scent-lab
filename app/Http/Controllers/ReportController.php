<?php

namespace App\Http\Controllers;

use App\Models\BatchDefect;
use App\Models\BatchMaterial;
use App\Models\BatchMaterialAddition;
use App\Models\MaterialStock;
use App\Models\ProductionBatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function production(Request $request)
    {
        $this->authorize('report.view');

        $from = $this->parseDate($request->input('from'), Carbon::now()->startOfMonth()->format('Y-m-d'));
        $to = $this->parseDate($request->input('to'), Carbon::now()->format('Y-m-d'));

        // DB-level aggregation instead of loading all records into memory
        $summary = DB::table('production_batches')
            ->join('production_batch_products', 'production_batches.id', '=', 'production_batch_products.production_batch_id')
            ->whereBetween('production_batches.production_date', [$from, $to])
            ->selectRaw("
                COUNT(DISTINCT production_batches.id) as total_batches,
                COALESCE(SUM(production_batch_products.good_qty), 0) as total_good,
                COALESCE(SUM(production_batch_products.defect_qty), 0) as total_defect,
                AVG(CASE WHEN production_batch_products.planned_qty > 0 THEN (production_batch_products.good_qty::decimal / production_batch_products.planned_qty) * 100 END) as avg_yield
            ")
            ->first();

        // Paginated batch list with eager loading (prevents N+1)
        $batches = ProductionBatch::with(['products.product:id,sku,full_name', 'warehouse:id,name'])
            ->whereBetween('production_date', [$from, $to])
            ->orderByDesc('production_date')
            ->get();

        $summaryData = [
            'total_batches' => (int) $summary->total_batches,
            'total_good' => (float) $summary->total_good,
            'total_defect' => (float) $summary->total_defect,
            'avg_yield' => round($summary->avg_yield ?? 0, 1),
        ];

        return view('reports.production', compact('batches', 'summaryData', 'from', 'to'));
    }

    public function material(Request $request)
    {
        $this->authorize('report.view');

        $from = $this->parseDate($request->input('from'), Carbon::now()->startOfMonth()->format('Y-m-d'));
        $to = $this->parseDate($request->input('to'), Carbon::now()->format('Y-m-d'));

        // DB-level aggregation: issued materials from batch_materials
        $issuedUsage = BatchMaterial::query()
            ->join('production_batches', 'batch_materials.production_batch_id', '=', 'production_batches.id')
            ->join('materials', 'batch_materials.material_id', '=', 'materials.id')
            ->whereBetween('production_batches.production_date', [$from, $to])
            ->select(
                'materials.id as material_id',
                'materials.code',
                'materials.name',
                'materials.unit',
                DB::raw('COALESCE(SUM(batch_materials.issued_qty), 0) as issued'),
                DB::raw('0 as additions')
            )
            ->groupBy('materials.id', 'materials.code', 'materials.name', 'materials.unit')
            ->get()
            ->keyBy('material_id');

        // DB-level aggregation: additional materials from batch_material_additions
        $additionUsage = BatchMaterialAddition::query()
            ->join('production_batches', 'batch_material_additions.production_batch_id', '=', 'production_batches.id')
            ->join('materials', 'batch_material_additions.material_id', '=', 'materials.id')
            ->whereBetween('production_batches.production_date', [$from, $to])
            ->where('batch_material_additions.type', 'topup')
            ->select(
                'materials.id as material_id',
                'materials.code',
                'materials.name',
                'materials.unit',
                DB::raw('0 as issued'),
                DB::raw('COALESCE(SUM(batch_material_additions.quantity), 0) as additions')
            )
            ->groupBy('materials.id', 'materials.code', 'materials.name', 'materials.unit')
            ->get()
            ->keyBy('material_id');

        // Merge both datasets correctly by key to avoid duplicates when keys are numeric
        foreach ($additionUsage as $key => $add) {
            if ($issuedUsage->has($key)) {
                $issuedUsage->get($key)->additions = $add->additions;
            } else {
                $issuedUsage->put($key, $add);
            }
        }
        $usage = $issuedUsage->values();

        return view('reports.material', compact('usage', 'from', 'to'));
    }

    public function defect(Request $request)
    {
        $this->authorize('report.view');

        $from = $this->parseDate($request->input('from'), Carbon::now()->startOfMonth()->format('Y-m-d'));
        $to = $this->parseDate($request->input('to'), Carbon::now()->format('Y-m-d'));

        // Single efficient query with eager loading
        $defects = BatchDefect::with(['batch', 'product:id,full_name'])
            ->whereHas('batch', fn ($q) => $q->whereBetween('production_date', [$from, $to]))
            ->orderByDesc('created_at')
            ->get();

        // Group by reason using the collection (already loaded)
        $byReason = $defects->groupBy('reason')->map(fn ($group) => [
            'label' => $group->first()->reason_label,
            'total' => $group->sum('defect_qty'),
            'count' => $group->count(),
        ]);

        // Group by product name
        $byProduct = $defects->groupBy(fn ($d) => $d->product?->full_name ?? 'Unknown')
            ->map(fn ($group) => [
                'total' => $group->sum('defect_qty'),
                'count' => $group->count(),
            ]);

        return view('reports.defect', compact('defects', 'byReason', 'byProduct', 'from', 'to'));
    }

    public function lowStock()
    {
        $this->authorize('report.view');
        return view('reports.low-stock');
    }

    public function lowStockData()
    {
        $this->authorize('report.view');

        $query = MaterialStock::with(['material:id,code,name,unit', 'warehouse:id,name'])
            ->where('min_alert', '>', 0)
            ->whereRaw('quantity <= min_alert')
            ->select('material_stocks.*');

        return \Yajra\DataTables\Facades\DataTables::eloquent($query)
            ->addColumn('material_code', fn ($s) => '<span class="font-mono text-xs text-gray-600">' . $s->material->code . '</span>')
            ->addColumn('material_name', fn ($s) => '<span class="font-medium text-gray-900">' . $s->material->name . '</span>')
            ->addColumn('warehouse_name', fn ($s) => '<span class="text-gray-600">' . $s->warehouse->name . '</span>')
            ->editColumn('quantity', fn ($s) => '<span class="font-semibold text-red-600">' . number_format($s->quantity, 1, ',', '.') . '</span>')
            ->editColumn('min_alert', fn ($s) => '<span class="text-gray-600 text-right w-full block">' . number_format($s->min_alert, 1, ',', '.') . '</span>')
            ->addColumn('difference', fn ($s) => '<span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">-' . number_format($s->min_alert - $s->quantity, 1, ',', '.') . '</span>')
            ->addColumn('unit', fn ($s) => '<span class="text-gray-500">' . $s->material->unit . '</span>')
            ->rawColumns(['material_code', 'material_name', 'warehouse_name', 'quantity', 'min_alert', 'difference', 'unit'])
            ->toJson();
    }

    private function parseDate(?string $date, string $default): string
    {
        if (empty($date)) {
            return $default;
        }
        
        try {
            if (str_contains($date, '/')) {
                return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
            }
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $default;
        }
    }
}
