<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\LoginRequest;
use App\Http\Requests\Portal\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function create()
    {
        return view('portal.auth.login');
    }

    public function store(LoginRequest $request): Response
    {
        $data = $request->validated();
        $key = 'patient-login:'.hash('sha256', $data['phone'].'|'.$request->ip());
        $ipKey = 'patient-login-ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            return back()->withErrors(['phone' => 'Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau '.max(1, RateLimiter::availableIn($key), RateLimiter::availableIn($ipKey)).' giây.'])->onlyInput('phone');
        }
        if (! Auth::attempt(['phone' => $data['phone'], 'password' => $data['password'], 'status' => 'ACTIVE'])) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            return back()->withErrors(['phone' => 'Số điện thoại hoặc mật khẩu không chính xác.'])->onlyInput('phone');
        }
        $request->session()->regenerate();
        $user = Auth::user();
        if ($user instanceof User && $user->canAccessAdmin()) {
            RateLimiter::clear($key);
            // Không dùng URL đang chờ của bệnh nhân để chuyển tài khoản quản trị.
            $request->session()->forget('url.intended');
            return redirect()->route('admin.dashboard')->with('success', 'Đăng nhập thành công.');
        }
        if ($user instanceof User && ($employee = app(EmployeeAccess::class)->employee($user))) {
            RateLimiter::clear($key);
            $request->session()->forget('url.intended');
            $request->session()->forget(['employee_workspace_mode', 'doctor_workspace_mode']);
            $request->session()->put('password_hash_'.Auth::getDefaultDriver(), Auth::guard()->hashPasswordForCookie($user->getAuthPassword()));
            return redirect()->route(EmployeeAccess::homeRoute($employee))->with('success', 'Đăng nhập thành công.');
        }
        if ($user instanceof User && app(\App\Support\DoctorAccess::class)->doctor($user)) {
            RateLimiter::clear($key);
            $request->session()->forget('url.intended');
            $request->session()->forget(['employee_workspace_mode', 'doctor_workspace_mode']);
            return redirect()->route('doctor.home')->with('success', 'Đăng nhập thành công.');
        }
        if (! $user instanceof User || ! $user->canAccessPortal()) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['phone' => 'Tài khoản chưa có khu vực làm việc phù hợp hoặc không còn hoạt động.'])->onlyInput('phone');
        }
        RateLimiter::clear($key);
        if ($user->must_change_password) {
            $request->session()->put('patient_temporary_password_hash', Auth::guard()->hashPasswordForCookie($user->getAuthPassword()));

            return redirect()->route('patient.password.initial');
        }
        $request->session()->forget('patient_temporary_password_hash');

        return redirect()->intended(route('patient.home'))->with('success', 'Đăng nhập thành công.');
    }

    public function initialPassword(Request $request)
    {
        return $request->user()->must_change_password
            ? view('portal.auth.password-initial')
            : redirect()->route('patient.home');
    }

    public function updateInitialPassword(ResetPasswordRequest $request)
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('patient.home');
        }

        $sessionHash = $request->session()->get('patient_temporary_password_hash');
        $user = DB::transaction(function () use ($request, $sessionHash): User {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->canAccessPortal() && $user->must_change_password && is_string($sessionHash)
                && hash_equals(Auth::guard()->hashPasswordForCookie($user->getAuthPassword()), $sessionHash), 403);

            $password = $request->validated('password');
            if (Hash::check($password, $user->password)) {
                throw ValidationException::withMessages(['password' => 'Mật khẩu mới phải khác mật khẩu tạm.']);
            }
            $user->update(['password' => $password, 'must_change_password' => false]);
            AuditLog::create([
                'user_id' => $user->id, 'role_name_snapshot' => 'Patient', 'action' => 'UPDATE',
                'entity_type' => 'users', 'entity_id' => $user->id, 'entity_business_code' => $user->business_code,
                'new_values' => ['credential_changed' => true], 'description' => 'Bệnh nhân đổi mật khẩu tạm khi đăng nhập lần đầu.',
                'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'performed_at' => now(),
            ]);

            return $user;
        });

        Auth::setUser($user);
        $request->session()->forget('patient_temporary_password_hash');
        $request->session()->regenerate();

        return redirect()->intended(route('patient.home'))->with('success', 'Đã đổi mật khẩu. Bạn có thể sử dụng tài khoản Mediway.');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('public.home')->with('success', 'Bạn đã đăng xuất an toàn.');
    }
}
