<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => 'sometimes|required|string|max:200',
            'category_id' => 'sometimes|required|exists:categories,id',
            'price' => 'sometimes|required|numeric|min:0',
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products')->ignore($productId)],
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
