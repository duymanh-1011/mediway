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
            return redirect()->route('patient.login');
        }

        if (! Auth::user()->canAccessPortal()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login')
                ->withErrors(['auth' => 'Tài khoản không có quyền truy cập khu vực bệnh nhân hoặc đã ngừng hoạt động.']);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
