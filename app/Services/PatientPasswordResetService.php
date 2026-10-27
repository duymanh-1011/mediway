<?php

namespace App\Services;

use App\Contracts\PatientOtpSender;
use App\Models\PatientPasswordResetChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PatientPasswordResetService
{
    public function __construct(private readonly PatientOtpSender $sender) {}

    public function request(User $user): bool
    {
        $otp = (string) random_int(100000, 999999);
        if (! $this->sender->send($user, $otp)) {
            return false;
        }

        DB::transaction(function () use ($user, $otp): void {
            PatientPasswordResetChallenge::updateOrCreate(['user_id' => $user->id], [
                'otp_hash' => Hash::make($otp), 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'used_at' => null,
            ]);
        });

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
            return Str::random(64);
        });
    }
}
