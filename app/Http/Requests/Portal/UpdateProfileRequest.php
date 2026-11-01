<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canAccessPortal();
    }

    public function rules(): array
    {
        $patient = $this->user()?->patient;
        $detail = $patient?->detail;
        return [
            'booking_slot' => ['sometimes', 'nullable', 'integer', 'exists:appointment_time_slots,id'],
            'full_name' => ['sometimes', 'required', 'string', 'min:2', 'max:120', 'regex:/\p{L}/u'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'hometown' => ['sometimes', 'nullable', 'string', 'max:255'],
            'permanent_address' => ['sometimes', 'nullable', 'string', 'min:5', 'max:255', 'regex:/\p{L}/u'],
            'current_address' => ['sometimes', 'nullable', 'string', 'min:5', 'max:255', 'regex:/\p{L}/u'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:80'],
            'ethnicity' => ['sometimes', 'nullable', 'string', 'max:80'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:120'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:120', 'regex:/\p{L}/u'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'regex:/^0[0-9]{9}$/'],
            'emergency_contact_relationship' => ['sometimes', 'nullable', 'string', 'max:80'],
            'date_of_birth' => $patient?->date_of_birth ? ['prohibited'] : ['sometimes', 'nullable', 'date', 'after_or_equal:'.today()->subYears(120)->toDateString(), 'before_or_equal:today'],
            'gender' => $patient?->gender ? ['prohibited'] : ['sometimes', 'nullable', Rule::in(['Nam', 'Nữ', 'Khác'])],
            'citizen_id_number' => $detail?->citizen_id_number ? ['prohibited'] : ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{12}$/', Rule::unique('patient_details', 'citizen_id_number')],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.regex' => 'Họ và tên phải có chữ cái.',
            'full_name.min' => 'Họ và tên phải có ít nhất 2 ký tự.',
            'current_address.min' => 'Địa chỉ phải có ít nhất 5 ký tự.',
            'permanent_address.min' => 'Địa chỉ phải có ít nhất 5 ký tự.',
            'current_address.regex' => 'Địa chỉ phải có chữ cái.',
            'permanent_address.regex' => 'Địa chỉ phải có chữ cái.',
            'emergency_contact_name.min' => 'Họ tên liên hệ khẩn cấp phải có ít nhất 2 ký tự.',
            'emergency_contact_name.regex' => 'Họ tên liên hệ khẩn cấp phải có chữ cái.',
            'date_of_birth.after_or_equal' => 'Ngày sinh không được cách hiện tại quá 120 năm.',
            'email.email' => 'Email không đúng định dạng.',
            'emergency_contact_phone.regex' => 'Số điện thoại liên hệ khẩn cấp phải gồm 10 chữ số và bắt đầu bằng 0.',
            'date_of_birth.date' => 'Ngày sinh không hợp lệ.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không thể ở tương lai.',
            'gender.in' => 'Vui lòng chọn giới tính hợp lệ.',
            'citizen_id_number.regex' => 'Số CCCD phải gồm 12 chữ số.',
            'citizen_id_number.unique' => 'Số CCCD đã được sử dụng.',
            '*.max' => 'Trường :attribute vượt quá số ký tự cho phép.',
            '*.prohibited' => 'Thông tin này hiện không được phép chỉnh sửa.',
        ];
    }
}
