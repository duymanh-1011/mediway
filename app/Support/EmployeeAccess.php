<?php

namespace App\Support;

use App\Models\User;

final class EmployeeAccess
{
    public const TYPES = [
        'RECEPTIONIST' => 'Nhân viên tiếp đón',
        'LAB_TECHNICIAN' => 'Kỹ thuật viên xét nghiệm',
        'IMAGING_TECHNICIAN' => 'Kỹ thuật viên chẩn đoán hình ảnh',
        'CASHIER' => 'Nhân viên thu ngân',
    ];

    private const CAPABILITIES = [
        'RECEPTIONIST' => ['appointment.view', 'reception.check_in', 'queue.view'],
        'LAB_TECHNICIAN' => ['laboratory.view', 'laboratory.process'],
        'IMAGING_TECHNICIAN' => ['imaging.view', 'imaging.process'],
        'CASHIER' => ['payment.view', 'payment.collect'],
    ];

    public function employee(User $user): ?\App\Models\Employee
    {
        // Reload on every request; never trust a type stored in the session.
        $user->load(['role', 'employee.detail']);
        $employee = $user->employee;
        if ($user->status !== 'ACTIVE' || $user->role?->name !== 'Employee'
            || $user->role?->status !== 'ACTIVE' || $employee?->status !== 'ACTIVE'
            || ! isset(self::TYPES[$employee?->detail?->employee_type ?? ''])) {
            return null;
        }

        return $employee;
    }

    public function allows(User $user, string $capability): bool
    {
        $employee = $this->employee($user);
        return $employee !== null && in_array($capability, self::CAPABILITIES[$employee->detail->employee_type], true);
    }
}
