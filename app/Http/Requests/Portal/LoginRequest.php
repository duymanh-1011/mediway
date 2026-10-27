<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['phone' => ['required', 'string', 'regex:/^0[0-9]{9}$/'], 'password' => ['required', 'string']];
    }

    public function messages(): array
    {
        return ['phone.required' => 'Vui lòng nhập số điện thoại.', 'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.', 'password.required' => 'Vui lòng nhập mật khẩu.'];
    }
}
