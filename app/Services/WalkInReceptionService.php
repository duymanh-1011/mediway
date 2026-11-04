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
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalkInReceptionService
{
    public function receive(User $actor, array $data): MedicalVisit
    {
        return DB::transaction(function () use ($actor, $data): MedicalVisit {
            app(ReceptionAccess::class)->lockAndAuthorize($actor);
            if (! empty($data['request_token'])) {
                $previous = AuditLog::where('user_id', $actor->id)->where('action', 'WALK_IN_REQUEST')
                    ->where('entity_type', 'MedicalVisit')->where('entity_business_code', $data['request_token'])->lockForUpdate()->first();
                if ($previous) return MedicalVisit::findOrFail($previous->entity_id);
            }
            $schedule = DoctorSchedule::whereKey($data['schedule_id'])->lockForUpdate()->firstOrFail();
            if ($schedule->status !== 'ACTIVE' || ! $schedule->work_date?->isToday()
                || now()->format('H:i:s') < $schedule->start_time || now()->format('H:i:s') >= $schedule->end_time) {
                throw ValidationException::withMessages(['schedule_id' => 'Ca làm việc không còn hoạt động tại thời điểm tiếp nhận.']);
            }
            $doctor = Doctor::whereKey($schedule->doctor_id)->lockForUpdate()->firstOrFail();
            if ($data['patient_mode'] === 'existing') {
                $patient = Patient::whereKey($data['patient_id'])->lockForUpdate()->firstOrFail();
            } else {
                if (! empty($data['citizen_id_number']) && PatientDetail::where('citizen_id_number', $data['citizen_id_number'])->exists()) {
                    throw ValidationException::withMessages(['citizen_id_number' => 'CCCD đã có trong hồ sơ. Vui lòng tìm và chọn bệnh nhân hiện có.']);
                }
                $patient = Patient::create(['user_id' => null, 'patient_code' => app(PatientCodeService::class)->next(),
                    'full_name' => trim($data['full_name']), 'date_of_birth' => $data['date_of_birth'], 'gender' => $data['gender'], 'status' => 'ACTIVE']);
                PatientDetail::create(['patient_id' => $patient->id, 'citizen_id_number' => $data['citizen_id_number'] ?? null]);
            }
            $specialty = Specialty::whereKey($schedule->specialty_id)->lockForUpdate()->firstOrFail();
            $room = ExaminationRoom::whereKey($schedule->examination_room_id)->lockForUpdate()->firstOrFail();
            if ($doctor->status !== 'ACTIVE' || $patient->status !== 'ACTIVE' || $specialty->status !== 'ACTIVE'
                || $room->status !== 'ACTIVE' || ! $doctor->canPracticeSpecialty((int) $specialty->id)) {
                throw ValidationException::withMessages(['schedule_id' => 'Bệnh nhân hoặc thông tin bác sĩ, chuyên khoa, phòng khám không còn hợp lệ.']);
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
            do { $code = 'LK'.now()->format('ymdHis').strtoupper(Str::random(3)); } while (MedicalVisit::where('visit_code', $code)->exists());
            $visit = MedicalVisit::create(['visit_code' => $code, 'appointment_id' => null, 'patient_id' => $patient->id,
                'doctor_id' => $doctor->id, 'specialty_id' => $specialty->id, 'examination_room_id' => $room->id,
                'visit_status_id' => $waiting->id, 'checked_in_at' => now(), 'reception_source' => 'WALK_IN', 'reception_notes' => 'Tiếp nhận khám trực tiếp tại quầy.']);
            app(ExaminationFeeService::class)->createForVisit($visit);
            AuditLog::create(['user_id' => $actor->id, 'role_name_snapshot' => 'Employee', 'action' => 'CHECK_IN',
                'entity_type' => 'MedicalVisit', 'entity_id' => $visit->id, 'entity_business_code' => $visit->visit_code,
                'old_values' => [], 'new_values' => ['patient_id' => $patient->id, 'doctor_schedule_id' => $schedule->id],
                'description' => 'Tiếp nhận bệnh nhân khám trực tiếp.', 'ip_address' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 500), 'performed_at' => now()]);
            if (! empty($data['request_token'])) {
                AuditLog::create(['user_id' => $actor->id, 'role_name_snapshot' => 'Employee', 'action' => 'WALK_IN_REQUEST',
                    'entity_type' => 'MedicalVisit', 'entity_id' => $visit->id, 'entity_business_code' => $data['request_token'],
                    'old_values' => [], 'new_values' => [], 'description' => 'Ghi nhận yêu cầu tiếp nhận trực tiếp.',
                    'ip_address' => request()->ip(), 'user_agent' => mb_substr((string) request()->userAgent(), 0, 500), 'performed_at' => now()]);
            }
            return $visit;
        }, 3);
    }
}
