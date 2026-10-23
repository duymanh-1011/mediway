<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalVisitStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_status_id' => [
                'required',
                'integer',
                Rule::exists(
                    'visit_statuses',
                    'id'
                )->where(
                    'is_active',
                    1
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_status_id.required' => 'Vui lòng chọn trạng thái lượt khám.',

            'visit_status_id.integer' => 'Vui lòng chọn lại trạng thái lượt khám từ các lựa chọn có sẵn.',

            'visit_status_id.exists' => 'Trạng thái lượt khám không tồn tại hoặc đã ngừng hoạt động.',
        ];
    }
}
