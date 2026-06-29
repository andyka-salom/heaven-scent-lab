<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.planned_qty' => 'required|integer|min:1',
            'warehouse_id' => 'required|exists:warehouses,id',
            'production_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'Produk',
            'warehouse_id' => 'Gudang',
            'planned_qty' => 'Jumlah Rencana',
            'production_date' => 'Tanggal Produksi',
            'notes' => 'Catatan',
        ];
    }
}
