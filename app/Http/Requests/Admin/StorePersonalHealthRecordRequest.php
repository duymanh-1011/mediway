<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonalHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id'),
            ],

            'height_cm' => ['nullable', 'numeric', 'between:20,250'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.5,500'],

            'systolic_blood_pressure' => [
                'nullable', 'integer', 'between:40,300',
            ],
            'diastolic_blood_pressure' => [
                'nullable', 'integer', 'between:20,200',
            ],
            'heart_rate' => ['nullable', 'integer', 'between:20,250'],
            'body_temperature' => ['nullable', 'numeric', 'between:30,45'],
            'spo2' => ['nullable', 'numeric', 'between:0,100'],
            'respiratory_rate' => ['nullable', 'integer', 'between:5,100'],
            'blood_glucose' => ['nullable', 'numeric', 'between:0,1000'],

            'recorded_at' => [
                'required',
                'date_format:Y-m-d\TH:i',
                'before_or_equal:now',
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Vui lòng chọn bệnh nhân.',
            'patient_id.exists' => 'Bệnh nhân không tồn tại.',

            'height_cm.numeric' => 'Chiều cao phải là số.',
            'height_cm.between' => 'Chiều cao nằm ngoài khoảng nhập liệu cho phép.',

            'weight_kg.numeric' => 'Cân nặng phải là số.',
            'weight_kg.between' => 'Cân nặng nằm ngoài khoảng nhập liệu cho phép.',

            'systolic_blood_pressure.integer' => 'Huyết áp tâm thu phải là số nguyên.',
            'systolic_blood_pressure.between' => 'Huyết áp tâm thu không hợp lệ.',

            'diastolic_blood_pressure.integer' => 'Huyết áp tâm trương phải là số nguyên.',
            'diastolic_blood_pressure.between' => 'Huyết áp tâm trương không hợp lệ.',

            'heart_rate.integer' => 'Nhịp tim phải là số nguyên.',
            'heart_rate.between' => 'Nhịp tim không hợp lệ.',

            'body_temperature.numeric' => 'Nhiệt độ phải là số.',
            'body_temperature.between' => 'Nhiệt độ không hợp lệ.',

            'spo2.numeric' => 'SpO2 phải là số.',
            'spo2.between' => 'SpO2 phải nằm trong khoảng 0 đến 100%.',

            'respiratory_rate.integer' => 'Nhịp thở phải là số nguyên.',
            'respiratory_rate.between' => 'Nhịp thở không hợp lệ.',

            'blood_glucose.numeric' => 'Đường huyết phải là số.',
            'blood_glucose.between' => 'Đường huyết không hợp lệ.',

            'recorded_at.required' => 'Vui lòng chọn thời điểm ghi nhận.',
            'recorded_at.date_format' => 'Thời điểm ghi nhận không đúng định dạng.',
            'recorded_at.before_or_equal' => 'Không được ghi nhận thời điểm trong tương lai.',

            'notes.max' => 'Ghi chú không được quá 5000 ký tự.',
        ];
    }
}