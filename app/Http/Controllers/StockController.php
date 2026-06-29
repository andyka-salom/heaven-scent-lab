<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustRequest;
use App\Http\Requests\StockAlertRequest;
use App\Http\Requests\StockInRequest;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $this->authorize('stock.view');
        $warehouses = Warehouse::active()->pluck('name', 'id');
        $materials = Material::active()->pluck('name', 'id');
        return view('stocks.index', compact('warehouses', 'materials'));
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('stock.view');

        $query = MaterialStock::query()
            ->select('material_stocks.*')
            ->with(['material:id,code,name,type,unit', 'warehouse:id,name'])
            ->when($request->warehouse_id, fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->material_id, fn ($q) => $q->where('material_id', $request->material_id));

        return DataTables::eloquent($query)
            ->addColumn('material_code', fn ($s) => $s->material?->code ?? '-')
            ->addColumn('material_name', fn ($s) => $s->material?->name ?? '-')
            ->addColumn('warehouse_name', fn ($s) => $s->warehouse?->name ?? '-')
            ->addColumn('unit', fn ($s) => $s->material?->unit ?? '-')
            ->addColumn('low_stock', fn ($s) => $s->isLowStock())
            ->editColumn('quantity', fn ($s) => number_format($s->quantity, 1, ',', '.'))
            ->editColumn('min_alert', fn ($s) => number_format($s->min_alert, 1, ',', '.'))
            ->addColumn('action', fn ($s) => view('stocks._actions', ['s' => $s])->render())
            ->rawColumns(['action'])
            ->toJson();
    }

    public function stockIn(StockInRequest $request)
    {
        $this->authorize('stock.in');

        $this->stockService->move(
            $request->warehouse_id,
            $request->material_id,
            'in',
            $request->quantity,
            'manual',
            null,
            $request->notes
        );

        return back()->with('success', 'Stok masuk berhasil dicatat.');
    }

    public function adjust(StockAdjustRequest $request)
    {
        $this->authorize('stock.adjust');

        $type = $request->quantity >= 0 ? 'in' : 'out';
        $qty = abs($request->quantity);

        $this->stockService->move(
            $request->warehouse_id,
            $request->material_id,
            $type,
            $qty,
            'adjustment',
            null,
            $request->notes
        );

        return back()->with('success', 'Penyesuaian stok berhasil dicatat.');
    }

    public function setAlert(StockAlertRequest $request)
    {
        $this->authorize('stock.set_alert');

        MaterialStock::updateOrCreate(
            ['warehouse_id' => $request->warehouse_id, 'material_id' => $request->material_id],
            ['min_alert' => $request->min_alert]
        );

        return back()->with('success', 'Ambang peringatan stok berhasil diperbarui.');
    }

    public function ledger(Material $material)
    {
        $this->authorize('stock.ledger.view');
        $warehouses = Warehouse::active()->pluck('name', 'id');
        return view('stocks.ledger', compact('material', 'warehouses'));
    }

    public function ledgerData(Request $request, Material $material): JsonResponse
    {
        $this->authorize('stock.ledger.view');

        $query = StockMovement::query()
            ->select('stock_movements.*')
            ->where('material_id', $material->id)
            ->with(['warehouse:id,name', 'user:id,name'])
            ->when($request->warehouse_id, fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->from, fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at');

        return DataTables::eloquent($query)
            ->editColumn('created_at', fn ($m) => $m->created_at?->format('d/m/Y H:i'))
            ->editColumn('quantity', fn ($m) => number_format($m->quantity, 1, ',', '.'))
            ->editColumn('balance_after', fn ($m) => number_format($m->balance_after, 1, ',', '.'))
            ->addColumn('warehouse_name', fn ($m) => $m->warehouse?->name ?? '-')
            ->addColumn('user_name', fn ($m) => $m->user?->name ?? '-')
            ->addColumn('type_label', fn ($m) => $m->type_badge)
            ->toJson();
    }
}
