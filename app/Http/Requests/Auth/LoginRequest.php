<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|regex:/^[0-9]+$/|max:12',
            'password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại chỉ được chứa các chữ số.',
            'phone.max' => 'Số điện thoại không được quá 12 số.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ];
    }
}
