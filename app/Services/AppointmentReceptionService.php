<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AppointmentTimeSlot;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\QueueStatus;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VisitStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentReceptionService
{
    public function checkIn(Appointment $appointment, User $actor, bool $requireReceptionist = false): MedicalVisit
    {
        return DB::transaction(function () use ($appointment, $actor, $requireReceptionist) {
            // Match the lock order used by employee classification updates.
            if ($requireReceptionist) {
                app(ReceptionAccess::class)->lockAndAuthorize($actor);
            }

            $checkedIn = AppointmentStatus::where('name', 'CHECKED_IN')->where('is_active', true)->first();
            $waiting = VisitStatus::where('name', 'WAITING')->where('is_active', true)->first();
            $waitingQueue = QueueStatus::where('name', 'WAITING')->where('is_active', true)->first();
            if (! $checkedIn || ! $waiting || ! $waitingQueue) {
                throw ValidationException::withMessages(['appointment' => 'Chưa cấu hình đầy đủ trạng thái tiếp nhận và hàng đợi.']);
            }
            $initialSlot = AppointmentTimeSlot::findOrFail($appointment->appointment_time_slot_id);
            $schedule = DoctorSchedule::whereKey($initialSlot->doctor_schedule_id)->lockForUpdate()->firstOrFail();
            $slot = AppointmentTimeSlot::whereKey($initialSlot->id)->lockForUpdate()->firstOrFail();
            $slot->setRelation('doctorSchedule', $schedule);
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $locked->load('appointmentStatus');
            $locked->setRelation('appointmentTimeSlot', $slot);
            if ($locked->appointment_time_slot_id !== $slot->id) {
                throw ValidationException::withMessages(['appointment' => 'Khung giờ của lịch hẹn vừa thay đổi. Vui lòng tải lại trang.']);
            }
            $locked->assertReceptionWindow();
            $doctor = Doctor::whereKey($locked->doctor_id)->lockForUpdate()->firstOrFail();
            $patient = Patient::whereKey($locked->patient_id)->lockForUpdate()->firstOrFail();
            $specialty = Specialty::whereKey($locked->specialty_id)->lockForUpdate()->firstOrFail();
            $room = ExaminationRoom::whereKey($locked->examination_room_id)->lockForUpdate()->firstOrFail();
            if ($schedule->status !== 'ACTIVE' || $slot->status !== 'ACTIVE' || $doctor->status !== 'ACTIVE'
                || $patient->status !== 'ACTIVE' || $specialty->status !== 'ACTIVE' || $room->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['appointment' => 'Chưa thể tiếp nhận vì lịch làm việc, khung giờ, bác sĩ, bệnh nhân, chuyên khoa hoặc phòng khám đã ngừng hoạt động. Vui lòng kiểm tra và điều chỉnh lịch hẹn.']);
            }
            if ((int) $schedule->doctor_id !== (int) $locked->doctor_id || (int) $schedule->specialty_id !== (int) $locked->specialty_id
                || (int) $schedule->examination_room_id !== (int) $locked->examination_room_id || $slot->start_time < $schedule->start_time
                || $slot->end_time > $schedule->end_time || $slot->start_time >= $slot->end_time) {
                throw ValidationException::withMessages(['appointment' => 'Thông tin lịch hẹn không còn khớp với ca làm việc. Vui lòng kiểm tra lại bác sĩ, chuyên khoa, phòng và khung giờ trước khi tiếp nhận.']);
            }
            if (! $doctor->canPracticeSpecialty((int) $locked->specialty_id)) {
                throw ValidationException::withMessages(['appointment' => 'Bác sĩ chưa được phân chuyên khoa phù hợp với lịch hẹn. Vui lòng kiểm tra hồ sơ bác sĩ trước khi tiếp nhận.']);
            }
            $duplicate = MedicalVisit::join('appointments', 'appointments.id', '=', 'medical_visits.appointment_id')
                ->where('appointments.patient_id', $locked->patient_id)->where('appointments.appointment_time_slot_id', $slot->id)
                ->where('appointments.id', '!=', $locked->id)->select('medical_visits.*', 'appointments.appointment_code')->lockForUpdate()->first();
            if ($duplicate) {
                throw ValidationException::withMessages(['appointment' => 'Bệnh nhân đã được tiếp nhận ở lịch hẹn '.$duplicate->appointment_code.' trong cùng khung giờ. Không thể tạo thêm lượt khám trùng.']);
            }
            $walkIn = MedicalVisit::where('patient_id', $locked->patient_id)->where('doctor_id', $locked->doctor_id)
                ->where('reception_source', 'WALK_IN')->whereDate('checked_in_at', today())
                ->whereHas('status', fn ($q) => $q->whereIn('name', ['WAITING', 'IN_EXAMINATION', 'WAITING_FOR_SERVICE', 'RETURNING']))
                ->lockForUpdate()->first();
            if ($walkIn) {
                throw ValidationException::withMessages(['appointment' => 'Bệnh nhân đã có lượt khám trực tiếp chưa hoàn tất với bác sĩ này trong ngày.']);
            }
            if ($locked->appointmentStatus?->name !== 'BOOKED' || ! $locked->appointmentStatus?->allows_transition) {
                throw ValidationException::withMessages(['appointment' => 'Lịch hẹn không còn ở trạng thái được phép tiếp nhận.']);
            }
            if (MedicalVisit::where('appointment_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['appointment' => 'Bệnh nhân của lịch hẹn này đã được tiếp nhận trước đó.']);
            }
            $old = $locked->only(['appointment_code', 'patient_id', 'doctor_id', 'specialty_id', 'examination_room_id', 'appointment_date', 'appointment_time_slot_id', 'appointment_status_id', 'reason_for_visit', 'patient_notes', 'booked_at', 'cancelled_at', 'cancellation_reason']);
            do { $code = 'LK'.now()->format('ymdHis').strtoupper(Str::random(3)); } while (MedicalVisit::where('visit_code', $code)->exists());
            $visit = MedicalVisit::create([
                'visit_code' => $code, 'appointment_id' => $locked->id, 'patient_id' => $locked->patient_id,
                'doctor_id' => $locked->doctor_id, 'specialty_id' => $locked->specialty_id, 'examination_room_id' => $locked->examination_room_id,
                'visit_status_id' => $waiting->id, 'checked_in_at' => now(), 'reception_source' => 'COUNTER',
                'reception_notes' => 'Tiếp nhận từ lịch hẹn '.$locked->appointment_code.'.',
            ]);
            app(ExaminationFeeService::class)->createForVisit($visit);
            $locked->update(['appointment_status_id' => $checkedIn->id]);
            $new = $old;
            $new['appointment_status_id'] = $checkedIn->id;
            AuditLog::create(['user_id' => $actor->id, 'role_name_snapshot' => $requireReceptionist ? 'Employee' : 'Administrator',
                'action' => 'CHECK_IN', 'entity_type' => 'Appointment', 'entity_id' => $locked->id, 'entity_business_code' => $locked->appointment_code,
                'old_values' => $old, 'new_values' => $new, 'description' => 'Tiếp nhận lịch hẹn '.$locked->appointment_code.' và tạo lượt khám '.$visit->visit_code.'.',
                'ip_address' => request()->ip(), 'user_agent' => mb_substr((string) request()->userAgent(), 0, 500), 'performed_at' => now()]);
            return $visit;
        }, 3);
    }
}
