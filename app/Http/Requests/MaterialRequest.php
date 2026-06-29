<?php

namespace App\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('material')?->id;

        return [
            'code' => 'required|string|max:64|unique:materials,code' . ($id ? ",{$id}" : ''),
            'name' => 'required|string|max:150',
            'type' => 'required|string|in:' . implode(',', array_keys(Material::TYPES)),
            'unit' => 'required|string|in:' . implode(',', Material::UNITS),
            'is_active' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode Bahan',
            'name' => 'Nama Bahan',
            'type' => 'Tipe',
            'unit' => 'Satuan',
        ];
    }
}
