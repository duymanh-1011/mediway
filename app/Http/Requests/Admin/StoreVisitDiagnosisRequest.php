<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitDiagnosisRequest extends FormRequest
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
                Rule::exists('diseases', 'id')
                    ->where('status', 'ACTIVE'),
            ],

            'diagnosis_type' => [
                'required',
                'string',
                Rule::in([
                    'PRIMARY',
                    'SECONDARY',
                    'PRELIMINARY',
                    'CONFIRMED',
                ]),
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
            'disease_id.required' =>
                'Vui lòng chọn bệnh.',

            'disease_id.exists' =>
                'Bệnh được chọn không hợp lệ hoặc đã ngừng hoạt động.',

            'diagnosis_type.required' =>
                'Vui lòng chọn loại chẩn đoán.',

            'diagnosis_type.in' =>
                'Loại chẩn đoán không hợp lệ.',

            'severity.max' =>
                'Mức độ không được vượt quá 40 ký tự.',

            'diagnosis_description.max' =>
                'Mô tả chẩn đoán quá dài.',

            'notes.max' =>
                'Ghi chú quá dài.',
        ];
    }
}
