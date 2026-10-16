<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Liên kết tài khoản Doctor có sẵn.
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('doctors', 'user_id'),
            ],

            // Chỉ bắt buộc khi không chọn tài khoản có sẵn.
            'phone' => [
                'required_without:user_id',
                'nullable',
                'regex:/^0[0-9]{9}$/',
                Rule::unique('users', 'phone'),
            ],
            'password' => [
                'required_without:user_id',
                'nullable',
                'string',
                'min:8',
                'max:100',
                'confirmed',
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
            'status' => [
                'required',
                Rule::in(['ACTIVE', 'INACTIVE']),
            ],

            // Thông tin cá nhân.
            'citizen_id_number' => [
                'nullable',
                'regex:/^[0-9]{12}$/',
                Rule::unique('doctor_details', 'citizen_id_number'),
            ],
            'citizen_id_issue_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'citizen_id_issue_place' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'hometown' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'ethnicity' => ['nullable', 'string', 'max:80'],

            // Hồ sơ chuyên môn.
            'education_level' => ['nullable', 'string', 'max:120'],
            'professional_qualification' => [
                'nullable', 'string', 'max:150',
            ],
            'academic_title_degree' => ['nullable', 'string', 'max:120'],
            'professional_title' => ['nullable', 'string', 'max:120'],
            'current_position' => ['nullable', 'string', 'max:120'],
            'current_workplace' => ['nullable', 'string', 'max:180'],
            'work_start_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'years_of_experience' => [
                'nullable', 'integer', 'between:0,100',
            ],
            'specialized_fields' => ['nullable', 'string', 'max:5000'],
            'professional_skills' => ['nullable', 'string', 'max:5000'],
            'languages' => ['nullable', 'string', 'max:2000'],

            // Giấy phép hành nghề.
            'practice_license_number' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique(
                    'doctor_details',
                    'practice_license_number'
                ),
            ],
            'practice_license_issue_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'practice_license_issue_place' => [
                'nullable', 'string', 'max:180',
            ],
            'practice_license_expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:practice_license_issue_date',
            ],
            'practice_scope' => ['nullable', 'string', 'max:5000'],
            'professional_bio' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:5000'],

            // Tài liệu: lưu trong private storage.
            'avatar' => [
                'nullable', 'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'citizen_id_front' => [
                'nullable', 'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'citizen_id_back' => [
                'nullable', 'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'practice_license_file' => [
                'nullable', 'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],
            'profile_pdf' => [
                'nullable', 'file',
                'mimes:pdf',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'Tài khoản được chọn không tồn tại.',
            'user_id.unique' => 'Tài khoản đã liên kết với bác sĩ khác.',

            'phone.required_without' =>
                'Vui lòng chọn tài khoản có sẵn hoặc nhập số điện thoại để tạo tài khoản mới.',
            'phone.regex' => 'Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',

            'password.required_without' =>
                'Vui lòng nhập mật khẩu cho tài khoản mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',

            'full_name.required' => 'Vui lòng nhập họ và tên bác sĩ.',
            'date_of_birth.before_or_equal' =>
                'Ngày sinh không được ở tương lai.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',

            'citizen_id_number.regex' => 'Số CCCD phải có 12 chữ số.',
            'citizen_id_number.unique' => 'Số CCCD đã tồn tại.',

            'email.email' => 'Email không đúng định dạng.',
            'years_of_experience.between' =>
                'Số năm kinh nghiệm phải từ 0 đến 100.',

            'practice_license_number.unique' =>
                'Số giấy phép hành nghề đã tồn tại.',
            'practice_license_expiry_date.after_or_equal' =>
                'Ngày hết hạn giấy phép không được trước ngày cấp.',

            'avatar.max' => 'Ảnh đại diện tối đa 2 MB.',
            'citizen_id_front.max' => 'Ảnh CCCD mặt trước tối đa 2 MB.',
            'citizen_id_back.max' => 'Ảnh CCCD mặt sau tối đa 2 MB.',
            'practice_license_file.max' =>
                'Tệp giấy phép hành nghề tối đa 5 MB.',
            'profile_pdf.max' => 'Hồ sơ PDF tối đa 5 MB.',
        ];
    }
}
