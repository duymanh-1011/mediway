<?php

namespace App\Http\Requests\Admin;

use App\Rules\PasswordByteLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdministratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
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
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('administrators', 'email'),
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],
            'gender' => [
                'nullable',
                Rule::in(['MALE', 'FEMALE', 'OTHER']),
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
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
            'full_name.required' => 'Vui lòng nhập họ tên quản trị viên.',
            'full_name.max' => 'Họ tên không được quá 120 ký tự.',

            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu không được quá 72 ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp. Vui lòng kiểm tra cả hai ô mật khẩu.',

            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được quá 150 ký tự.',
            'email.unique' => 'Email đã được sử dụng.',

            'date_of_birth.date' => 'Ngày sinh chưa hợp lệ. Vui lòng chọn lại ngày bằng ô lịch.',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại.',

            'gender.in' => 'Vui lòng chọn giới tính từ các lựa chọn có sẵn.',
            'address.max' => 'Địa chỉ không được quá 255 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Vui lòng chọn lại trạng thái từ các lựa chọn có sẵn.',
        ];
    }
}
