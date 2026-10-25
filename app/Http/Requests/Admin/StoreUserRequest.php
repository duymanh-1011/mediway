<?php

namespace App\Http\Requests\Admin;

use App\Rules\PasswordByteLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^0[0-9]{9}$/',
                Rule::unique('users', 'phone'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                new PasswordByteLimit,
                'confirmed',
            ],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('status', 'ACTIVE'),
            ],
            'status' => [
                'required',
                Rule::in(['ACTIVE', 'LOCKED']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu không được quá 72 ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp. Vui lòng kiểm tra cả hai ô mật khẩu.',

            'role_id.required' => 'Vui lòng chọn vai trò.',
            'role_id.exists' => 'Vai trò không tồn tại hoặc đã ngừng hoạt động.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Vui lòng chọn lại trạng thái từ các lựa chọn có sẵn.',
        ];
    }
}
