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

            DB::table('business_code_sequences')->insertOrIgnore([
                'scope' => 'patient_account_code', 'last_value' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = DB::table('business_code_sequences')->where('scope', 'patient_account_code')->lockForUpdate()->firstOrFail();
            $next = max((int) $sequence->last_value, $this->maximumCode('users', 'business_code'), $this->maximumCode('patients', 'patient_code')) + 1;
            $code = 'BN'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            DB::table('business_code_sequences')->where('id', $sequence->id)->update(['last_value' => $next, 'updated_at' => now()]);

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

    private function maximumCode(string $table, string $column): int
    {
        $maximum = 0;
        DB::table($table)->select($column)->orderBy($column)->each(function ($record) use ($column, &$maximum): void {
            if (preg_match('/^BN(\d+)$/', (string) $record->{$column}, $matches)) {
                $maximum = max($maximum, (int) $matches[1]);
            }
        });

        return $maximum;
    }
}
