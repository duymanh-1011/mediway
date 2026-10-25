<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Vui lòng nhập lý do hủy lịch hẹn.',

            'cancellation_reason.max' => 'Lý do hủy không được vượt quá 2000 ký tự.',
        ];
    }
}
