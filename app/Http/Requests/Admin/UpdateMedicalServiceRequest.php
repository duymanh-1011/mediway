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
                )->ignore(
                    $medicalService->id
                ),
            ],

            'service_group_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'service_group_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('service_groups', 'id')->where(fn ($query) => $query
                    ->where('status', 'ACTIVE')
                    ->orWhereIn('id', $medicalService->serviceGroups()->pluck('service_groups.id'))),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('medical_services', 'name')->ignore($medicalService->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'multiple_of:1000',
                'max:9999999999.99',
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
            'service_code.required' => 'Vui lòng nhập mã dịch vụ.',

            'service_code.unique' => 'Mã dịch vụ đã tồn tại.',

            'service_group_ids.required' => 'Vui lòng chọn ít nhất một nhóm dịch vụ.',

            'service_group_ids.array' => 'Vui lòng chọn nhóm dịch vụ từ danh sách gợi ý.',

            'service_group_ids.min' => 'Vui lòng chọn ít nhất một nhóm dịch vụ.',

            'service_group_ids.*.required' => 'Vui lòng chọn lại nhóm dịch vụ từ danh sách gợi ý.',

            'service_group_ids.*.integer' => 'Vui lòng chọn lại nhóm dịch vụ từ danh sách gợi ý.',

            'service_group_ids.*.distinct' => 'Không được chọn trùng nhóm dịch vụ.',

            'service_group_ids.*.exists' => 'Nhóm dịch vụ đã chọn không tồn tại hoặc đã ngừng hoạt động. Vui lòng chọn lại.',

            'name.required' => 'Vui lòng nhập tên dịch vụ.',
            'name.unique' => 'Tên dịch vụ đã tồn tại. Hãy gán dịch vụ hiện có vào nhóm.',

            'price.required' => 'Vui lòng nhập giá dịch vụ.',

            'price.numeric' => 'Giá dịch vụ phải là số.',

            'price.min' => 'Giá dịch vụ không được âm.',

            'price.multiple_of' => 'Giá dịch vụ phải chia hết cho 1.000 đồng, ví dụ 0, 1000 hoặc 2000.',

            'unit.required' => 'Vui lòng nhập đơn vị.',

            'estimated_duration_minutes.integer' => 'Thời lượng dự kiến phải là số nguyên.',

            'estimated_duration_minutes.min' => 'Thời lượng dự kiến phải lớn hơn 0 phút.',

            'estimated_duration_minutes.max' => 'Thời lượng dự kiến không được vượt quá 1440 phút.',

            'display_order.required' => 'Vui lòng nhập thứ tự hiển thị.',

            'display_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái dịch vụ từ các lựa chọn có sẵn.',
        ];
    }
}
