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
            'login' => 'required_without:phone|string',
            'phone' => 'required_without:login|string',
            'password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'login.required_without' => 'Vui lòng nhập số điện thoại hoặc email.',
            'phone.required_without' => 'Vui lòng nhập số điện thoại hoặc email.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ];
    }
}
