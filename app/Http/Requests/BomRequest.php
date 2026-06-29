<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'Daftar Bahan',
            'notes' => 'Catatan',
        ];
    }
}
