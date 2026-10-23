<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignGroupServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medical_service_ids' => ['required', 'array', 'min:1'],
            'medical_service_ids.*' => ['required', 'integer', 'distinct', Rule::exists('medical_services', 'id')->where('status', 'ACTIVE')],
        ];
    }

    public function messages(): array
    {
        return [
            'medical_service_ids.required' => 'Vui lòng chọn ít nhất một dịch vụ.',
            'medical_service_ids.array' => 'Vui lòng chọn dịch vụ từ danh sách gợi ý.',
            'medical_service_ids.*.distinct' => 'Không được chọn trùng dịch vụ.',
            'medical_service_ids.*.exists' => 'Dịch vụ không tồn tại hoặc đã ngừng hoạt động.',
        ];
    }
}
