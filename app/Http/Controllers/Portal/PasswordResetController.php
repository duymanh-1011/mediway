<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PasswordResetPhoneRequest;
use App\Http\Requests\Portal\ResetPasswordRequest;
use App\Http\Requests\Portal\VerifyOtpRequest;
use App\Models\User;
use App\Services\PatientPasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetController extends Controller
{
    public function create() { return view('portal.auth.password-request', ['demoEnabled' => $this->demoEnabled()]); }

    public function send(PasswordResetPhoneRequest $request, PatientPasswordResetService $resets)
    {
        $phone = $request->validated('phone');
        $key = 'patient-otp:'.hash('sha256', $phone.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['phone' => 'Vui lòng chờ '.max(1, RateLimiter::availableIn($key)).' giây trước khi gửi lại mã.'])->onlyInput('phone');
        }
        $user = User::query()->where('phone', $phone)->first();
        $eligible = $user && $user->canAccessPortal() && $this->demoEnabled() && $user->phone === config('portal.demo_otp_phone');
        RateLimiter::hit($key, 60);
        if ($eligible) {
            $resets->request($user);
            $request->session()->put('patient_reset_user_id', $user->id);
            return redirect()->route('patient.password.verify')->with('success', 'Mã xác minh mô phỏng đã được tạo cho tài khoản demo trong môi trường local/testing.');
        }

        return back()->with('success', 'Nếu tài khoản đủ điều kiện, hướng dẫn khôi phục sẽ được gửi qua kênh xác minh đã đăng ký.');
    }

    public function verifyForm(Request $request)
    {
        return $request->session()->has('patient_reset_user_id') ? view('portal.auth.password-verify') : redirect()->route('patient.password.request');
    }

    public function verify(VerifyOtpRequest $request, PatientPasswordResetService $resets)
    {
        $user = User::find($request->session()->get('patient_reset_user_id'));
        if (! $user || ! $user->canAccessPortal()) {
            $this->forgetResetFlow($request);
            return redirect()->route('patient.password.request')->withErrors(['phone' => 'Yêu cầu khôi phục không còn hiệu lực.']);
        }
        $grant = $resets->verify($user, $request->validated('otp'));
        if (! $grant) {
            return back()->withErrors(['otp' => 'Mã xác minh không đúng, đã hết hạn hoặc không còn hiệu lực.']);
        }
        $request->session()->put('patient_reset_grant', hash('sha256', $grant));
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
        if (! $user || ! $this->hasValidResetGrant($request) || ! $user->canAccessPortal()) {
            $this->forgetResetFlow($request);
            return redirect()->route('patient.password.request')->withErrors(['password' => 'Quyền đặt lại mật khẩu không còn hiệu lực.']);
        }
        $user->update(['password' => Hash::make($request->validated('password'))]);
        $this->forgetResetFlow($request);
        return redirect()->route('patient.login')->with('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.');
    }

    private function demoEnabled(): bool { return app()->environment(['local', 'testing']); }
    private function hasValidResetGrant(Request $request): bool
    {
        $issuedAt = $request->session()->get('patient_reset_grant_issued_at');

        return $request->session()->has('patient_reset_user_id')
            && $request->session()->has('patient_reset_grant')
            && is_int($issuedAt)
            && $issuedAt >= now()->subMinutes((int) config('portal.reset_grant_minutes', 15))->timestamp;
    }

    private function forgetResetFlow(Request $request): void
    {
        $request->session()->forget(['patient_reset_user_id', 'patient_reset_grant', 'patient_reset_grant_issued_at']);
    }
}
