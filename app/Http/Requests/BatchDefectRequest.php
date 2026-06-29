<?php

namespace App\Http\Requests;

use App\Models\BatchDefect;
use Illuminate\Foundation\Http\FormRequest;

class BatchDefectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'defect_qty' => 'required|integer|min:1',
            'reason' => 'required|string|in:' . implode(',', array_keys(BatchDefect::REASONS)),
            'notes' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'Produk',
            'defect_qty' => 'Jumlah Rusak',
            'reason' => 'Alasan',
            'notes' => 'Catatan',
        ];
    }
}
