<?php

namespace App\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'sku' => 'required|string|max:64|unique:products,sku' . ($id ? ",{$id}" : ''),
            'item_name' => 'required|string|max:120',
            'variant_name' => 'required|string|max:120',
            'unit' => 'required|string|max:16',
            'default_warehouse_id' => 'nullable|exists:warehouses,id',
            'is_active' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'item_name' => 'Nama Item',
            'variant_name' => 'Nama Varian',
            'unit' => 'Satuan',
            'default_warehouse_id' => 'Gudang Default',
        ];
    }
}
