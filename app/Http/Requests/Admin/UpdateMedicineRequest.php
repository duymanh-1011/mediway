<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $medicine =
            $this->route('medicine');

        return [
            'medicine_code' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'medicines',
                    'medicine_code'
                )->ignore($medicine->id),
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

            'price_per_unit' => ['nullable', 'integer', 'min:1', 'max:99999999'],
            'price_per_blister' => ['nullable', 'integer', 'min:1', 'max:99999999'],
            'price_per_box' => ['nullable', 'integer', 'min:1', 'max:99999999'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $unitInput = $this->input('unit');
            $unit = is_string($unitInput) ? mb_strtolower(trim($unitInput)) : '';
            if ($this->filled('price_per_unit') && $unit === '') {
                $validator->errors()->add('unit', 'Cần nhập đơn vị cơ bản khi thiết lập giá cho đơn vị này.');
            }
            if (($unit === 'vỉ' && $this->filled('price_per_unit') && $this->filled('price_per_blister'))
                || ($unit === 'hộp' && $this->filled('price_per_unit') && $this->filled('price_per_box'))) {
                $validator->errors()->add('unit', 'Đơn vị cơ bản trùng với đơn vị đóng gói; chỉ nhập giá tương ứng một lần.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'medicine_code.required' => 'Vui lòng nhập mã thuốc.',

            'medicine_code.unique' => 'Mã thuốc đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên thuốc.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái thuốc từ các lựa chọn có sẵn.',
        ];
    }
}
