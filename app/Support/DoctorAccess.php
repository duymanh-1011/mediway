<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\User;

final class DoctorAccess
{
    public function doctor(User $user): ?Doctor
    {
        $user->load(['role', 'doctor']);
        $doctor = $user->doctor;

        if ($user->status !== 'ACTIVE' || $user->role?->name !== 'Doctor'
            || $user->role?->status !== 'ACTIVE' || $doctor?->status !== 'ACTIVE') {
            return null;
        }

        return $doctor;
    }
}
