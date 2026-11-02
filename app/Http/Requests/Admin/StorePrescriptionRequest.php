<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'general_instructions' => ['nullable', 'string', 'max:5000'],
            'doctor_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.medicine_id' => ['required', 'integer', 'distinct', Rule::exists('medicines', 'id')->where('status', 'ACTIVE')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
            'items.*.usage_unit' => ['required', 'string', 'max:50'],
            'items.*.dose_per_use' => ['required', 'string', 'max:80'],
            'items.*.times_per_day' => ['required', 'numeric', 'min:0.01', 'max:999.99', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'items.*.duration_days' => ['required', 'integer', 'min:1', 'max:65535'],
            'items.*.administration_time' => ['nullable', 'string', 'max:120'],
            'items.*.usage_method' => ['required', 'string', 'max:120'],
            'items.*.usage_instructions' => ['nullable', 'string', 'max:5000'],
            'confirm_create' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Vui lòng thêm ít nhất một thuốc.',
            'items.*.medicine_id.required' => 'Vui lòng chọn thuốc.',
            'items.*.medicine_id.exists' => 'Thuốc không tồn tại hoặc đã ngừng hoạt động.',
            'items.*.medicine_id.distinct' => 'Mỗi thuốc chỉ được chọn một lần trong đơn.',
            'items.*.quantity.*' => 'Số lượng phải lớn hơn 0 và có tối đa 2 chữ số thập phân.',
            'items.*.usage_unit.required' => 'Vui lòng nhập đơn vị tính.',
            'items.*.dose_per_use.required' => 'Vui lòng nhập liều mỗi lần.',
            'items.*.times_per_day.*' => 'Số lần mỗi ngày phải lớn hơn 0 và có tối đa 2 chữ số thập phân.',
            'items.*.duration_days.*' => 'Số ngày dùng phải là số nguyên lớn hơn 0.',
            'items.*.usage_method.required' => 'Vui lòng nhập cách dùng.',
            'confirm_create.accepted' => 'Vui lòng xác nhận thông tin trước khi lưu đơn thuốc.',
        ];
    }
}
