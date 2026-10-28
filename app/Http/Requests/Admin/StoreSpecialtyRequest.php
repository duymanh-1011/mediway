<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specialty_code' => [
                'exclude',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
                'unique:specialties,name',
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

            'specialty_code.max' => 'Mã chuyên khoa không được vượt quá 30 ký tự.',

            'specialty_code.unique' => 'Mã chuyên khoa đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên chuyên khoa.',

            'name.max' => 'Tên chuyên khoa không được vượt quá 150 ký tự.',

            'name.unique' => 'Tên chuyên khoa đã tồn tại.',

            'image.image' => 'Tệp tải lên phải là hình ảnh.',

            'image.mimes' => 'Ảnh chỉ chấp nhận JPG, JPEG, PNG hoặc WEBP.',

            'image.max' => 'Dung lượng ảnh không được vượt quá 2MB.',

            'status.required' => 'Vui lòng chọn trạng thái.',

            'status.in' => 'Vui lòng chọn lại trạng thái chuyên khoa từ các lựa chọn có sẵn.',
        ];
    }
}
