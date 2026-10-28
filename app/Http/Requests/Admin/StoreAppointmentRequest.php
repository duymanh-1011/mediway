<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_doctor_id' => ['nullable', 'integer', 'exists:doctors,id', 'prohibits:booking_room_id,booking_specialty_id'],
            'booking_room_id' => ['nullable', 'integer', 'exists:examination_rooms,id', 'prohibits:booking_doctor_id,booking_specialty_id'],
            'booking_specialty_id' => ['nullable', 'integer', 'exists:specialties,id', 'prohibits:booking_doctor_id,booking_room_id'],
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

    public function attributes(): array
    {
        return ['booking_doctor_id' => 'bác sĩ', 'booking_room_id' => 'phòng khám', 'booking_specialty_id' => 'chuyên khoa'];
    }

    public function messages(): array
    {
        return [
            'booking_doctor_id.prohibits' => 'Chỉ được chọn một trong ba: bác sĩ, phòng khám hoặc chuyên khoa.',
            'booking_room_id.prohibits' => 'Chỉ được chọn một trong ba: bác sĩ, phòng khám hoặc chuyên khoa.',
            'booking_specialty_id.prohibits' => 'Chỉ được chọn một trong ba: bác sĩ, phòng khám hoặc chuyên khoa.',
            'booking_doctor_id.exists' => 'Bác sĩ đã chọn không tồn tại.',
            'booking_room_id.exists' => 'Phòng khám đã chọn không tồn tại.',
            'booking_specialty_id.exists' => 'Chuyên khoa đã chọn không tồn tại.',
            'patient_id.required' => 'Vui lòng chọn bệnh nhân.',

            'patient_id.exists' => 'Bệnh nhân không tồn tại.',

            'appointment_time_slot_id.required' => 'Vui lòng chọn khung giờ khám.',

            'appointment_time_slot_id.exists' => 'Khung giờ khám không tồn tại.',

            'reason_for_visit.required' => 'Vui lòng nhập lý do khám.',

            'reason_for_visit.max' => 'Lý do khám không được vượt quá 2000 ký tự.',

            'patient_notes.max' => 'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }
}
