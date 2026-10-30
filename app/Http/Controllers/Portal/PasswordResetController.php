<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PasswordResetPhoneRequest;
use App\Http\Requests\Portal\ResetPasswordRequest;
use App\Http\Requests\Portal\VerifyOtpRequest;
use App\Models\User;
use App\Models\PatientPasswordResetChallenge;
use App\Models\AuditLog;
use App\Services\PatientPasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PasswordResetController extends Controller
{
    public function create() { return view('portal.auth.password-request', ['demoEnabled' => $this->demoEnabled()]); }

    public function send(PasswordResetPhoneRequest $request, PatientPasswordResetService $resets)
    {
        $phone = $request->validated('phone');
        $key = 'patient-otp:'.hash('sha256', $phone.'|'.$request->ip());
        $ipKey = 'patient-otp-ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return back()->withErrors(['phone' => 'Vui lòng chờ '.max(1, RateLimiter::availableIn($key), RateLimiter::availableIn($ipKey)).' giây trước khi gửi lại mã.'])->onlyInput('phone');
        }
        $this->forgetResetFlow($request);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($ipKey, 60);
        if (! $this->demoEnabled()) {
            return back()->with('success', 'Chức năng khôi phục hiện chưa khả dụng. Vui lòng liên hệ hỗ trợ.');
        }
        if (config('portal.esms.enabled') && config('portal.esms.sandbox')) {
            return back()->with('success', 'eSMS Sandbox không gửi SMS. Vui lòng kiểm tra kết nối bằng công cụ nội bộ.');
        }
        if (! Schema::hasTable('patient_password_reset_challenges')) {
            return back()->withErrors(['phone' => 'Chức năng khôi phục chưa sẵn sàng. Vui lòng liên hệ quản trị viên.']);
        }
        $user = User::query()->where('phone', $phone)->first();
        $eligible = $user && $user->canAccessPortal() && $user->phone === config('portal.demo_otp_phone');
        if ($eligible && $resets->request($user)) {
            $request->session()->put('patient_reset_user_id', $user->id);
        }
        $request->session()->put('patient_reset_flow_started', true);
        return redirect()->route('patient.password.verify')->with('success', 'Nếu tài khoản đủ điều kiện, mã xác minh mô phỏng đã được tạo.');
    }

    public function verifyForm(Request $request)
    {
        return $this->demoEnabled() && $request->session()->has('patient_reset_flow_started') ? view('portal.auth.password-verify') : redirect()->route('patient.password.request');
    }

    public function verify(VerifyOtpRequest $request, PatientPasswordResetService $resets)
    {
        $user = User::find($request->session()->get('patient_reset_user_id'));
        if (! $this->demoEnabled() || ! $user || $user->phone !== config('portal.demo_otp_phone') || ! $user->canAccessPortal()) {
            return back()->withErrors(['otp' => 'Mã xác minh không đúng, đã hết hạn hoặc không còn hiệu lực.']);
        }
        $grant = $resets->verify($user, $request->validated('otp'));
        if (! $grant) {
            return back()->withErrors(['otp' => 'Mã xác minh không đúng, đã hết hạn hoặc không còn hiệu lực.']);
        }
        $request->session()->put('patient_reset_grant', $grant);
        $request->session()->put('patient_reset_grant_user_id', $user->id);
        $request->session()->put('patient_reset_grant_issued_at', now()->timestamp);
        return redirect()->route('patient.password.reset');
    }

    public function resetForm(Request $request)
    {
        return $this->hasValidResetGrant($request)
            ? view('portal.auth.password-reset') : redirect()->route('patient.password.request');
    }

    public function reset(ResetPasswordRequest $request)
    {
        $user = User::find($request->session()->get('patient_reset_user_id'));
        if (! $this->demoEnabled() || ! $user || $user->phone !== config('portal.demo_otp_phone') || ! $this->hasValidResetGrant($request) || ! $user->canAccessPortal()) {
            $this->forgetResetFlow($request);
            return redirect()->route('patient.password.request')->withErrors(['password' => 'Quyền đặt lại mật khẩu không còn hiệu lực.']);
        }
        $changed = DB::transaction(function () use ($request, $user): bool {
            $challenge = PatientPasswordResetChallenge::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $this->hasValidResetGrant($request, $challenge)) {
                return false;
            }
            $user->update(['password' => Hash::make($request->validated('password'))]);
            $challenge->update(['expires_at' => now()->subSecond()]);
            AuditLog::create([
                'user_id' => $user->id,
                'role_name_snapshot' => 'Patient',
                'action' => 'UPDATE',
                'entity_type' => 'users',
                'entity_id' => $user->id,
                'entity_business_code' => $user->business_code,
                'old_values' => [],
                'new_values' => ['credential_changed' => true],
                'description' => 'Bệnh nhân đặt lại mật khẩu tài khoản.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'performed_at' => now(),
            ]);
            return true;
        });
        $this->forgetResetFlow($request);
        if (! $changed) {
            return redirect()->route('patient.password.request')->withErrors(['password' => 'Quyền đặt lại mật khẩu không còn hiệu lực.']);
        }
        return redirect()->route('patient.login')->with('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.');
    }

    private function demoEnabled(): bool { return app()->environment(['local', 'testing']); }
    private function hasValidResetGrant(Request $request, ?PatientPasswordResetChallenge $challenge = null): bool
    {
        if (! $this->demoEnabled()) {
            return false;
        }
        $issuedAt = $request->session()->get('patient_reset_grant_issued_at');

        $challenge ??= PatientPasswordResetChallenge::where('user_id', $request->session()->get('patient_reset_user_id'))->first();

        return $request->session()->has('patient_reset_user_id')
            && $request->session()->get('patient_reset_user_id') === $request->session()->get('patient_reset_grant_user_id')
            && $request->session()->has('patient_reset_grant')
            && $challenge && $challenge->used_at && ! $challenge->expires_at->isPast()
            && hash_equals(hash('sha256', $challenge->otp_hash), (string) $request->session()->get('patient_reset_grant'))
            && is_int($issuedAt)
            && $issuedAt >= now()->subMinutes((int) config('portal.reset_grant_minutes', 15))->timestamp;
    }

    private function forgetResetFlow(Request $request): void
    {
        $request->session()->forget(['patient_reset_user_id', 'patient_reset_grant', 'patient_reset_grant_user_id', 'patient_reset_grant_issued_at', 'patient_reset_flow_started']);
    }
}
