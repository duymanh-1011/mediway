<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                Rule::in([
                    'CASH',
                    'QR',
                ]),
            ],

            'provider_transaction_code' => [
                'nullable',
                'string',
                'max:120',
            ],

            'payment_content' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'confirm_payment' => [
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' =>
                'Vui lòng chọn phương thức thanh toán.',

            'payment_method.in' =>
                'Phương thức thanh toán không hợp lệ.',

            'provider_transaction_code.max' =>
                'Mã giao dịch nhà cung cấp không được vượt quá 120 ký tự.',

            'payment_content.max' =>
                'Nội dung thanh toán không được vượt quá 255 ký tự.',

            'confirm_payment.accepted' =>
                'Bạn phải xác nhận trước khi ghi nhận thanh toán.',
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method' =>
                'phương thức thanh toán',

            'provider_transaction_code' =>
                'mã giao dịch nhà cung cấp',

            'payment_content' =>
                'nội dung thanh toán',

            'notes' =>
                'ghi chú',
        ];
    }
}