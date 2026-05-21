<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => 'required|string|max:100',
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('categories')->ignore($categoryId)],
            'image' => 'nullable', // Can be file or string (url)
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
