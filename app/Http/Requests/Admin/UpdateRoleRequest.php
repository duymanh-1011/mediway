<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        if ($role instanceof Role && $role->isSystemRole()) {
            return [
                'description' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ];
        }

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::in(['ACTIVE', 'INACTIVE']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên vai trò.',
            'name.max' => 'Tên vai trò không được quá 50 ký tự.',
            'name.regex' => 'Tên vai trò không hợp lệ.',
            'name.unique' => 'Tên vai trò đã tồn tại.',

            'description.max' => 'Mô tả không được quá 255 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Vui lòng chọn lại trạng thái từ các lựa chọn có sẵn.',
        ];
    }
}
