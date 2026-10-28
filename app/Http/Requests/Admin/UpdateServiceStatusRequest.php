<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceStatusRequest extends FormRequest
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
                'max:255',
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
            'description.string' => 'Mô tả không hợp lệ.',

            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',

            'allows_transition.required' => 'Vui lòng chọn cấu hình cho phép chuyển trạng thái.',

            'allows_transition.boolean' => 'Vui lòng chọn lại mục Cho phép chuyển trạng thái.',

            'is_active.required' => 'Vui lòng chọn trạng thái sử dụng.',

            'is_active.boolean' => 'Trạng thái sử dụng không hợp lệ.',
        ];
    }
}
