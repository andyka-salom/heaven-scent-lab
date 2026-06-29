<?php

namespace App\Http\Controllers;

use App\Http\Requests\BomRequest;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use App\Services\BomImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BomController extends Controller
{
    public function __construct(private BomImportService $importService) {}

    public function edit(Product $product)
    {
        $this->authorize('bom.view');
        $product->load('activeBom.items.material:id,code,name,unit');
        $materials = Material::active()->orderBy('name')->get(['id', 'code', 'name', 'unit']);
        return view('bom.edit', compact('product', 'materials'));
    }

    public function update(BomRequest $request, Product $product)
    {
        $this->authorize('bom.manage');

        // Pre-load all materials by ID for O(1) lookup instead of N+1 findOrFail
        $materialIds = collect($request->items)->pluck('material_id')->unique()->values()->all();
        $materials = Material::whereIn('id', $materialIds)->get()->keyBy('id');

        DB::transaction(function () use ($request, $product, $materials) {
            // Deactivate existing BOMs
            Bom::where('product_id', $product->id)->update(['is_active' => false]);

            // Get max version
            $maxVersion = Bom::where('product_id', $product->id)->max('version') ?? 0;

            $bom = Bom::create([
                'product_id' => $product->id,
                'version' => $maxVersion + 1,
                'is_active' => true,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $material = $materials->get($item['material_id']);
                if (!$material) {
                    continue; // skip invalid material IDs
                }

                BomItem::create([
                    'bom_id' => $bom->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $material->unit,
                ]);
            }
        });

        return redirect()->route('bom.edit', $product)->with('success', 'BOM berhasil diperbarui.');
    }

    public function import(Request $request)
    {
        $this->authorize('bom.import');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $rows = array_map('str_getcsv', file($file->getPathname()));
        array_shift($rows); // remove header

        $result = $this->importService->import($rows);

        return back()
            ->with('success', "{$result['imported']} baris BOM berhasil diimpor.")
            ->with('errors', $result['errors']);
    }

    public function duplicate(Request $request, Product $product)
    {
        $this->authorize('bom.manage');

        $sourceProductId = $request->input('source_product_id');

        // Eager load product to prevent N+1
        $sourceBom = Bom::where('product_id', $sourceProductId)
            ->where('is_active', true)
            ->with(['items:id,bom_id,material_id,quantity,unit', 'product:id,full_name'])
            ->first();

        if (!$sourceBom) {
            return back()->with('error', 'BOM sumber tidak ditemukan.');
        }

        DB::transaction(function () use ($product, $sourceBom) {
            $bom = Bom::create([
                'product_id' => $product->id,
                'version' => (Bom::where('product_id', $product->id)->max('version') ?? 0) + 1,
                'is_active' => true,
                'notes' => 'Diduplikasi dari ' . $sourceBom->product->full_name,
            ]);

            foreach ($sourceBom->items as $item) {
                BomItem::create([
                    'bom_id' => $bom->id,
                    'material_id' => $item->material_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                ]);
            }
        });

        return redirect()->route('bom.edit', $product)->with('success', 'BOM berhasil diduplikasi.');
    }
}
