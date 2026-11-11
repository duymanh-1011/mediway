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
            'citizen_id_number' => ['required', 'string', 'regex:/^[0-9]{12}$/'],
            'activation_code' => ['nullable', 'string', 'regex:/^[a-f0-9]{20}$/i'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'max:72', new PasswordByteLimit, 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.max' => 'Họ và tên không được quá 120 ký tự.',
            'citizen_id_number.required' => 'Vui lòng nhập số CCCD.',
            'citizen_id_number.regex' => 'Số CCCD phải gồm 12 chữ số.',
            'activation_code.regex' => 'Mã kích hoạt không hợp lệ. Vui lòng kiểm tra mã do quầy tiếp đón cấp.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
            'phone.unique' => 'Tài khoản đã tồn tại với số điện thoại này. Vui lòng đăng nhập hoặc chọn Quên mật khẩu.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu cần có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu chưa khớp.',
        ];
    }
}
