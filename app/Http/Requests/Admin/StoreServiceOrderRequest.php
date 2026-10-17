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
            'medical_service_ids.required' =>
                'Vui lòng chọn ít nhất một dịch vụ.',

            'medical_service_ids.array' =>
                'Danh sách dịch vụ không hợp lệ.',

            'medical_service_ids.min' =>
                'Vui lòng chọn ít nhất một dịch vụ.',

            'medical_service_ids.*.required' =>
                'Dịch vụ không hợp lệ.',

            'medical_service_ids.*.integer' =>
                'Dịch vụ không hợp lệ.',

            'medical_service_ids.*.distinct' =>
                'Không được chọn trùng dịch vụ.',

            'medical_service_ids.*.exists' =>
                'Có dịch vụ không tồn tại trong hệ thống.',

            'reason.string' =>
                'Lý do chỉ định không hợp lệ.',

            'reason.max' =>
                'Lý do chỉ định không được vượt quá 5000 ký tự.',

            'instructions.string' =>
                'Hướng dẫn thực hiện không hợp lệ.',

            'instructions.max' =>
                'Hướng dẫn thực hiện không được vượt quá 5000 ký tự.',
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