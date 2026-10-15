<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                'integer',
                'exists:doctors,id',
            ],

            'specialty_id' => [
                'required',
                'integer',
                'exists:specialties,id',
            ],

            'examination_room_id' => [
                'required',
                'integer',
                'exists:examination_rooms,id',
            ],

            'work_date' => [
                'required',
                'date',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
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
            'doctor_id.required' =>
                'Vui lòng chọn bác sĩ.',

            'doctor_id.exists' =>
                'Bác sĩ được chọn không tồn tại.',

            'specialty_id.required' =>
                'Vui lòng chọn chuyên khoa.',

            'specialty_id.exists' =>
                'Chuyên khoa được chọn không tồn tại.',

            'examination_room_id.required' =>
                'Vui lòng chọn phòng khám.',

            'examination_room_id.exists' =>
                'Phòng khám được chọn không tồn tại.',

            'work_date.required' =>
                'Vui lòng chọn ngày làm việc.',

            'work_date.date' =>
                'Ngày làm việc không hợp lệ.',

            'start_time.required' =>
                'Vui lòng nhập giờ bắt đầu.',

            'start_time.date_format' =>
                'Giờ bắt đầu không hợp lệ.',

            'end_time.required' =>
                'Vui lòng nhập giờ kết thúc.',

            'end_time.date_format' =>
                'Giờ kết thúc không hợp lệ.',

            'end_time.after' =>
                'Giờ kết thúc phải sau giờ bắt đầu.',

            'status.required' =>
                'Vui lòng chọn trạng thái.',

            'status.in' =>
                'Trạng thái lịch làm việc không hợp lệ.',
        ];
    }
}