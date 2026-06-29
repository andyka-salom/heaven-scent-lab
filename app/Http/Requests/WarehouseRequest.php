<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('warehouse')?->id;

        return [
            'code' => 'required|string|max:64|unique:warehouses,code' . ($id ? ",{$id}" : ''),
            'name' => 'required|string|max:150',
            'location' => 'nullable|string|max:200',
            'is_active' => 'boolean',
            'allow_negative_stock' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode Gudang',
            'name' => 'Nama Gudang',
            'location' => 'Lokasi',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'allow_negative_stock' => $this->boolean('allow_negative_stock'),
        ]);
    }
}
