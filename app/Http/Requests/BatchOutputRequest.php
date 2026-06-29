<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'good_qty' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'Produk',
            'good_qty' => 'Jumlah Unit Baik',
        ];
    }
}
