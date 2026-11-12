<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $request->isMethod('GET') ? redirect()->guest(route('patient.login')) : redirect()->route('patient.login');
        }

        // Cho phép kết thúc phiên ngay cả khi tài khoản vừa bị khóa.
        if ($request->routeIs('doctor.logout')) {
            return $next($request);
        }

        // Kiểm tra cả tài khoản, vai trò và hồ sơ bác sĩ trước mỗi yêu cầu.
        $user->load(['role', 'doctor']);
        abort_unless($user->status === 'ACTIVE' && $user->role?->name === 'Doctor'
            && $user->role?->status === 'ACTIVE' && $user->doctor?->status === 'ACTIVE', 403);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        return $response;
    }
}
