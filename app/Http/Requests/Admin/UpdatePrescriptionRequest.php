<?php

namespace App\Http\Requests\Admin;

use App\Models\ClinicalDocument;
use App\Models\ClinicalTimeline;
use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePrescriptionRequest extends FormRequest
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('prescribed_at')) {
                $prescription = Prescription::find($this->route('id'));
                $visit = $prescription?->medicalVisit;
                ClinicalTimeline::assertEventTime($this->input('prescribed_at'), $visit?->examination_started_at ?? $visit?->checked_in_at,
                    'prescribed_at', 'Thời điểm kê đơn');
            }
        }];
    }

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
            'prescribed_at.required' => 'Vui lòng chọn ngày và giờ kê đơn.',

            'prescribed_at.date' => 'Thời điểm kê đơn chưa hợp lệ. Vui lòng chọn đủ ngày và giờ.',

            'status.required' => 'Vui lòng chọn trạng thái đơn thuốc.',

            'status.in' => 'Vui lòng chọn lại trạng thái đơn thuốc từ các lựa chọn có sẵn.',

            'confirm_update.accepted' => 'Vui lòng bấm Xác nhận cập nhật để lưu thay đổi đơn thuốc.',
        ];
    }
}
