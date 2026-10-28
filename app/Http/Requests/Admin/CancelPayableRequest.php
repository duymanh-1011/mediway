<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirm_cancel' => [
                'accepted',
            ],

            'cancel_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_cancel.accepted' => 'Vui lòng xác nhận hủy khoản thu trước khi tiếp tục.',

            'cancel_reason.required' => 'Vui lòng nhập lý do hủy.',

            'cancel_reason.string' => 'Vui lòng nhập lý do hủy bằng văn bản.',

            'cancel_reason.max' => 'Lý do hủy không được vượt quá 1000 ký tự.',
        ];
    }
}
