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

        // Existing nurse records remain editable until assigned a supported type.
        $types = array_keys(\App\Support\EmployeeAccess::TYPES);
        if (is_object($employee) && $employee->detail?->employee_type === 'NURSE') {
            $types[] = 'NURSE';
        }
        $rules['employee_type'] = ['required', 'string', Rule::exists('employee_types', 'code'), Rule::in($types)];
        $rules['expected_employee_type'] = ['nullable', 'string', 'max:80'];
        $rules['employee_type_change_reason'] = ['nullable', 'string', 'max:500'];

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
