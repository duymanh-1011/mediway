<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateServiceResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Lấy role Doctor
        |--------------------------------------------------------------------------
        */
        $doctorRoleId = DB::table('roles')
            ->where('name', 'Doctor')
            ->value('id');

        return [
            'result_summary' => [
                'nullable',
                'string',
            ],

            'result_conclusion' => [
                'nullable',
                'string',
            ],

            'result_parameters' => [
                'nullable',
                'json',
            ],

            'result_pdf_path' => [
                'nullable',
                'string',
                'max:500',
            ],

            'result_image_path' => [
                'nullable',
                'string',
                'max:500',
            ],

            'verified_by_user_id' => [
                'nullable',
                'integer',

                Rule::exists('users', 'id')
                    ->where(function ($query) use ($doctorRoleId) {
                        $query->where(
                            'role_id',
                            $doctorRoleId
                        );
                    }),
            ],

            'resulted_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'confirm_update' => [
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'result_parameters.json' =>
            'Thông số kết quả phải là JSON hợp lệ.',

            'result_pdf_path.max' =>
            'Đường dẫn PDF không được vượt quá 500 ký tự.',

            'result_image_path.max' =>
            'Đường dẫn ảnh không được vượt quá 500 ký tự.',

            'verified_by_user_id.integer' =>
            'Người xác nhận không hợp lệ.',

            'verified_by_user_id.exists' =>
            'Người xác nhận phải là bác sĩ hợp lệ.',

            'resulted_at.date' =>
            'Thời gian trả kết quả không hợp lệ.',

            'confirm_update.accepted' =>
            'Bạn phải xác nhận trước khi cập nhật kết quả y tế.',
        ];
    }
}
