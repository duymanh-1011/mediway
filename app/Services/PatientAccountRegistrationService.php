<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientAccountRegistrationService
{
    public function register(string $fullName, string $phone, string $password): Patient
    {
        return DB::transaction(function () use ($fullName, $phone, $password): Patient {
            $role = Role::query()->where('name', 'Patient')->where('status', 'ACTIVE')->lockForUpdate()->first();
            if (! $role) {
                throw ValidationException::withMessages(['phone' => 'Hệ thống chưa sẵn sàng tiếp nhận đăng ký bệnh nhân. Vui lòng thử lại sau.']);
            }

            $code = app(PatientCodeService::class)->next();

            $user = User::create([
                'business_code' => $code, 'phone' => $phone, 'password' => $password,
                'role_id' => $role->id, 'status' => 'ACTIVE',
            ]);

            $patient = Patient::create([
                'user_id' => $user->id, 'patient_code' => $code, 'full_name' => $fullName, 'status' => 'ACTIVE',
            ]);
            AuditLog::create([
                'user_id' => null, 'role_name_snapshot' => null, 'action' => 'CREATE',
                'entity_type' => 'users', 'entity_id' => $user->id, 'entity_business_code' => $code,
                'old_values' => [], 'new_values' => [], 'description' => 'Đăng ký tài khoản bệnh nhân.',
                'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'performed_at' => now(),
            ]);

            return $patient;
        }, 3);
    }

}
