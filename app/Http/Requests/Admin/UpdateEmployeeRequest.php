<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $employee = $this->route('employee');

        $employeeId = is_object($employee)
            ? $employee->getKey()
            : $employee;

        /*
         * Không cho thay đổi tài khoản liên kết
         * qua form sửa hồ sơ nhân viên.
         */
        unset(
            $rules['user_id'],
            $rules['phone'],
            $rules['password']
        );

        /*
         * CCCD được phép giữ nguyên khi chỉnh sửa,
         * nhưng không được trùng với nhân viên khác.
         */
        $rules['citizen_id_number'] = [
            'nullable',
            'regex:/^[0-9]{12}$/',
            Rule::unique(
                'employee_details',
                'citizen_id_number'
            )->where(function ($query) use ($employeeId) {
                $query->where(
                    'employee_id',
                    '!=',
                    $employeeId
                );
            }),
        ];

        return $rules;
    }
}
