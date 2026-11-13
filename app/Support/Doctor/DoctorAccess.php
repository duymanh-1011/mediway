<?php

namespace App\Support\Doctor;

use App\Models\Doctor;
use App\Models\ExaminationRoom;
use App\Models\User;

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
            && (int) $doctor->user_id === (int) $user->id
            && $doctor->doctor_type === self::EXAMINATION_TYPE;
    }

    public function assertRoom(
        Doctor $doctor,
        ExaminationRoom $room,
        string $date
    ): void {
        abort_unless(
            $room->status === 'ACTIVE'
                && $doctor->schedules()
                ->where('examination_room_id', $room->id)
                ->whereDate('work_date', $date)
                ->where('status', 'ACTIVE')
                ->exists(),
            404
        );
    }
}
