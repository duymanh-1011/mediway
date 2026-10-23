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
            'reason_for_visit.string' => 'Vui lòng nhập lý do khám bằng văn bản.',

            'current_symptoms.string' => 'Vui lòng nhập triệu chứng hiện tại bằng văn bản.',

            'present_illness_history.string' => 'Vui lòng nhập bệnh sử hiện tại bằng văn bản.',

            'relevant_medical_history.string' => 'Vui lòng nhập tiền sử liên quan bằng văn bản.',

            'physical_examination.string' => 'Vui lòng nhập nội dung khám thực thể bằng văn bản.',

            'initial_assessment.string' => 'Vui lòng nhập nhận định ban đầu bằng văn bản.',

            'notes.string' => 'Vui lòng nhập ghi chú bằng văn bản.',
        ];
    }
}
