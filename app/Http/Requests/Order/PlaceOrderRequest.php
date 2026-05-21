<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => 'required|string|max:100',
            'phone' => 'required|string|regex:/^[0-9]+$/|max:12',
            'email' => 'required|email|max:150',
            'ship_address' => 'required|string',
            'note' => 'nullable|string',
            'payment_method' => 'required|in:cod,bank_transfer',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại chỉ được chứa các chữ số.',
            'phone.max' => 'Số điện thoại không được quá 12 số.',
        ];
    }
}
