<?php

namespace App\Http\Requests\Portal;

use App\Models\Appointment;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrowseDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specialty_id' => [
                'nullable',
                'integer',
                Rule::exists('specialties', 'id')
                    ->where(
                        fn(Builder $query): Builder =>
                        $query->where('status', 'ACTIVE')
                    ),
            ],

            'doctor_id' => [
                'nullable',
                'integer',
                Rule::exists('doctors', 'id')
                    ->where(
                        fn(Builder $query): Builder =>
                        $query->where('status', 'ACTIVE')
                    ),
            ],

            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'
                    . today()
                    ->addDays(Appointment::BOOKING_ADVANCE_DAYS)
                    ->toDateString(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'specialty_id.integer' =>
            'Chuyên khoa không hợp lệ.',

            'specialty_id.exists' =>
            'Chuyên khoa đã chọn không tồn tại hoặc đã ngừng hoạt động.',

            'doctor_id.integer' =>
            'Bác sĩ không hợp lệ.',

            'doctor_id.exists' =>
            'Bác sĩ đã chọn không tồn tại hoặc đã ngừng hoạt động.',

            'date.date_format' =>
            'Ngày khám không đúng định dạng.',

            'date.after_or_equal' =>
            'Ngày khám không được trước ngày hiện tại.',

            'date.before_or_equal' =>
            'Ngày khám chỉ được tra cứu trong phạm vi lịch đang nhận đặt.',
        ];
    }
}
