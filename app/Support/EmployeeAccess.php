<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;

final class EmployeeAccess
{
    public const SERVICE_TECHNICIAN = 'SERVICE_TECHNICIAN';

    public const TYPES = [
        'RECEPTIONIST' => 'Nhân viên tiếp đón',
        'CASHIER' => 'Nhân viên thu ngân',
        self::SERVICE_TECHNICIAN => 'Kỹ thuật viên dịch vụ y tế',
    ];

    public const TECHNICIAN_CODES = [self::SERVICE_TECHNICIAN, 'LAB_TECHNICIAN', 'IMAGING_TECHNICIAN'];

    private const CAPABILITIES = [
        'RECEPTIONIST' => ['appointment.view', 'reception.check_in'],
        'CASHIER' => ['payment.view', 'payment.collect'],
        self::SERVICE_TECHNICIAN => ['services.view', 'services.process'],
    ];

    public static function typeCode(?string $type): ?string
    {
        return in_array($type, self::TECHNICIAN_CODES, true) ? self::SERVICE_TECHNICIAN : $type;
    }

    public static function homeRoute(Employee $employee): string
    {
        return self::typeCode($employee->detail?->employee_type) === self::SERVICE_TECHNICIAN
            ? 'employee.services.index'
            : 'employee.home';
    }

    public function employee(User $user): ?Employee
    {
        // Reload on every request; never trust a type stored in the session.
        $user->load(['role', 'employee.detail']);
        $employee = $user->employee;
        if ($user->status !== 'ACTIVE' || $user->role?->name !== 'Employee'
            || $user->role?->status !== 'ACTIVE' || $employee?->status !== 'ACTIVE'
            || ! isset(self::TYPES[self::typeCode($employee->detail?->employee_type)])) {
            return null;
        }

        return $employee;
    }

    public function allows(User $user, string $capability): bool
    {
        $employee = $this->employee($user);

        return $employee !== null && in_array($capability, self::CAPABILITIES[self::typeCode($employee->detail->employee_type)], true);
    }
}
