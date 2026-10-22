<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_group_code' => [
                'exclude',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
                'unique:service_groups,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'display_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],

            'status' => [
                'required',
                'in:ACTIVE,INACTIVE',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_group_code.required' => 'Vui lòng nhập mã nhóm dịch vụ.',

            'service_group_code.max' => 'Mã nhóm dịch vụ không được vượt quá 30 ký tự.',

            'service_group_code.unique' => 'Mã nhóm dịch vụ đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên nhóm dịch vụ.',

            'name.max' => 'Tên nhóm dịch vụ không được vượt quá 150 ký tự.',

            'name.unique' => 'Tên nhóm dịch vụ đã tồn tại.',

            'display_order.required' => 'Vui lòng nhập thứ tự hiển thị.',

            'display_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',

            'display_order.min' => 'Thứ tự hiển thị không được nhỏ hơn 0.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Trạng thái nhóm dịch vụ không hợp lệ.',
        ];
    }
}
