<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\PatientDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientAccountRegistrationService
{
    private const ACTIVATION_TABLE = 'patient_registration_activations';

    public function register(string $fullName, string $phone, string $password, string $citizenId, ?string $activationCode = null): Patient
    {
        return DB::transaction(function () use ($fullName, $phone, $password, $citizenId, $activationCode): Patient {
            $role = Role::query()->where('name', 'Patient')->where('status', 'ACTIVE')->lockForUpdate()->first();
            if (! $role) {
                throw ValidationException::withMessages(['phone' => 'Hệ thống chưa sẵn sàng tiếp nhận đăng ký bệnh nhân. Vui lòng thử lại sau.']);
            }

            $patientId = PatientDetail::where('citizen_id_number', $citizenId)->value('patient_id');
            $patient = $patientId ? Patient::whereKey($patientId)->lockForUpdate()->first() : null;
            if ($patient && ! $patient->detail()->where('citizen_id_number', $citizenId)->lockForUpdate()->first()) {
                $patient = null;
            }
            if ($patient?->user_id !== null) {
                throw ValidationException::withMessages([
                    'citizen_id_number' => 'Tài khoản đã tồn tại. Vui lòng đăng nhập hoặc chọn Quên mật khẩu.',
                ]);
            }
            if ($patientId !== null || $activationCode !== null) {
                $this->verifyActivation($patient, $citizenId, $activationCode);
            }
            $code = $patient?->patient_code ?? app(PatientCodeService::class)->next();

            $user = User::create([
                'business_code' => $code, 'phone' => $phone, 'password' => $password,
                'role_id' => $role->id, 'status' => 'ACTIVE',
            ]);

            $linkedExisting = $patient !== null;
            if ($linkedExisting) {
                $patient->update(['user_id' => $user->id]);
                DB::table(self::ACTIVATION_TABLE)->where('patient_id', $patient->id)->delete();
            } else {
                // An unmatched CCCD is a search term, not verified identity for a new profile.
                $patient = Patient::create([
                    'user_id' => $user->id, 'patient_code' => $code, 'full_name' => $fullName, 'status' => 'ACTIVE',
                ]);
            }
            AuditLog::create([
                'user_id' => null, 'role_name_snapshot' => null, 'action' => 'CREATE',
                'entity_type' => 'users', 'entity_id' => $user->id, 'entity_business_code' => $code,
                'old_values' => [], 'new_values' => $linkedExisting ? ['patient_id' => $patient->id] : [],
                'description' => $linkedExisting ? 'Đăng ký tài khoản và liên kết hồ sơ bệnh nhân đã xác minh.' : 'Đăng ký tài khoản bệnh nhân.',
                'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'performed_at' => now(),
            ]);

            return $patient;
        }, 3);
    }

    public function issueActivationCode(User $actor, Patient $patient, bool $asAdministrator): string
    {
        return DB::transaction(function () use ($actor, $patient, $asAdministrator): string {
            app(ReceptionAccess::class)->lockAndAuthorize($actor, $asAdministrator);
            $patient = Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $citizenId = $patient->detail()->lockForUpdate()->value('citizen_id_number');

            if ($patient->status !== 'ACTIVE' || $patient->user_id !== null || ! is_string($citizenId) || ! preg_match('/^[0-9]{12}$/', $citizenId)) {
                throw ValidationException::withMessages(['activation' => 'Chỉ cấp mã cho hồ sơ đang hoạt động, có CCCD hợp lệ và chưa liên kết tài khoản.']);
            }

            $code = strtoupper(bin2hex(random_bytes(10)));
            DB::table(self::ACTIVATION_TABLE)->updateOrInsert(['patient_id' => $patient->id], [
                'code_hash' => $this->activationHash($citizenId, $code), 'expires_at' => now()->addDay(),
            ]);
            AuditLog::create([
                'user_id' => $actor->id, 'role_name_snapshot' => $asAdministrator ? 'Administrator' : 'Employee',
                'action' => 'ISSUE_REGISTRATION_ACTIVATION', 'entity_type' => 'patients', 'entity_id' => $patient->id,
                'entity_business_code' => $patient->patient_code, 'description' => 'Cấp mã kích hoạt tài khoản cho hồ sơ bệnh nhân.',
                'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'performed_at' => now(),
            ]);

            return $code;
        }, 3);
    }

    private function verifyActivation(?Patient $patient, string $citizenId, ?string $code): void
    {
        if ($code !== null && $patient?->status === 'ACTIVE') {
            $activation = DB::table(self::ACTIVATION_TABLE)->where('patient_id', $patient->id)
                ->where('expires_at', '>', now())->lockForUpdate()->first();
            if ($activation && hash_equals($activation->code_hash, $this->activationHash($citizenId, $code))) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'activation_code' => 'Không thể xác minh mã kích hoạt cho thông tin đã nhập. Nếu đã có hồ sơ tại Mediway, hãy liên hệ quầy tiếp đón để được hỗ trợ.',
        ]);
    }

    private function activationHash(string $citizenId, string $code): string
    {
        return hash('sha256', $citizenId.':'.strtoupper($code));
    }
}
