<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Doctor;
use Illuminate\Validation\Validator;

trait ValidatesDoctorSpecialty
{
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->hasAny(['doctor_id', 'specialty_id'])) {
                return;
            }
            $schedule = $this->route('doctor_schedule');
            if (is_object($schedule) && $this->input('status') === 'INACTIVE'
                && (int) $schedule->doctor_id === $this->integer('doctor_id')
                && (int) $schedule->specialty_id === $this->integer('specialty_id')) {
                return;
            }
            $doctor = Doctor::with('specialty')->find($this->integer('doctor_id'));
            if (! $doctor?->canPracticeSpecialty($this->integer('specialty_id'))) {
                $validator->errors()->add('doctor_id', 'Bác sĩ không thuộc chuyên khoa đã chọn. Vui lòng chọn đúng bác sĩ của chuyên khoa hoặc bác sĩ Đa khoa.');
            }
        }];
    }
}
