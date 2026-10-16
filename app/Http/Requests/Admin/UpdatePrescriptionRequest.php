<?php

namespace App\Http\Requests\Admin;

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

            'prescription_pdf_path' => [
                'nullable',
                'string',
                'max:500',
            ],

            'confirm_update' => [
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'prescribed_at.required' =>
            'Thời gian kê đơn là bắt buộc.',

            'prescribed_at.date' =>
            'Thời gian kê đơn không hợp lệ.',

            'status.required' =>
            'Trạng thái đơn thuốc là bắt buộc.',

            'status.in' =>
            'Trạng thái đơn thuốc không hợp lệ.',

            'prescription_pdf_path.max' =>
            'Đường dẫn PDF không được vượt quá 500 ký tự.',

            'confirm_update.accepted' =>
            'Bạn phải xác nhận trước khi cập nhật đơn thuốc.',
        ];
    }
}
