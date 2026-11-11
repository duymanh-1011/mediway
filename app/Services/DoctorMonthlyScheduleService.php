<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Specialty;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DoctorMonthlyScheduleService
{
    private const SNAPSHOT = [
        'doctor_id', 'work_date', 'shift_code', 'start_time', 'end_time', 'status', 'specialty_id', 'examination_room_id',
    ];

    public function __construct(private DoctorScheduleSlots $slots) {}

    public function forMonth(User $actor, int $doctorId, string $month): array
    {
        $this->authorize($actor);
        Validator::make(['month' => $month], ['month' => 'required|date_format:Y-m'])->validate();
        $firstDay = CarbonImmutable::createFromFormat('!Y-m', $month, config('app.timezone'));
        $nextMonth = $firstDay->addMonth();
        $doctor = Doctor::with('specialty')->findOrFail($doctorId);
        $byDay = $doctor->schedules()->with(['specialty', 'examinationRoom'])
            ->where('work_date', '>=', $firstDay->toDateString())->where('work_date', '<', $nextMonth->toDateString())
            ->orderBy('start_time')->orderBy('id')->get()
            ->groupBy(fn (DoctorSchedule $schedule) => $schedule->work_date->toDateString());

        $days = [];
        for ($day = $firstDay; $day < $nextMonth; $day = $day->addDay()) {
            $date = $day->toDateString();
            $schedules = $byDay->get($date, collect());
            $standard = $schedules->whereNotNull('shift_code')->keyBy('shift_code');
            $shifts = [];
            foreach (config('doctor_schedules.shifts') as $code => $hours) {
                $schedule = $standard->get($code);
                $shifts[$code] = [
                    ...$hours,
                    'state' => match ($schedule?->status) {
                        'ACTIVE' => 'WORKING',
                        'INACTIVE' => 'OFF',
                        default => 'UNCONFIGURED',
                    },
                    'schedule' => $schedule,
                    'revision' => $this->revision($schedule),
                ];
            }
            $days[$date] = ['shifts' => $shifts, 'legacy_schedules' => $schedules->whereNull('shift_code')->values()];
        }

        return ['doctor' => $doctor, 'month' => $month, 'days' => $days];
    }

    public function updateShift(
        User $actor,
        int $doctorId,
        string $date,
        string $shiftCode,
        string $state,
        ?string $expectedRevision,
        ?int $specialtyId = null,
        ?int $roomId = null,
        bool $preview = false,
    ): DoctorSchedule {
        $actor = $this->authorize($actor);
        $shifts = config('doctor_schedules.shifts');
        Validator::make(
            ['date' => $date, 'shift' => $shiftCode, 'state' => $state],
            ['date' => 'required|date_format:Y-m-d', 'shift' => ['required', Rule::in(array_keys($shifts))], 'state' => 'required|in:WORKING,OFF'],
        )->validate();
        $endOfDay = CarbonImmutable::createFromFormat('!Y-m-d', $date, config('app.timezone'))->addDay()->toDateString();
        $hours = $shifts[$shiftCode];

        $apply = function () use ($actor, $doctorId, $date, $endOfDay, $shiftCode, $state, $expectedRevision, $specialtyId, $roomId, $hours, $preview) {
            $cell = DoctorSchedule::where('doctor_id', $doctorId)->where('shift_code', $shiftCode)
                ->where('work_date', '>=', $date)->where('work_date', '<', $endOfDay);
            // Existing booking and reception writers lock the schedule before the doctor.
            $scheduleId = $preview ? null : (clone $cell)->value('id');
            $schedule = $scheduleId ? DoctorSchedule::lockForUpdate()->findOrFail($scheduleId) : null;
            $doctor = Doctor::query()->when(! $preview, fn ($query) => $query->lockForUpdate())->findOrFail($doctorId);
            $schedule ??= $cell->when(! $preview, fn ($query) => $query->lockForUpdate())->first();
            if ($this->revision($schedule) !== $expectedRevision) {
                throw ValidationException::withMessages(['revision' => 'Ca này vừa được cập nhật. Vui lòng tải lại lịch trước khi lưu.']);
            }

            $working = $state === 'WORKING';
            $assignment = $working
                ? $this->workingAssignment($doctor, $specialtyId ?? $schedule?->specialty_id, $roomId ?? $schedule?->examination_room_id, ! $preview)
                : ['specialty_id' => $schedule?->specialty_id, 'examination_room_id' => $schedule?->examination_room_id];
            $before = $schedule ? $this->snapshot($schedule) : [];
            $schedule ??= new DoctorSchedule(['doctor_id' => $doctorId, 'work_date' => $date, 'shift_code' => $shiftCode]);
            $schedule->fill([
                ...$assignment, 'start_time' => $hours['start'], 'end_time' => $hours['end'], 'status' => $working ? 'ACTIVE' : 'INACTIVE',
            ]);
            if (! $schedule->isDirty()) {
                if ($working) {
                    $this->checkConflicts($schedule, ! $preview);
                    $this->slots->generate($schedule, $preview);
                }
                return $schedule;
            }

            $this->checkConflicts($schedule, ! $preview);
            if ($schedule->exists) {
                $this->protectExistingActivity($schedule, ! $preview);
            }
            if ($preview) {
                if ($working && $schedule->exists) {
                    $this->slots->generate($schedule, preview: true);
                }
                return $schedule;
            }

            $schedule->save();
            if ($working) {
                $this->slots->generate($schedule);
            }
            AuditLog::create([
                'user_id' => $actor->id, 'role_name_snapshot' => $actor->role->name,
                'action' => $schedule->wasRecentlyCreated ? 'CREATE' : 'UPDATE',
                'entity_type' => $schedule->getTable(), 'entity_id' => $schedule->id, 'entity_business_code' => $doctor->doctor_code,
                'old_values' => $before, 'new_values' => $this->snapshot($schedule),
                'description' => 'Cập nhật ca hành chính trong lịch làm việc bác sĩ.', 'performed_at' => now(),
            ]);

            return $schedule;
        };

        return $preview ? $apply() : DB::transaction($apply, 5);
    }

    private function authorize(User $actor): User
    {
        $actor = $actor->fresh();
        abort_unless($actor?->canAccessAdmin(), 403);

        return $actor;
    }

    private function revision(?DoctorSchedule $schedule): ?string
    {
        return $schedule === null ? null : hash('sha256', json_encode([
            $schedule->id, $this->snapshot($schedule), $schedule->updated_at,
        ], JSON_THROW_ON_ERROR));
    }

    private function snapshot(DoctorSchedule $schedule): array
    {
        return [...$schedule->only(self::SNAPSHOT), 'work_date' => $schedule->work_date->toDateString()];
    }

    private function workingAssignment(Doctor $doctor, ?int $specialtyId, ?int $roomId, bool $lock): array
    {
        $specialties = Specialty::whereKey(array_values(array_filter([$specialtyId, $doctor->specialty_id])))
            ->orderBy('id')->when($lock, fn ($query) => $query->lockForUpdate())->get()->keyBy('id');
        $doctor->setRelation('specialty', $specialties->get($doctor->specialty_id));
        if ($doctor->status !== 'ACTIVE' || $specialties->get($specialtyId)?->status !== 'ACTIVE'
            || ! $doctor->canPracticeSpecialty((int) $specialtyId)) {
            throw ValidationException::withMessages(['specialty_id' => 'Chọn chuyên khoa hoạt động phù hợp với bác sĩ để lưu ca làm việc.']);
        }

        $room = $roomId === null ? null : ExaminationRoom::query()->when($lock, fn ($query) => $query->lockForUpdate())->find($roomId);
        if ($room?->status !== 'ACTIVE' || (int) $room->specialty_id !== $specialtyId) {
            throw ValidationException::withMessages(['examination_room_id' => 'Chọn phòng khám hoạt động thuộc chuyên khoa đã chọn.']);
        }

        return ['specialty_id' => $specialtyId, 'examination_room_id' => $roomId];
    }

    private function checkConflicts(DoctorSchedule $schedule, bool $lock): void
    {
        $overlapping = DoctorSchedule::query()->overlappingOn(
            $schedule->work_date->toDateString(), $schedule->start_time, $schedule->end_time,
        )->where(fn ($query) => $query->where('status', 'ACTIVE')
            ->orWhereHas('appointmentTimeSlots.appointments', $this->unresolvedAppointment(...)))
            ->when($schedule->exists, fn ($query) => $query->whereKeyNot($schedule->id))
            ->when($lock, fn ($query) => $query->sharedLock());
        // Use locking reads to see changes committed while waiting for the doctor or room.
        $doctorConflict = (clone $overlapping)->where('doctor_id', $schedule->doctor_id)->first(['id']);
        if ($doctorConflict) {
            throw ValidationException::withMessages(['shift' => 'Bác sĩ đã có lịch làm việc trùng giờ. Hãy xử lý lịch hiện có trước.']);
        }
        if ($schedule->status === 'ACTIVE' && $overlapping->where('examination_room_id', $schedule->examination_room_id)
            ->first(['id'])) {
            throw ValidationException::withMessages(['examination_room_id' => 'Phòng khám đã có lịch làm việc trùng giờ.']);
        }
    }

    private function protectExistingActivity(DoctorSchedule $schedule, bool $lock): void
    {
        $hoursChanged = $schedule->isDirty(['start_time', 'end_time']);
        $assignmentChanged = $hoursChanged || $schedule->isDirty(['specialty_id', 'examination_room_id']);
        $appointments = Appointment::whereHas('appointmentTimeSlot', fn ($slots) => $slots->where('doctor_schedule_id', $schedule->id))
            ->when($lock, fn ($query) => $query->sharedLock());

        if ($assignmentChanged && (clone $appointments)->first(['id'])) {
            throw ValidationException::withMessages(['shift' => 'Ca đã có lịch hẹn. Không thể đổi giờ, phòng hoặc chuyên khoa của ca này.']);
        }
        if ($hoursChanged && $schedule->appointmentTimeSlots()->when($lock, fn ($query) => $query->sharedLock())->first(['id'])) {
            throw ValidationException::withMessages(['shift' => 'Ca đã có khung giờ. Cần giữ thời gian gốc của ca này.']);
        }
        if ($schedule->status === 'INACTIVE' && $this->unresolvedAppointment($appointments)->first(['id'])) {
            throw ValidationException::withMessages(['shift' => 'Ca còn lịch hẹn hoặc lượt khám chưa hoàn tất. Hãy xử lý trước khi chuyển sang nghỉ.']);
        }
        if ($schedule->getRawOriginal('status') === 'ACTIVE' && ($assignmentChanged || $schedule->status === 'INACTIVE')
            && MedicalVisit::where('doctor_id', $schedule->doctor_id)
                ->where('examination_room_id', $schedule->getRawOriginal('examination_room_id'))
                ->where('reception_source', 'WALK_IN')->whereDate('checked_in_at', $schedule->work_date->toDateString())
                ->whereTime('checked_in_at', '>=', $schedule->getRawOriginal('start_time'))
                ->whereTime('checked_in_at', '<', $schedule->getRawOriginal('end_time'))
                ->whereHas('status', fn ($status) => $status->whereNotIn('name', ['CANCELLED', 'COMPLETED']))
                ->when($lock, fn ($query) => $query->sharedLock())->first(['id'])) {
            throw ValidationException::withMessages(['shift' => 'Ca còn lượt khám trực tiếp chưa hoàn tất.']);
        }
    }

    private function unresolvedAppointment(Builder $appointments): Builder
    {
        return $appointments->where(fn ($query) => $query
            ->whereHas('appointmentStatus', fn ($status) => $status->whereNotIn('name', ['CANCELLED', 'COMPLETED']))
            ->orWhereHas('medicalVisit.status', fn ($status) => $status->whereNotIn('name', ['CANCELLED', 'COMPLETED'])));
    }
}
