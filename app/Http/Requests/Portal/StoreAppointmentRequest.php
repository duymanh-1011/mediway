<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->canAccessPortal();
    }

    protected function prepareForValidation(): void
    {
        $reasonForVisit = $this->input('reason_for_visit');
        $patientNotes = $this->input('patient_notes');

        $this->merge([
            'reason_for_visit' =>
            is_string($reasonForVisit)
                ? trim($reasonForVisit)
                : $reasonForVisit,

            'patient_notes' =>
            is_string($patientNotes) && trim($patientNotes) !== ''
                ? trim($patientNotes)
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

                /*
                |--------------------------------------------------------------------------
                | Phải có ít nhất một chữ cái
                |--------------------------------------------------------------------------
                |
                | Cho phép:
                | - đau đầu
                | - đau chân 2 ngày
                | - viêm da 123
                |
                | Không cho:
                | - 123456
                | - !!!@@@
                |
                | \p{L} hỗ trợ cả chữ tiếng Việt.
                |
                */

                'regex:/\p{L}/u',
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
            'appointment_time_slot_id.required' =>
            'Vui lòng chọn khung giờ khám.',

            'appointment_time_slot_id.integer' =>
            'Khung giờ khám không hợp lệ.',

            'appointment_time_slot_id.exists' =>
            'Khung giờ khám không tồn tại.',

            'reason_for_visit.required' =>
            'Vui lòng nhập lý do khám.',

            'reason_for_visit.string' =>
            'Lý do khám không hợp lệ.',

            'reason_for_visit.regex' =>
            'Lý do khám phải có ít nhất một chữ cái, không được chỉ nhập số hoặc ký tự đặc biệt.',

            'reason_for_visit.max' =>
            'Lý do khám không được vượt quá 2000 ký tự.',

            'patient_notes.string' =>
            'Ghi chú không hợp lệ.',

            'patient_notes.max' =>
            'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'appointment_time_slot_id' => 'khung giờ khám',
            'reason_for_visit' => 'lý do khám',
            'patient_notes' => 'ghi chú',
        ];
    }
}
