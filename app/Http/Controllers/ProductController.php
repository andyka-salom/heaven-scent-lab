<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('product.view');
        $warehouses = Warehouse::active()->pluck('name', 'id');
        return view('products.index', compact('warehouses'));
    }

    public function data(): JsonResponse
    {
        $this->authorize('product.view');

        $query = Product::query()
            ->select('products.*')
            ->with('defaultWarehouse:id,name')
            ->withCount(['boms as boms_count' => function ($q) {
                $q->where('is_active', true);
            }]);

        return DataTables::eloquent($query)
            ->addColumn('warehouse', fn ($p) => $p->defaultWarehouse?->name ?? '-')
            ->addColumn('bom_count', fn ($p) => $p->boms_count)
            ->editColumn('is_active', fn ($p) => $p->is_active ? 'Aktif' : 'Nonaktif')
            ->addColumn('action', fn ($p) => view('products._actions', ['p' => $p])->render())
            ->rawColumns(['action'])
            ->toJson();
    }

    public function create()
    {
        $this->authorize('product.create');
        $warehouses = Warehouse::active()->pluck('name', 'id');
        return view('products.create', compact('warehouses'));
    }

    public function store(ProductRequest $request)
    {
        $this->authorize('product.create');
        Product::create($request->validated());
        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $this->authorize('product.edit');
        $warehouses = Warehouse::active()->pluck('name', 'id');
        return view('products.edit', compact('product', 'warehouses'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->authorize('product.edit');
        $product->update($request->validated());
        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('product.delete');
        $product->delete();
        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
