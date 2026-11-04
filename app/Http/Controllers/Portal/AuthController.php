<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return Auth::user()->canAccessPortal() ? redirect()->route('patient.home') : redirect()->route('admin.dashboard');
        }
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
        if ($user instanceof User && app(\App\Support\EmployeeAccess::class)->employee($user)) {
            RateLimiter::clear($key);
            $request->session()->forget('url.intended');
            $request->session()->put('password_hash_'.Auth::getDefaultDriver(), Auth::guard()->hashPasswordForCookie($user->getAuthPassword()));
            return redirect()->route('employee.home')->with('success', 'Đăng nhập thành công.');
        }
        // The Doctor Portal is supplied by its owner. Do not create or route to
        // a placeholder when that portal has not been installed.
        if ($user instanceof User && Route::has('doctor.home')) {
            $user->load(['role', 'doctor']);
            if ($user->status === 'ACTIVE' && $user->role?->name === 'Doctor'
                && $user->role?->status === 'ACTIVE' && $user->doctor?->status === 'ACTIVE') {
                RateLimiter::clear($key);
                $request->session()->forget('url.intended');
                return redirect()->route('doctor.home')->with('success', 'Đăng nhập thành công.');
            }
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
        return redirect()->intended(route('patient.home'))->with('success', 'Đăng nhập thành công.');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('public.home')->with('success', 'Bạn đã đăng xuất an toàn.');
    }
}
