<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                'exists:patients,id',
            ],

            'appointment_time_slot_id' => [
                'required',
                'integer',
                'exists:appointment_time_slots,id',
            ],

            'reason_for_visit' => [
                'required',
                'string',
                'max:2000',
            ],

            'patient_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' =>
                'Vui lòng chọn bệnh nhân.',

            'appointment_time_slot_id.required' =>
                'Vui lòng chọn khung giờ khám.',

            'reason_for_visit.required' =>
                'Vui lòng nhập lý do khám.',
        ];
    }
}