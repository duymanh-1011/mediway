<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiseaseGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $diseaseGroup =
            $this->route('disease_group');

        return [
            'disease_group_code' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'disease_groups',
                    'disease_group_code'
                )->ignore($diseaseGroup->id),
            ],

            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'disease_groups',
                    'name'
                )->ignore($diseaseGroup->id),
            ],

            'description' => [
                'nullable',
                'string',
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
            'disease_group_code.required' => 'Vui lòng nhập mã nhóm bệnh.',

            'disease_group_code.max' => 'Mã nhóm bệnh không được vượt quá 30 ký tự.',

            'disease_group_code.unique' => 'Mã nhóm bệnh đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên nhóm bệnh.',

            'name.max' => 'Tên nhóm bệnh không được vượt quá 150 ký tự.',

            'name.unique' => 'Tên nhóm bệnh đã tồn tại.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái nhóm bệnh từ các lựa chọn có sẵn.',
        ];
    }
}
