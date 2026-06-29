<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchDefectRequest;
use App\Http\Requests\BatchOutputRequest;
use App\Http\Requests\BatchStoreRequest;
use App\Http\Requests\BatchTopupRequest;
use App\Models\Material;
use App\Models\ProductionBatch;
use App\Models\Warehouse;
use App\Services\BatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BatchController extends Controller
{
    public function __construct(private BatchService $batchService) {}

    public function index()
    {
        $this->authorize('batch.view');
        return view('batches.index');
    }

    public function data(): JsonResponse
    {
        $this->authorize('batch.view');

        $query = ProductionBatch::query()
            ->select('production_batches.*')
            ->with([
                'products.product:id,sku,full_name',
                'warehouse:id,name',
                'creator:id,name',
            ]);

        return DataTables::eloquent($query)
            ->addColumn('product', function ($b) {
                if ($b->products->count() === 1) {
                    return $b->products->first()->product?->full_name ?? '-';
                }
                return $b->products->count() . ' Produk';
            })
            ->addColumn('planned_qty', fn ($b) => $b->products->sum('planned_qty'))
            ->addColumn('good_qty', fn ($b) => $b->products->sum('good_qty'))
            ->addColumn('defect_qty', fn ($b) => $b->products->sum('defect_qty'))
            ->addColumn('warehouse', fn ($b) => $b->warehouse?->name ?? '-')
            ->addColumn('yield', fn ($b) => $b->yield !== null ? $b->yield . '%' : '-')
            ->editColumn('status', fn ($b) => view('batches._status', ['s' => $b->status])->render())
            ->editColumn('production_date', fn ($b) => $b->production_date?->format('d/m/Y') ?? '-')
            ->addColumn('action', fn ($b) => view('batches._actions', ['b' => $b])->render())
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function create()
    {
        $this->authorize('batch.create');
        $products = \App\Models\Product::active()->orderBy('full_name')->get(['id', 'sku', 'full_name']);
        $warehouses = Warehouse::active()->pluck('name', 'id');
        return view('batches.create', compact('products', 'warehouses'));
    }

    /**
     * Global exception handler catches DomainException and redirects back with error flash.
     * No try/catch needed here.
     */
    public function store(BatchStoreRequest $request)
    {
        $this->authorize('batch.create');
        $batch = $this->batchService->create($request->validated());
        return redirect()->route('batches.show', $batch)->with('success', 'Batch berhasil dibuat.');
    }

    public function show(ProductionBatch $batch)
    {
        $this->authorize('batch.view');

        $batch->load([
            'products.product:id,sku,full_name,unit',
            'warehouse:id,name',
            'creator:id,name',
            'materials.material:id,code,name,unit',
            'additions.material:id,code,name,unit',
            'defects.product:id,full_name',
            'outputs.product:id,full_name',
        ]);

        $preview = $this->batchService->getMaterialPreview($batch);
        $materials = Material::active()->orderBy('name')->get(['id', 'code', 'name', 'unit']);

        return view('batches.show', compact('batch', 'preview', 'materials'));
    }

    public function release(ProductionBatch $batch)
    {
        $this->authorize('batch.release');
        $this->batchService->release($batch);
        return back()->with('success', 'Batch berhasil di-release. Bahan telah dikeluarkan dari stok.');
    }

    public function start(ProductionBatch $batch)
    {
        $this->authorize('batch.start');
        $this->batchService->start($batch);
        return back()->with('success', 'Produksi dimulai.');
    }

    public function recordOutput(BatchOutputRequest $request, ProductionBatch $batch)
    {
        $this->authorize('batch.record_output');
        $this->batchService->recordOutput($batch, $request->product_id, $request->good_qty);
        return back()->with('success', 'Output berhasil dicatat.');
    }

    public function recordDefect(BatchDefectRequest $request, ProductionBatch $batch)
    {
        $this->authorize('batch.record_defect');
        $this->batchService->recordDefect($batch, $request->product_id, $request->defect_qty, $request->reason, $request->notes);
        return back()->with('success', 'Defect berhasil dicatat.');
    }

    public function addMaterial(BatchTopupRequest $request, ProductionBatch $batch)
    {
        $this->authorize('batch.topup');
        $this->batchService->addMaterial(
            $batch, 
            $request->material_id, 
            $request->quantity, 
            $request->reason, 
            $request->product_id, 
            $request->type ?? 'topup'
        );
        return back()->with('success', $request->type === 'defect' ? 'Kerusakan bahan baku dicatat dan bahan pengganti telah dikeluarkan.' : 'Top-up bahan berhasil.');
    }

    public function complete(Request $request, ProductionBatch $batch)
    {
        $this->authorize('batch.complete');
        
        $request->validate([
            'results' => 'nullable|array',
            'results.*.product_id' => 'required|exists:products,id',
            'results.*.good_qty' => 'required|integer|min:0',
            'results.*.defect_qty' => 'required|integer|min:0',
        ]);

        $this->batchService->complete($batch, $request->results ?? []);
        return back()->with('success', 'Batch selesai. Hasil produksi berhasil dicatat.');
    }

    public function cancel(ProductionBatch $batch)
    {
        $this->authorize('batch.cancel');
        $this->batchService->cancel($batch);
        return back()->with('success', 'Batch dibatalkan. Bahan telah dikembalikan ke stok.');
    }
}
