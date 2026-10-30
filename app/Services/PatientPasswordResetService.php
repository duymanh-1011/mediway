<?php

namespace App\Services;

use App\Contracts\PatientOtpSender;
use App\Models\PatientPasswordResetChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

final class PatientPasswordResetService
{
    public function __construct(private readonly PatientOtpSender $sender) {}

    public function request(User $user): bool
    {
        $otp = (string) random_int(100000, 999999);
        $otpHash = Hash::make($otp);
        DB::transaction(function () use ($user, $otpHash): void {
            PatientPasswordResetChallenge::updateOrCreate(['user_id' => $user->id], [
                'otp_hash' => $otpHash, 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'used_at' => null,
            ]);
        });

        try {
            $sent = $this->sender->send($user, $otp);
        } catch (Throwable) {
            $sent = false;
        }
        if (! $sent) {
            PatientPasswordResetChallenge::where('user_id', $user->id)->where('otp_hash', $otpHash)
                ->update(['expires_at' => now()->subSecond(), 'used_at' => now()]);
            return false;
        }

        // The OTP deliberately exists only in this method and its hash in storage.
        return true;
    }

    public function verify(User $user, string $otp): ?string
    {
        return DB::transaction(function () use ($user, $otp): ?string {
            $challenge = PatientPasswordResetChallenge::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $challenge || $challenge->used_at || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
                return null;
            }
            if (! Hash::check($otp, $challenge->otp_hash)) {
                $challenge->increment('attempts');
                return null;
            }
            $challenge->update(['used_at' => now()]);
            return hash('sha256', $challenge->otp_hash);
        });
    }
}
