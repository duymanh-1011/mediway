<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $disease =
            $this->route('disease');

        return [
            'disease_code' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'diseases',
                    'disease_code'
                )->ignore($disease->id),
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
            'disease_code.required' =>
                'Vui lòng nhập mã bệnh.',

            'disease_code.unique' =>
                'Mã bệnh đã tồn tại.',

            'disease_group_id.required' =>
                'Vui lòng chọn nhóm bệnh.',

            'disease_group_id.exists' =>
                'Nhóm bệnh không hợp lệ.',

            'name.required' =>
                'Vui lòng nhập tên bệnh.',

            'status.required' =>
                'Vui lòng chọn trạng thái.',

            'status.in' =>
                'Trạng thái bệnh không hợp lệ.',
        ];
    }
}