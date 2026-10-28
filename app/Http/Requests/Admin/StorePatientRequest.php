<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('patients', 'user_id'),
            ],

            'full_name' => ['required', 'string', 'max:120'],

            'date_of_birth' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'gender' => [
                'nullable',
                Rule::in(['Nam', 'Nữ', 'Khác']),
            ],

            // Thông tin giấy tờ
            'citizen_id_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{12}$/',
                Rule::unique('patient_details', 'citizen_id_number'),
            ],

            'citizen_id_issue_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'citizen_id_issue_place' => [
                'nullable',
                'string',
                'max:150',
            ],

            'email' => ['nullable', 'email', 'max:150'],
            'hometown' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'ethnicity' => ['nullable', 'string', 'max:80'],
            'occupation' => ['nullable', 'string', 'max:120'],

            'blood_type' => [
                'nullable',
                Rule::in([
                    'A+', 'A-',
                    'B+', 'B-',
                    'AB+', 'AB-',
                    'O+', 'O-',
                ]),
            ],

            // Liên hệ khẩn cấp
            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:120',
            ],

            'emergency_contact_phone' => [
                'nullable',
                'string',
                'regex:/^0[0-9]{9}$/',
            ],

            'emergency_contact_relationship' => [
                'nullable',
                'string',
                'max:80',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            // Tệp đính kèm - tối đa 2 MB mỗi ảnh
            'avatar' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'citizen_id_front' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'citizen_id_back' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            // Hồ sơ PDF tối đa 5 MB
            'profile_pdf' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'Tài khoản được chọn không tồn tại.',
            'user_id.unique' => 'Tài khoản đã liên kết với bệnh nhân khác.',

            'full_name.required' => 'Vui lòng nhập họ tên bệnh nhân.',
            'full_name.max' => 'Họ tên không được quá 120 ký tự.',

            'date_of_birth.date' => 'Ngày sinh chưa hợp lệ. Vui lòng chọn lại ngày bằng ô lịch.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không thể ở tương lai.',

            'gender.in' => 'Vui lòng chọn giới tính từ các lựa chọn có sẵn.',

            'citizen_id_number.regex' => 'Số CCCD phải gồm 12 chữ số.',
            'citizen_id_number.unique' => 'Số CCCD đã được sử dụng.',

            'citizen_id_issue_date.date' => 'Ngày cấp căn cước công dân chưa hợp lệ. Vui lòng chọn lại ngày bằng ô lịch.',
            'citizen_id_issue_date.before_or_equal' => 'Ngày cấp CCCD không thể ở tương lai.',

            'email.email' => 'Email không đúng định dạng.',
            'blood_type.in' => 'Vui lòng chọn nhóm máu từ các lựa chọn có sẵn.',

            'emergency_contact_phone.regex' => 'Số điện thoại liên hệ khẩn cấp phải gồm 10 chữ số và bắt đầu bằng 0.',

            'avatar.mimes' => 'Ảnh đại diện phải là JPG, PNG hoặc WEBP.',
            'avatar.max' => 'Ảnh đại diện không được quá 2 MB.',

            'citizen_id_front.mimes' => 'Ảnh CCCD mặt trước phải là JPG, PNG hoặc WEBP.',
            'citizen_id_front.max' => 'Ảnh CCCD mặt trước không được quá 2 MB.',

            'citizen_id_back.mimes' => 'Ảnh CCCD mặt sau phải là JPG, PNG hoặc WEBP.',
            'citizen_id_back.max' => 'Ảnh CCCD mặt sau không được quá 2 MB.',

            'profile_pdf.mimes' => 'Hồ sơ đính kèm phải là tệp PDF.',
            'profile_pdf.max' => 'Hồ sơ PDF không được quá 5 MB.',
        ];
    }
}
