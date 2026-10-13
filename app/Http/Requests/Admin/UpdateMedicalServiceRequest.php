<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $medicalService =
            $this->route('medical_service');

        return [
            'service_code' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'medical_services',
                    'service_code'
                )->ignore($medicalService->id),
            ],

            'service_group_id' => [
                'required',
                'integer',
                'exists:service_groups,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'estimated_duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440',
            ],

            'preparation_instructions' => [
                'nullable',
                'string',
            ],

            'precautions' => [
                'nullable',
                'string',
            ],

            'display_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
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
            'service_code.required' =>
                'Vui lòng nhập mã dịch vụ.',

            'service_code.unique' =>
                'Mã dịch vụ đã tồn tại.',

            'service_group_id.required' =>
                'Vui lòng chọn nhóm dịch vụ.',

            'service_group_id.exists' =>
                'Nhóm dịch vụ không hợp lệ.',

            'name.required' =>
                'Vui lòng nhập tên dịch vụ.',

            'price.required' =>
                'Vui lòng nhập giá dịch vụ.',

            'price.numeric' =>
                'Giá dịch vụ phải là số.',

            'price.min' =>
                'Giá dịch vụ không được âm.',

            'unit.required' =>
                'Vui lòng nhập đơn vị.',

            'estimated_duration_minutes.integer' =>
                'Thời lượng dự kiến phải là số nguyên.',

            'estimated_duration_minutes.min' =>
                'Thời lượng dự kiến phải lớn hơn 0 phút.',

            'display_order.required' =>
                'Vui lòng nhập thứ tự hiển thị.',

            'display_order.integer' =>
                'Thứ tự hiển thị phải là số nguyên.',

            'status.required' =>
                'Vui lòng chọn trạng thái.',

            'status.in' =>
                'Trạng thái dịch vụ không hợp lệ.',
        ];
    }
}