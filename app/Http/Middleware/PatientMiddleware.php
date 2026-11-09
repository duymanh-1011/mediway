<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PatientMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('vi');
        if (! Auth::check()) {
            return $request->isMethod('GET')
                ? redirect()->guest(route('patient.login'))
                : redirect()->route('patient.login');
        }

        $user = Auth::user();
        if (! $user->canAccessPortal()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login')
                ->withErrors(['auth' => 'Tài khoản không có quyền truy cập khu vực bệnh nhân hoặc đã ngừng hoạt động.']);
        }

        $sessionHash = $request->session()->get('patient_temporary_password_hash');
        if (is_string($sessionHash) && ! hash_equals(Auth::guard()->hashPasswordForCookie($user->getAuthPassword()), $sessionHash)) {
            Auth::guard()->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login');
        }
        if ($user->must_change_password && ! $request->routeIs('patient.password.initial', 'patient.password.initial.update', 'patient.logout')) {
            return redirect()->route('patient.password.initial');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
