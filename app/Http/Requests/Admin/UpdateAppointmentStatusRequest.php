<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $appointmentStatus =
            $this->route('appointment_status');

        return [
            'description' => [
                'nullable',
                'string',
            ],

            'process_order' => [
                'required',
                'integer',
                'min:1',

                Rule::unique(
                    'appointment_statuses',
                    'process_order'
                )->ignore($appointmentStatus->id),
            ],

            'allows_transition' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'process_order.required' =>
                'Vui lòng nhập thứ tự xử lý.',

            'process_order.integer' =>
                'Thứ tự xử lý phải là số nguyên.',

            'process_order.min' =>
                'Thứ tự xử lý phải lớn hơn hoặc bằng 1.',

            'process_order.unique' =>
                'Thứ tự xử lý này đang được sử dụng bởi trạng thái khác.',

            'allows_transition.required' =>
                'Vui lòng chọn khả năng chuyển trạng thái.',

            'allows_transition.boolean' =>
                'Giá trị chuyển trạng thái không hợp lệ.',

            'is_active.required' =>
                'Vui lòng chọn trạng thái hoạt động.',

            'is_active.boolean' =>
                'Giá trị trạng thái hoạt động không hợp lệ.',
        ];
    }
}