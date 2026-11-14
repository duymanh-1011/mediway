<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PatientDetail;
use App\Models\QueueStatus;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalkInReceptionService
{
    /** @return array{visit: MedicalVisit, credentials: array{phone: string, password: string}|null} */
    public function receive(User $actor, array $data, bool $asAdministrator = false): array
    {
        return DB::transaction(function () use ($actor, $data, $asAdministrator): array {
            app(ReceptionAccess::class)->lockAndAuthorize($actor, $asAdministrator);
            $roleName = $asAdministrator ? 'Administrator' : 'Employee';
            if (! empty($data['request_token'])) {
                $previous = AuditLog::where('user_id', $actor->id)->where('action', 'WALK_IN_REQUEST')
                    ->where('entity_type', 'MedicalVisit')->where('entity_business_code', $data['request_token'])->lockForUpdate()->first();
                if ($previous) {
                    return ['visit' => MedicalVisit::findOrFail($previous->entity_id), 'credentials' => null];
                }
            }
            $schedule = DoctorSchedule::whereKey($data['schedule_id'])->lockForUpdate()->firstOrFail();
            if ($schedule->status !== 'ACTIVE' || ! $schedule->work_date?->isToday()
                || now()->format('H:i:s') < $schedule->start_time || now()->format('H:i:s') >= $schedule->end_time) {
                throw ValidationException::withMessages(['schedule_id' => 'Phòng khám hoặc bác sĩ vừa ngừng tiếp nhận. Vui lòng chọn lại phòng khám.']);
            }
            $doctor = Doctor::whereKey($schedule->doctor_id)->lockForUpdate()->firstOrFail();
            $patientRole = Role::where('name', 'Patient')->where('status', 'ACTIVE')->sharedLock()->first();
            if ($data['patient_mode'] === 'existing') {
                $patient = Patient::whereKey($data['patient_id'])->lockForUpdate()->firstOrFail();
                $identityUpdates = [];
                if (blank($patient->date_of_birth)) {
                    if (blank($data['date_of_birth'] ?? null)) {
                        throw ValidationException::withMessages(['date_of_birth' => 'Vui lòng bổ sung ngày sinh còn thiếu trong hồ sơ bệnh nhân.']);
                    }
                    $identityUpdates['date_of_birth'] = $data['date_of_birth'];
                }
                if (blank($patient->gender)) {
                    if (blank($data['gender'] ?? null)) {
                        throw ValidationException::withMessages(['gender' => 'Vui lòng bổ sung giới tính còn thiếu trong hồ sơ bệnh nhân.']);
                    }
                    $identityUpdates['gender'] = $data['gender'];
                }
                if ($identityUpdates !== []) {
                    $oldIdentity = array_intersect_key($patient->getOriginal(), $identityUpdates);
                    $patient->update($identityUpdates);
                    AuditLog::create([
                        'user_id' => $actor->id, 'role_name_snapshot' => $roleName, 'action' => 'UPDATE',
                        'entity_type' => 'Patient', 'entity_id' => $patient->id, 'entity_business_code' => $patient->patient_code,
                        'old_values' => $oldIdentity, 'new_values' => $identityUpdates,
                        'description' => 'Bổ sung ngày sinh hoặc giới tính còn thiếu khi tiếp nhận khám trực tiếp.',
                        'ip_address' => request()->ip(), 'user_agent' => mb_substr((string) request()->userAgent(), 0, 500), 'performed_at' => now(),
                    ]);
                }
            } else {
                $patient = Patient::create(['user_id' => null, 'patient_code' => app(PatientCodeService::class)->next(),
                    'full_name' => trim($data['full_name']), 'date_of_birth' => $data['date_of_birth'], 'gender' => $data['gender'], 'status' => 'ACTIVE']);
            }
            $specialty = Specialty::whereKey($schedule->specialty_id)->lockForUpdate()->firstOrFail();
            $room = ExaminationRoom::whereKey($schedule->examination_room_id)->lockForUpdate()->firstOrFail();
            if ($doctor->status !== 'ACTIVE' || $patient->status !== 'ACTIVE' || $specialty->status !== 'ACTIVE'
                || $room->status !== 'ACTIVE' || ! $doctor->canPracticeSpecialty((int) $specialty->id)) {
                throw ValidationException::withMessages(['schedule_id' => 'Bệnh nhân hoặc thông tin bác sĩ, chuyên khoa, phòng khám không còn hợp lệ. Vui lòng chọn lại phòng khám.']);
            }
            if (MedicalVisit::where('patient_id', $patient->id)->where('doctor_id', $doctor->id)
                ->whereDate('checked_in_at', today())->whereHas('status', fn ($q) => $q->whereIn('name', ['WAITING', 'IN_EXAMINATION', 'WAITING_FOR_SERVICE', 'RETURNING']))
                ->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['patient_id' => 'Bệnh nhân đã có lượt khám chưa hoàn tất với bác sĩ này trong ngày.']);
            }
            $waiting = VisitStatus::where('name', 'WAITING')->where('is_active', true)->first();
            if (! $waiting || ! QueueStatus::where('name', 'WAITING')->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['schedule_id' => 'Chưa cấu hình trạng thái lượt khám hoặc hàng đợi.']);
            }
            $credentials = $patient->user_id === null
                ? $this->createPatientAccount($patient, $patientRole, $data, $actor, $roleName)
                : null;
            do { $code = 'LK'.now()->format('ymdHis').strtoupper(Str::random(3)); } while (MedicalVisit::where('visit_code', $code)->exists());
            $visit = MedicalVisit::create(['visit_code' => $code, 'appointment_id' => null, 'patient_id' => $patient->id,
                'doctor_id' => $doctor->id, 'specialty_id' => $specialty->id, 'examination_room_id' => $room->id,
                'visit_status_id' => $waiting->id, 'checked_in_at' => now(), 'reception_source' => 'WALK_IN',
                'reason_for_visit' => $data['reason_for_visit'] ?? null, 'reception_notes' => 'Tiếp nhận khám trực tiếp tại quầy.']);
            app(ExaminationQueueNumber::class)->reserve($visit);
            app(ExaminationFeeService::class)->createForVisit($visit);
            AuditLog::create(['user_id' => $actor->id, 'role_name_snapshot' => $roleName, 'action' => 'CHECK_IN',
                'entity_type' => 'MedicalVisit', 'entity_id' => $visit->id, 'entity_business_code' => $visit->visit_code,
                'old_values' => [], 'new_values' => ['patient_id' => $patient->id, 'doctor_schedule_id' => $schedule->id],
                'description' => 'Tiếp nhận bệnh nhân khám trực tiếp.', 'ip_address' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 500), 'performed_at' => now()]);
            if (! empty($data['request_token'])) {
                AuditLog::create(['user_id' => $actor->id, 'role_name_snapshot' => $roleName, 'action' => 'WALK_IN_REQUEST',
                    'entity_type' => 'MedicalVisit', 'entity_id' => $visit->id, 'entity_business_code' => $data['request_token'],
                    'old_values' => [], 'new_values' => [], 'description' => 'Ghi nhận yêu cầu tiếp nhận trực tiếp.',
                    'ip_address' => request()->ip(), 'user_agent' => mb_substr((string) request()->userAgent(), 0, 500), 'performed_at' => now()]);
            }
            return ['visit' => $visit, 'credentials' => $asAdministrator ? null : $credentials];
        }, 3);
    }

    /** @return array{visit: MedicalVisit, credentials: array{phone: string, password: string, reissued: true}} */
    public function issueTemporaryPassword(User $actor, Patient $patient): array
    {
        return DB::transaction(function () use ($actor, $patient): array {
            app(ReceptionAccess::class)->lockAndAuthorize($actor);
            $patient = Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $user = $patient->user()->lockForUpdate()->first();
            $citizenId = $patient->detail()->lockForUpdate()->value('citizen_id_number');
            $visit = $patient->medicalVisits()->where('reception_source', 'WALK_IN')->whereNull('appointment_id')->latest('id')->first();

            if (! $visit || ! $user?->must_change_password || ! $user->canAccessPortal()
                || ! is_string($citizenId) || ! preg_match('/^[0-9]{12}$/D', $citizenId)) {
                throw ValidationException::withMessages(['confirm_identity' => 'Chỉ cấp mật khẩu tạm cho tài khoản tiếp nhận trực tiếp chưa đặt mật khẩu riêng và có CCCD hợp lệ.']);
            }

            $password = Str::random(16);
            $user->update(['password' => $password]);
            AuditLog::create([
                'user_id' => $actor->id, 'role_name_snapshot' => 'Employee', 'action' => 'UPDATE',
                'entity_type' => 'users', 'entity_id' => $user->id, 'entity_business_code' => $user->business_code,
                'new_values' => ['credential_changed' => true], 'description' => 'Lễ tân cấp mật khẩu tạm cho tài khoản tiếp nhận trực tiếp.',
                'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'performed_at' => now(),
            ]);

            return ['visit' => $visit, 'credentials' => ['phone' => $user->phone, 'password' => $password, 'reissued' => true]];
        }, 3);
    }

    /** @return array{phone: string, password: string} */
    private function createPatientAccount(Patient $patient, ?Role $role, array $data, User $actor, string $roleName): array
    {
        $phone = $data['phone'] ?? null;
        if (! is_string($phone) || ! preg_match('/^0[0-9]{9}$/D', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Nhập số điện thoại gồm 10 chữ số, bắt đầu bằng số 0, để tạo tài khoản.']);
        }
        if (! $role) {
            throw ValidationException::withMessages(['phone' => 'Vai trò bệnh nhân chưa hoạt động. Vui lòng kiểm tra cấu hình tài khoản.']);
        }
        $detail = $patient->detail()->lockForUpdate()->first();
        $needsCitizenId = blank($detail?->citizen_id_number);
        $citizenId = $needsCitizenId ? ($data['citizen_id_number'] ?? null) : $detail->citizen_id_number;
        if (! is_string($citizenId) || ! preg_match('/^[0-9]{12}$/D', $citizenId)) {
            throw ValidationException::withMessages(['citizen_id_number' => 'Cần có CCCD gồm 12 chữ số trong hồ sơ để tạo tài khoản.']);
        }
        if ($needsCitizenId) {
            try {
                $profile = ['citizen_id_number' => $citizenId];
                if ($data['patient_mode'] === 'new') {
                    $profile['current_address'] = $data['current_address'] ?? null;
                }
                ($detail ?? new PatientDetail(['patient_id' => $patient->id]))->fill($profile)->save();
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['citizen_id_number' => 'CCCD đã có trong hồ sơ. Vui lòng tìm và chọn bệnh nhân hiện có.']);
            }
        }

        $password = Str::random(16);
        try {
            $user = User::create([
                'business_code' => $patient->patient_code, 'phone' => $phone, 'password' => $password,
                'role_id' => $role->id, 'status' => 'ACTIVE', 'must_change_password' => true,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            if (User::where('phone', $phone)->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['phone' => 'Số điện thoại đã có tài khoản. Vui lòng tìm đúng hồ sơ hoặc xác nhận lại số điện thoại của bệnh nhân.']);
            }

            throw $exception;
        }
        $patient->update(['user_id' => $user->id]);
        AuditLog::create([
            'user_id' => $actor->id, 'role_name_snapshot' => $roleName, 'action' => 'CREATE',
            'entity_type' => 'users', 'entity_id' => $user->id, 'entity_business_code' => $user->business_code,
            'new_values' => ['patient_id' => $patient->id], 'description' => 'Tạo tài khoản bệnh nhân khi tiếp nhận trực tiếp.',
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'performed_at' => now(),
        ]);

        return ['phone' => $phone, 'password' => $password];
    }
}
