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

            'start_time.date_format' => 'Giờ bắt đầu chưa đúng. Vui lòng nhập theo dạng giờ:phút, ví dụ 08:30.',

            'end_time.required' => 'Vui lòng nhập giờ kết thúc.',

            'end_time.date_format' => 'Giờ kết thúc chưa đúng. Vui lòng nhập theo dạng giờ:phút, ví dụ 11:30.',

            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',

            'max_patients.required' => 'Vui lòng nhập sức chứa.',

            'max_patients.integer' => 'Sức chứa phải là số nguyên.',

            'max_patients.min' => 'Mỗi khung giờ phải tiếp nhận được ít nhất 1 bệnh nhân.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái khung giờ từ các lựa chọn có sẵn.',
        ];
    }
}
