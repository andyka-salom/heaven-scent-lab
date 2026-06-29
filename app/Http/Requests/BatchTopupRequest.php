<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|exists:products,id',
            'type' => 'nullable|string|in:topup,defect',
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'material_id' => 'Bahan',
            'quantity' => 'Jumlah',
            'reason' => 'Alasan',
        ];
    }
}
