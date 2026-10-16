<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Tài khoản Employee có sẵn.
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id'),
            ],

            // Tạo tài khoản mới khi không chọn user_id.
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

            // Bảng employees.
            'full_name' => [
                'required',
                'string',
                'max:120',
            ],

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
                Rule::unique(
                    'employee_details',
                    'citizen_id_number'
                ),
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

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'hometown' => [
                'nullable',
                'string',
                'max:255',
            ],

            'permanent_address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'current_address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nationality' => [
                'nullable',
                'string',
                'max:80',
            ],

            'ethnicity' => [
                'nullable',
                'string',
                'max:80',
            ],

            // Trình độ.
            'education_level' => [
                'nullable',
                'string',
                'max:120',
            ],

            'professional_qualification' => [
                'nullable',
                'string',
                'max:150',
            ],

            // Thông tin công việc.
            'department' => [
                'nullable',
                'string',
                'max:120',
            ],

            'position' => [
                'nullable',
                'string',
                'max:120',
            ],

            'employee_type' => [
                'nullable',
                'string',
                'max:80',
            ],

            'hire_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'termination_date' => [
                'nullable',
                'date',
                'after_or_equal:hire_date',
            ],

            'years_of_service' => [
                'nullable',
                'numeric',
                'between:0,999.99',
            ],

            'primary_shift' => [
                'nullable',
                'string',
                'max:80',
            ],

            'work_location' => [
                'nullable',
                'string',
                'max:180',
            ],

            'job_description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'professional_skills' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            // Tài liệu đính kèm.
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
            'user_id.exists' =>
                'Tài khoản được chọn không tồn tại.',

            'user_id.unique' =>
                'Tài khoản đã được liên kết với nhân viên khác.',

            'phone.required_without' =>
                'Vui lòng chọn tài khoản Employee có sẵn hoặc nhập số điện thoại mới.',

            'phone.regex' =>
                'Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.',

            'phone.unique' =>
                'Số điện thoại đã được sử dụng.',

            'password.required_without' =>
                'Vui lòng nhập mật khẩu khi tạo tài khoản mới.',

            'password.min' =>
                'Mật khẩu phải có ít nhất 8 ký tự.',

            'password.confirmed' =>
                'Xác nhận mật khẩu không khớp.',

            'full_name.required' =>
                'Vui lòng nhập họ và tên nhân viên.',

            'full_name.max' =>
                'Họ và tên không được vượt quá 120 ký tự.',

            'date_of_birth.before_or_equal' =>
                'Ngày sinh không được ở tương lai.',

            'gender.in' =>
                'Giới tính không hợp lệ.',

            'status.required' =>
                'Vui lòng chọn trạng thái nhân viên.',

            'status.in' =>
                'Trạng thái nhân viên không hợp lệ.',

            'citizen_id_number.regex' =>
                'Số CCCD phải có đúng 12 chữ số.',

            'citizen_id_number.unique' =>
                'Số CCCD đã tồn tại trong hồ sơ nhân viên.',

            'citizen_id_issue_date.before_or_equal' =>
                'Ngày cấp CCCD không được ở tương lai.',

            'email.email' =>
                'Email không đúng định dạng.',

            'hire_date.before_or_equal' =>
                'Ngày tuyển dụng không được ở tương lai.',

            'termination_date.after_or_equal' =>
                'Ngày nghỉ việc không được trước ngày tuyển dụng.',

            'years_of_service.numeric' =>
                'Số năm công tác phải là số.',

            'years_of_service.between' =>
                'Số năm công tác phải từ 0 đến 999,99.',

            'avatar.max' =>
                'Ảnh đại diện không được vượt quá 2 MB.',

            'citizen_id_front.max' =>
                'Ảnh CCCD mặt trước không được vượt quá 2 MB.',

            'citizen_id_back.max' =>
                'Ảnh CCCD mặt sau không được vượt quá 2 MB.',

            'profile_pdf.max' =>
                'Hồ sơ PDF không được vượt quá 5 MB.',
        ];
    }
}
