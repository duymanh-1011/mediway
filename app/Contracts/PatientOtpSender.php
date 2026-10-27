<?php

namespace App\Contracts;

use App\Models\User;

interface PatientOtpSender
{
    /** Returns true only when a challenge can actually be verified. */
    public function send(User $user, string $otp): bool;
}
