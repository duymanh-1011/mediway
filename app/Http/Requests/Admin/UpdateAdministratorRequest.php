<?php

namespace App\Http\Requests\Admin;

use App\Models\Administrator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdministratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $administrator = $this->route('administrator');

        $administratorId = $administrator instanceof Administrator
            ? $administrator->id
            : null;

        $userId = $administrator instanceof Administrator
            ? $administrator->user_id
            : null;

        return [
            'full_name' => [
                'required',
                'string',
                'max:120',
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^0[0-9]{9}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('administrators', 'email')->ignore($administratorId),
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

            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã được sử dụng.',
            'email.max' => 'Email không được quá 150 ký tự.',

            'date_of_birth.date' => 'Ngày sinh không hợp lệ.',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại.',

            'gender.in' => 'Giới tính không hợp lệ.',
            'address.max' => 'Địa chỉ không được quá 255 ký tự.',
        ];
    }
}