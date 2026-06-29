<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAlertRequest extends FormRequest
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
            'min_alert' => 'required|numeric|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Gudang',
            'material_id' => 'Bahan',
            'min_alert' => 'Ambang Peringatan',
        ];
    }
}
