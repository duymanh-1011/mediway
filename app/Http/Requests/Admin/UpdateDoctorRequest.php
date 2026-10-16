<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends StoreDoctorRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $doctor = $this->route('doctor');
        $doctorId = is_object($doctor)
            ? $doctor->getKey()
            : $doctor;

        // Không đổi tài khoản từ form sửa hồ sơ bác sĩ.
        unset(
            $rules['user_id'],
            $rules['phone'],
            $rules['password']
        );

        $rules['citizen_id_number'] = [
            'nullable',
            'regex:/^[0-9]{12}$/',
            Rule::unique(
                'doctor_details',
                'citizen_id_number'
            )->where(function ($query) use ($doctorId) {
                $query->where('doctor_id', '!=', $doctorId);
            }),
        ];

        $rules['practice_license_number'] = [
            'nullable',
            'string',
            'max:80',
            Rule::unique(
                'doctor_details',
                'practice_license_number'
            )->where(function ($query) use ($doctorId) {
                $query->where('doctor_id', '!=', $doctorId);
            }),
        ];

        return $rules;
    }
}
