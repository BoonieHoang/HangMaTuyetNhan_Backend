<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'stock' => 'nullable|integer|min:0',
            'holiday_ids' => 'nullable|array',
            'holiday_ids.*' => 'exists:holidays,id',
            'purpose_ids' => 'nullable|array',
            'purpose_ids.*' => 'exists:purposes,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
