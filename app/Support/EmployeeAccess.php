<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;

final class EmployeeAccess
{
    public static function homeRoute(Employee $employee): string
    {
        return 'employee.home';
    }

    public function employee(User $user): ?Employee
    {
        // Reload on every request; never trust a type stored in the session.
        $user->load(['role', 'employee.detail']);
        $employee = $user->employee;
        if ($user->status !== 'ACTIVE' || $user->role?->name !== 'Employee'
            || $user->role?->status !== 'ACTIVE' || $employee?->status !== 'ACTIVE') {
            return null;
        }

        return $employee;
    }

    public function allows(User $user, string $capability, ?string $workspace): bool
    {
        $employee = $this->employee($user);

        if ($employee === null) {
            return false;
        }

        return match ($workspace) {
            'reception' => in_array($capability, ['appointment.view', 'reception.check_in'], true),
            'cashier' => in_array($capability, ['payment.view', 'payment.collect'], true),
            default => false,
        };
    }
}
