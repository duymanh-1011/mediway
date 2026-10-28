<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medicine_code' => [
                'exclude',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'active_ingredient' => [
                'nullable',
                'string',
                'max:255',
            ],

            'strength' => [
                'nullable',
                'string',
                'max:100',
            ],

            'dosage_form' => [
                'nullable',
                'string',
                'max:100',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:50',
            ],

            'route_of_administration' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:255',
            ],

            'country_of_origin' => [
                'nullable',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'indications' => [
                'nullable',
                'string',
            ],

            'contraindications' => [
                'nullable',
                'string',
            ],

            'warnings' => [
                'nullable',
                'string',
            ],

            'common_side_effects' => [
                'nullable',
                'string',
            ],

            'storage_instructions' => [
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
            'medicine_code.required' => 'Vui lòng nhập mã thuốc.',

            'medicine_code.unique' => 'Mã thuốc đã tồn tại.',

            'medicine_code.max' => 'Mã thuốc không được vượt quá 30 ký tự.',

            'name.required' => 'Vui lòng nhập tên thuốc.',

            'name.max' => 'Tên thuốc không được vượt quá 255 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái thuốc từ các lựa chọn có sẵn.',
        ];
    }
}
