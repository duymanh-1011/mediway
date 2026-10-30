<?php

namespace App\Services;

use App\Contracts\PatientOtpSender;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Throwable;

/** eSMS's fixed public test template is permitted only for an explicitly configured demo account. */
final class EsmsDemoPatientOtpSender implements PatientOtpSender
{
    private const URL = 'https://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/';

    public function send(User $user, string $otp): bool
    {
        if (! app()->environment(['local', 'testing']) || ! config('portal.esms.enabled')
            || $user->phone !== config('portal.demo_otp_phone') || config('portal.esms.sandbox')) {
            return false;
        }

        return $this->sendRequest($user->phone, $otp, false);
    }

    /** A sandbox request checks configuration only; it never grants password reset rights. */
    public function checkSandbox(string $phone): bool
    {
        return app()->environment(['local', 'testing'])
            && config('portal.esms.enabled')
            && $phone === config('portal.demo_otp_phone')
            && $this->sendRequest($phone, '123456', true);
    }

    private function sendRequest(string $phone, string $otp, bool $sandbox): bool
    {
        $apiKey = config('portal.esms.api_key');
        $secretKey = config('portal.esms.secret_key');
        if (! is_string($apiKey) || trim($apiKey) === '' || ! is_string($secretKey) || trim($secretKey) === ''
            || ! preg_match('/^0[0-9]{9}$/', $phone) || ! preg_match('/^[0-9]{6}$/', $otp)) {
            return false;
        }
        try {
            $response = Http::asJson()->acceptJson()->withoutRedirecting()->connectTimeout(3)->timeout(8)->post(self::URL, [
                'ApiKey' => $apiKey,
                'SecretKey' => $secretKey,
                'Phone' => $phone,
                'Content' => $otp.' la ma xac minh dang ky Baotrixemay cua ban',
                'Brandname' => 'Baotrixemay',
                'SmsType' => '2',
                'IsUnicode' => '0',
                'Sandbox' => $sandbox ? '1' : '0',
            ]);

            return $response->successful() && (string) $response->json('CodeResult') === '100';
        } catch (Throwable) {
            // Never log provider responses, secrets, OTPs or recipient details.
            return false;
        }
    }
}
