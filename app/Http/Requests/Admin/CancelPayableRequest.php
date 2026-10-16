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
            'confirm_cancel.accepted' =>
            'Bạn phải xác nhận trước khi hủy khoản phải thanh toán.',

            'cancel_reason.required' =>
            'Vui lòng nhập lý do hủy.',

            'cancel_reason.string' =>
            'Lý do hủy không hợp lệ.',

            'cancel_reason.max' =>
            'Lý do hủy không được vượt quá 1000 ký tự.',
        ];
    }
}
