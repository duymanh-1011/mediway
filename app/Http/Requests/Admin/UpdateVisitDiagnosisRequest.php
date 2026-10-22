<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_id' => [
                'required',
                'integer',
                Rule::exists(
                    'diseases',
                    'id'
                ),
            ],

            'diagnosis_type' => [
                'required',
                'string',
                'max:40',
                Rule::in(['PRIMARY', 'SECONDARY', 'PRELIMINARY', 'CONFIRMED']),
                Rule::unique('visit_diagnoses', 'diagnosis_type')
                    ->where('medical_visit_id', $this->route('diagnosis')->medical_visit_id)
                    ->where('disease_id', $this->input('disease_id'))
                    ->ignore($this->route('diagnosis')->id),
            ],

            'severity' => [
                'nullable',
                'string',
                'max:40',
            ],

            'diagnosis_description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'diagnosed_at' => [
                'nullable',
                'date',
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
            'disease_id.required' => 'Vui lòng chọn bệnh.',

            'disease_id.integer' => 'Bệnh được chọn không hợp lệ.',

            'disease_id.exists' => 'Bệnh được chọn không tồn tại.',

            'diagnosis_type.max' => 'Loại chẩn đoán không được vượt quá 40 ký tự.',

            'severity.max' => 'Mức độ không được vượt quá 40 ký tự.',

            'diagnosis_description.max' => 'Mô tả chẩn đoán không được vượt quá 5000 ký tự.',

            'diagnosed_at.date' => 'Thời điểm chẩn đoán không hợp lệ.',

            'notes.max' => 'Ghi chú không được vượt quá 5000 ký tự.',
        ];
    }
}
