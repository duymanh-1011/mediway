<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_code' => [
                'exclude',
            ],

            'disease_group_id' => [
                'required',
                'integer',
                'exists:disease_groups,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'alternative_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'common_symptoms' => [
                'nullable',
                'string',
            ],

            'professional_notes' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:ACTIVE,INACTIVE',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'disease_code.required' => 'Vui lòng nhập mã bệnh.',

            'disease_code.unique' => 'Mã bệnh đã tồn tại.',

            'disease_code.max' => 'Mã bệnh không được vượt quá 30 ký tự.',

            'disease_group_id.required' => 'Vui lòng chọn nhóm bệnh.',

            'disease_group_id.exists' => 'Không tìm thấy nhóm bệnh đã chọn. Vui lòng tải lại trang và chọn lại.',

            'name.required' => 'Vui lòng nhập tên bệnh.',

            'name.max' => 'Tên bệnh không được vượt quá 255 ký tự.',

            'alternative_name.max' => 'Tên thay thế không được vượt quá 255 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái bệnh từ các lựa chọn có sẵn.',
        ];
    }
}
