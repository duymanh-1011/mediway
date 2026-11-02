<?php

namespace App\Http\Requests\Portal;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BrowseDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->canAccessPortal();
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
                        $query->where(
                            'status',
                            'ACTIVE'
                        )
                    ),
            ],

            'doctor_id' => [
                'nullable',
                'integer',

                Rule::exists('doctors', 'id')
                    ->where(
                        fn(Builder $query): Builder =>
                        $query->where(
                            'status',
                            'ACTIVE'
                        )
                    ),
            ],

            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'
                    . today()
                    ->addDays(
                        Appointment::BOOKING_ADVANCE_DAYS
                    )
                    ->toDateString(),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (
            Validator $validator
        ): void {

            if (
                $validator->errors()->has('specialty_id')
                || $validator->errors()->has('doctor_id')
            ) {
                return;
            }

            $specialtyId = $this->input('specialty_id');
            $doctorId = $this->input('doctor_id');

            if (
                ! filled($specialtyId)
                || ! filled($doctorId)
            ) {
                return;
            }

            /*
             * Kiểm tra bác sĩ có được phép khám
             * chuyên khoa đang chọn hay không.
             *
             * Sử dụng scopeForSpecialty()
             * đã có trong model Doctor.
             */
            $canPractice = Doctor::query()
                ->whereKey((int) $doctorId)
                ->where('status', 'ACTIVE')
                ->forSpecialty((int) $specialtyId)
                ->exists();

            if (! $canPractice) {

                $validator->errors()->add(
                    'doctor_id',
                    'Bác sĩ đã chọn không thuộc chuyên khoa này. '
                        . 'Vui lòng chọn lại bác sĩ.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'specialty_id.integer' =>
            'Chuyên khoa không hợp lệ.',

            'specialty_id.exists' =>
            'Chuyên khoa đã chọn không tồn tại '
                . 'hoặc đã ngừng hoạt động.',

            'doctor_id.integer' =>
            'Bác sĩ không hợp lệ.',

            'doctor_id.exists' =>
            'Bác sĩ đã chọn không tồn tại '
                . 'hoặc đã ngừng hoạt động.',

            'date.date_format' =>
            'Ngày khám không đúng định dạng.',

            'date.after_or_equal' =>
            'Ngày khám không được trước ngày hiện tại.',

            'date.before_or_equal' =>
            'Ngày khám chỉ được tra cứu '
                . 'trong phạm vi lịch đang nhận đặt.',
        ];
    }

    public function attributes(): array
    {
        return [
            'specialty_id' => 'chuyên khoa',
            'doctor_id' => 'bác sĩ',
            'date' => 'ngày khám',
        ];
    }
}
