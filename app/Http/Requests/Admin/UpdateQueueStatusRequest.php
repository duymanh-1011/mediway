<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'process_order' => [
                'required',
                'integer',
                'min:0',
            ],

            'allows_transition' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => 'Mô tả trạng thái không hợp lệ.',

            'description.max' => 'Mô tả trạng thái không được vượt quá 1000 ký tự.',

            'process_order.required' => 'Vui lòng nhập thứ tự xử lý.',

            'process_order.integer' => 'Thứ tự xử lý phải là số nguyên.',

            'process_order.min' => 'Thứ tự xử lý không được nhỏ hơn 0.',

            'allows_transition.required' => 'Vui lòng chọn quyền chuyển trạng thái.',

            'allows_transition.boolean' => 'Vui lòng chọn lại mục Cho phép chuyển trạng thái.',

            'is_active.required' => 'Vui lòng chọn trạng thái hoạt động.',

            'is_active.boolean' => 'Vui lòng chọn lại trạng thái sử dụng.',
        ];
    }
}
