<?php

namespace App\Support\Doctor;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class DoctorAccess
{
    public const EXAMINATION_TYPE = 'EXAMINATION';

    public function doctor(User $user): ?Doctor
    {
        $user->load([
            'role',
            'doctor.specialty',
            'doctor.detail',
        ]);

        if (
            $user->status !== 'ACTIVE'
            || $user->role?->name !== 'Doctor'
            || $user->role?->status !== 'ACTIVE'
            || $user->doctor?->status !== 'ACTIVE'
        ) {
            return null;
        }

        $doctor = $user->doctor;

        if (
            ! $doctor
            || (int) $doctor->user_id !== (int) $user->id
        ) {
            return null;
        }

        return $doctor;
    }

    public function canExamine(
        User $user,
        Doctor $doctor
    ): bool {
        $user->loadMissing('role');

        return
            $user->status === 'ACTIVE'
            && $user->role?->name === 'Doctor'
            && $user->role?->status === 'ACTIVE'
            && $doctor->status === 'ACTIVE'
            && (int) $doctor->user_id === (int) $user->id;
    }

    public function assertRoom(
        Doctor $doctor,
        ExaminationRoom $room,
        string $date
    ): void {
        abort_unless(
            $room->status === 'ACTIVE',
            404
        );

        if (! $this->hasRoomAssignment($doctor, $room, $date)) {
            throw ValidationException::withMessages([
                'room' => $this->roomAccessMessage($doctor),
            ]);
        }
    }

    public function hasRoomAssignment(
        Doctor $doctor,
        ExaminationRoom $room,
        string $date
    ): bool {
        return DoctorSchedule::query()
            ->where('doctor_id', $doctor->id)
            ->where('examination_room_id', $room->id)
            ->whereDate('work_date', $date)
            ->where('status', 'ACTIVE')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereColumn('start_time', '<', 'end_time')
            ->exists();
    }

    public function roomAccessMessage(Doctor $doctor): string
    {
        return 'Vui lòng chọn phòng khám thuộc lịch làm việc của bác sĩ '.$doctor->full_name.'.';
    }

    public function assertVisitOwner(Doctor $doctor, MedicalVisit $visit): void
    {
        abort_unless(
            $visit->doctor_id !== null
                && (int) $visit->doctor_id === (int) $doctor->id,
            404
        );
    }
}
