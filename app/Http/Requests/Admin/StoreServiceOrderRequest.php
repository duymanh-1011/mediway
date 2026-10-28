<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'instructions' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'medical_service_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'medical_service_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:medical_services,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'medical_service_ids.required' => 'Vui lòng chọn ít nhất một dịch vụ.',

            'medical_service_ids.array' => 'Vui lòng chọn dịch vụ từ danh sách gợi ý.',

            'medical_service_ids.min' => 'Vui lòng chọn ít nhất một dịch vụ.',

            'medical_service_ids.*.required' => 'Vui lòng chọn lại dịch vụ từ danh sách gợi ý.',

            'medical_service_ids.*.integer' => 'Vui lòng chọn lại dịch vụ từ danh sách gợi ý.',

            'medical_service_ids.*.distinct' => 'Không được chọn trùng dịch vụ.',

            'medical_service_ids.*.exists' => 'Có dịch vụ không tồn tại trong hệ thống.',

            'reason.string' => 'Vui lòng nhập lý do chỉ định bằng văn bản.',

            'reason.max' => 'Lý do chỉ định không được vượt quá 5000 ký tự.',

            'instructions.string' => 'Vui lòng nhập hướng dẫn thực hiện bằng văn bản.',

            'instructions.max' => 'Hướng dẫn thực hiện không được vượt quá 5000 ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'lý do chỉ định',
            'instructions' => 'hướng dẫn thực hiện',
            'medical_service_ids' => 'dịch vụ',
        ];
    }
}
