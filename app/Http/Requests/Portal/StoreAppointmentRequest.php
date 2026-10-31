<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->canAccessPortal();
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('reason_for_visit');
        $notes = $this->input('patient_notes');

        $this->merge([
            'reason_for_visit' => is_string($reason)
                ? trim($reason)
                : $reason,

            'patient_notes' => is_string($notes)
                && trim($notes) !== ''
                ? trim($notes)
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'appointment_time_slot_id' => [
                'bail',
                'required',
                'integer',
                'exists:appointment_time_slots,id',
            ],

            'reason_for_visit' => [
                'bail',
                'required',
                'string',
                'regex:/\p{L}/u',
                'max:2000',
            ],

            'patient_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'confirmed_information' => [
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_time_slot_id.required' =>
            'Vui lòng chọn khung giờ khám.',

            'appointment_time_slot_id.exists' =>
            'Khung giờ khám không tồn tại.',

            'reason_for_visit.required' =>
            'Vui lòng nhập lý do khám.',

            'reason_for_visit.regex' =>
            'Lý do khám phải có chữ cái, không được chỉ nhập số hoặc ký tự đặc biệt.',

            'reason_for_visit.max' =>
            'Lý do khám không được vượt quá 2000 ký tự.',

            'patient_notes.max' =>
            'Ghi chú không được vượt quá 2000 ký tự.',

            'confirmed_information.accepted' =>
            'Bạn phải tích chọn xác nhận thông tin trước khi đặt lịch khám.',
        ];
    }
}
