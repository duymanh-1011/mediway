<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayableStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_status_id' => [
                'required',
                'integer',

                Rule::exists(
                    'payment_statuses',
                    'id'
                )->where(function ($query) {
                    $query->where(
                        'is_active',
                        true
                    );
                }),
            ],

            'confirm_update' => [
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_status_id.required' => 'Vui lòng chọn trạng thái thanh toán.',

            'payment_status_id.integer' => 'Trạng thái thanh toán không hợp lệ.',

            'payment_status_id.exists' => 'Trạng thái thanh toán không hợp lệ.',

            'confirm_update.accepted' => 'Vui lòng xác nhận thay đổi trạng thái trước khi lưu.',
        ];
    }
}
