<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitClinicalNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_for_visit' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'current_symptoms' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'present_illness_history' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'relevant_medical_history' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'physical_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'initial_assessment' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_for_visit.string' =>
                'Lý do khám không hợp lệ.',

            'current_symptoms.string' =>
                'Triệu chứng hiện tại không hợp lệ.',

            'present_illness_history.string' =>
                'Bệnh sử hiện tại không hợp lệ.',

            'relevant_medical_history.string' =>
                'Tiền sử liên quan không hợp lệ.',

            'physical_examination.string' =>
                'Khám thực thể không hợp lệ.',

            'initial_assessment.string' =>
                'Nhận định ban đầu không hợp lệ.',

            'notes.string' =>
                'Ghi chú không hợp lệ.',
        ];
    }
}
