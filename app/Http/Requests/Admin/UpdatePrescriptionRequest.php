<?php

namespace App\Http\Requests\Admin;

use App\Models\ClinicalDocument;
use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = Prescription::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->pluck('status')
            ->all();

        return [
            'prescribed_at' => [
                'required',
                'date',
            ],

            'general_instructions' => [
                'nullable',
                'string',
            ],

            'doctor_notes' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'string',
                'max:20',
                Rule::in($statuses),
            ],

            'prescription_pdf' => ClinicalDocument::pdfRules(),
            'prescription_pdf_path' => ['prohibited'],

            'confirm_update' => [
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'prescribed_at.required' => 'Thời gian kê đơn là bắt buộc.',

            'prescribed_at.date' => 'Thời gian kê đơn không hợp lệ.',

            'status.required' => 'Trạng thái đơn thuốc là bắt buộc.',

            'status.in' => 'Trạng thái đơn thuốc không hợp lệ.',

            'confirm_update.accepted' => 'Bạn phải xác nhận trước khi cập nhật đơn thuốc.',
        ];
    }
}
