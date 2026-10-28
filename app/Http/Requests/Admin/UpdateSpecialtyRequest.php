<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecialtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $specialty = $this->route('specialty');

        return [
            'specialty_code' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'specialties',
                    'specialty_code'
                )->ignore($specialty->id),
            ],

            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'specialties',
                    'name'
                )->ignore($specialty->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'remove_image' => [
                'nullable',
                'boolean',
            ],

            'pre_visit_instructions' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:ACTIVE,INACTIVE',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'specialty_code.required' => 'Vui lòng nhập mã chuyên khoa.',

            'specialty_code.unique' => 'Mã chuyên khoa đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên chuyên khoa.',

            'name.unique' => 'Tên chuyên khoa đã tồn tại.',

            'image.image' => 'Tệp tải lên phải là hình ảnh.',

            'image.mimes' => 'Ảnh chỉ chấp nhận JPG, JPEG, PNG hoặc WEBP.',

            'image.max' => 'Dung lượng ảnh không được vượt quá 2MB.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái chuyên khoa từ các lựa chọn có sẵn.',
        ];
    }
}
