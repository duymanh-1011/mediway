<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitClinicalNoteRequest extends FormRequest
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

            'recorded_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_for_visit.string' => 'Vui lòng nhập lý do khám bằng văn bản.',

            'reason_for_visit.max' => 'Lý do khám không được vượt quá 2000 ký tự.',

            'current_symptoms.max' => 'Triệu chứng hiện tại không được vượt quá 5000 ký tự.',

            'present_illness_history.max' => 'Bệnh sử không được vượt quá 5000 ký tự.',

            'relevant_medical_history.max' => 'Tiền sử liên quan không được vượt quá 5000 ký tự.',

            'physical_examination.max' => 'Nội dung khám lâm sàng không được vượt quá 5000 ký tự.',

            'initial_assessment.max' => 'Nhận định ban đầu không được vượt quá 5000 ký tự.',

            'notes.max' => 'Ghi chú không được vượt quá 5000 ký tự.',

            'recorded_at.date' => 'Thời điểm ghi nhận chưa hợp lệ. Vui lòng chọn đủ ngày và giờ.',
        ];
    }
}
