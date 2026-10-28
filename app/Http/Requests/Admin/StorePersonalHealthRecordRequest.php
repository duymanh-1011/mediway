<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /** @return array<\Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $measurements = ['height_cm', 'weight_kg', 'systolic_blood_pressure', 'diastolic_blood_pressure',
                'heart_rate', 'body_temperature', 'spo2', 'respiratory_rate', 'blood_glucose'];
            if (! collect($measurements)->contains(fn ($field) => $this->filled($field))) {
                $validator->errors()->add('height_cm', 'Vui lòng nhập ít nhất một chỉ số sức khỏe trước khi lưu lần đo.');
            }
            if ($validator->errors()->has('height_cm') || $validator->errors()->has('weight_kg')
                || ! $this->filled('height_cm') || ! $this->filled('weight_kg')) {
                return;
            }
            $heightInMeters = (float) $this->input('height_cm') / 100;
            $bmi = round((float) $this->input('weight_kg') / ($heightInMeters * $heightInMeters), 2);
            if ($bmi > 999.99) {
                $validator->errors()->add('weight_kg', 'BMI tính từ chiều cao và cân nặng vượt giới hạn lưu trữ 999,99. Vui lòng kiểm tra lại các chỉ số.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Vui lòng chọn bệnh nhân.',
            'patient_id.exists' => 'Bệnh nhân không tồn tại.',

            'height_cm.numeric' => 'Chiều cao phải là số.',
            'height_cm.between' => 'Chiều cao phải từ :min đến :max cm. Vui lòng kiểm tra lại số đã nhập.',

            'weight_kg.numeric' => 'Cân nặng phải là số.',
            'weight_kg.between' => 'Cân nặng phải từ :min đến :max kg. Vui lòng kiểm tra lại số đã nhập.',

            'systolic_blood_pressure.integer' => 'Huyết áp tâm thu phải là số nguyên.',
            'systolic_blood_pressure.between' => 'Huyết áp tâm thu phải từ :min đến :max mmHg. Vui lòng kiểm tra lại số đã nhập.',

            'diastolic_blood_pressure.integer' => 'Huyết áp tâm trương phải là số nguyên.',
            'diastolic_blood_pressure.between' => 'Huyết áp tâm trương phải từ :min đến :max mmHg. Vui lòng kiểm tra lại số đã nhập.',

            'heart_rate.integer' => 'Nhịp tim phải là số nguyên.',
            'heart_rate.between' => 'Nhịp tim phải từ :min đến :max lần/phút. Vui lòng kiểm tra lại số đã nhập.',

            'body_temperature.numeric' => 'Nhiệt độ phải là số.',
            'body_temperature.between' => 'Nhiệt độ phải từ :min đến :max °C. Vui lòng kiểm tra lại số đã nhập.',

            'spo2.numeric' => 'SpO2 phải là số.',
            'spo2.between' => 'SpO2 phải nằm trong khoảng 0 đến 100%.',

            'respiratory_rate.integer' => 'Nhịp thở phải là số nguyên.',
            'respiratory_rate.between' => 'Nhịp thở phải từ :min đến :max lần/phút. Vui lòng kiểm tra lại số đã nhập.',

            'blood_glucose.numeric' => 'Đường huyết phải là số.',
            'blood_glucose.between' => 'Đường huyết phải từ :min đến :max theo đơn vị ghi trên biểu mẫu. Vui lòng kiểm tra lại số đã nhập.',

            'recorded_at.required' => 'Vui lòng chọn thời điểm ghi nhận.',
            'recorded_at.date_format' => 'Thời điểm ghi nhận chưa hợp lệ. Vui lòng chọn đủ ngày và giờ.',
            'recorded_at.before_or_equal' => 'Thời điểm ghi nhận không được sau thời điểm hiện tại. Vui lòng kiểm tra lại ngày và giờ.',

            'notes.max' => 'Ghi chú không được quá 5000 ký tự.',
        ];
    }
}
