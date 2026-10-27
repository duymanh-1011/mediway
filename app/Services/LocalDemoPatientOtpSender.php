<?php

namespace App\Services;

use App\Contracts\PatientOtpSender;
use App\Models\User;

/** A deliberately non-delivering adapter: test code controls the OTP, never a public page or application log. */
class LocalDemoPatientOtpSender implements PatientOtpSender
{
    public function send(User $user, string $otp): bool
    {
        return app()->environment(['local', 'testing'])
            && $user->phone === config('portal.demo_otp_phone');
    }
}
