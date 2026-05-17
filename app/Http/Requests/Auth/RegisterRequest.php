<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => 'required|string|max:100',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'phone' => 'required|string|regex:/^[0-9]+$/|max:12|unique:users',
        ];
    }

    public function messages(): array
    {
        return [
            'fullname.required' => 'Họ và tên không được để trống.',
            'fullname.max' => 'Họ và tên không được quá 100 ký tự.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu phải từ 8 ký tự trở lên.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex' => 'Số điện thoại chỉ được chứa các chữ số.',
            'phone.max' => 'Số điện thoại không được quá 12 số.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
        ];
    }
}
