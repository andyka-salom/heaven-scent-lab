<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Gudang',
            'material_id' => 'Bahan',
            'quantity' => 'Jumlah',
            'notes' => 'Catatan',
        ];
    }
}
