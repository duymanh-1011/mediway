<?php

namespace App\Http\Requests\Portal;

use App\Rules\PasswordByteLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterPatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'max:72', new PasswordByteLimit, 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return ['full_name.required' => 'Vui lòng nhập họ và tên.', 'full_name.max' => 'Họ và tên không được quá 120 ký tự.', 'phone.required' => 'Vui lòng nhập số điện thoại.', 'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.', 'phone.unique' => 'Số điện thoại đã được sử dụng.', 'password.required' => 'Vui lòng nhập mật khẩu.', 'password.min' => 'Mật khẩu cần có ít nhất 8 ký tự.', 'password.confirmed' => 'Xác nhận mật khẩu chưa khớp.'];
    }
}
