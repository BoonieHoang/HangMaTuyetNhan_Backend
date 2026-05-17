<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => 'required|string|max:100',
            'email' => 'nullable|email|unique:users,email,' . $this->user()->id,
            'phone' => 'required|string|regex:/^[0-9]+$/|max:12|unique:users,phone,' . $this->user()->id,
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date|before:today',
        ];
    }

    public function messages(): array
    {
        return [
            'fullname.required' => 'Họ và tên không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng bởi người khác.',
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex' => 'Số điện thoại chỉ được chứa các chữ số.',
            'phone.max' => 'Số điện thoại không được quá 12 số.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng bởi người khác.',
            'birthday.date' => 'Ngày sinh không đúng định dạng.',
            'birthday.before' => 'Ngày sinh phải trước ngày hôm nay.',
        ];
    }
}
