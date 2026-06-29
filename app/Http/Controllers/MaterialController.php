<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialRequest;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MaterialController extends Controller
{
    public function index()
    {
        $this->authorize('material.view');
        return view('materials.index');
    }

    public function data(): JsonResponse
    {
        $this->authorize('material.view');

        $query = Material::query()->select('materials.*');

        return DataTables::eloquent($query)
            ->editColumn('type', fn ($m) => $m->type_label)
            ->editColumn('is_active', fn ($m) => $m->is_active ? 'Aktif' : 'Nonaktif')
            ->addColumn('action', fn ($m) => view('materials._actions', ['m' => $m])->render())
            ->rawColumns(['action'])
            ->toJson();
    }

    public function create()
    {
        $this->authorize('material.create');
        return view('materials.create');
    }

    public function store(MaterialRequest $request)
    {
        $this->authorize('material.create');
        Material::create($request->validated());
        return redirect()->route('materials.index')->with('success', 'Bahan berhasil ditambahkan.');
    }

    public function edit(Material $material)
    {
        $this->authorize('material.edit');
        return view('materials.edit', compact('material'));
    }

    public function update(MaterialRequest $request, Material $material)
    {
        $this->authorize('material.edit');
        $material->update($request->validated());
        return redirect()->route('materials.index')->with('success', 'Bahan berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        $this->authorize('material.delete');
        $material->delete();
        return response()->json(['message' => 'Bahan berhasil dihapus.']);
    }
}
