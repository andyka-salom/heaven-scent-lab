<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustRequest extends FormRequest
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
            'quantity' => 'required|numeric',
            'notes' => 'required|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Gudang',
            'material_id' => 'Bahan',
            'quantity' => 'Jumlah',
            'notes' => 'Catatan/Alasan',
        ];
    }
}
