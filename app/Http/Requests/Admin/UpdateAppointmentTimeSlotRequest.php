<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],

            'max_patients' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
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
            'start_time.required' => 'Vui lòng nhập giờ bắt đầu.',

            'start_time.date_format' => 'Giờ bắt đầu không hợp lệ.',

            'end_time.required' => 'Vui lòng nhập giờ kết thúc.',

            'end_time.date_format' => 'Giờ kết thúc không hợp lệ.',

            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',

            'max_patients.required' => 'Vui lòng nhập sức chứa.',

            'max_patients.integer' => 'Sức chứa phải là số nguyên.',

            'max_patients.min' => 'Sức chứa phải lớn hơn hoặc bằng 1.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Trạng thái khung giờ không hợp lệ.',
        ];
    }
}
