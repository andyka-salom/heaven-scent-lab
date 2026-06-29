<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;

class WarehouseController extends Controller
{
    public function index()
    {
        $this->authorize('warehouse.view');
        $warehouses = Warehouse::withCount('stocks')->latest()->get();
        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $this->authorize('warehouse.create');
        return view('warehouses.create');
    }

    public function store(WarehouseRequest $request)
    {
        $this->authorize('warehouse.create');
        Warehouse::create($request->validated());
        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function edit(Warehouse $warehouse)
    {
        $this->authorize('warehouse.edit');
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $this->authorize('warehouse.edit');
        $warehouse->update($request->validated());
        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->authorize('warehouse.delete');
        $warehouse->delete();
        return response()->json(['message' => 'Gudang berhasil dihapus.']);
    }
}
