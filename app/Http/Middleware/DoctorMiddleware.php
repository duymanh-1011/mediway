<?php

namespace App\Http\Middleware;

use App\Support\Doctor\DoctorAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (! $request->user()) {
            return $request->isMethod('GET')
                ? redirect()->guest(route('patient.login'))
                : redirect()->route('patient.login');
        }

        // Cho phép tài khoản bị khóa vẫn đăng xuất được.
        if ($request->routeIs('doctor.logout') && $request->user()->role?->name === 'Doctor') {
            return $next($request);
        }

        $access = app(DoctorAccess::class);
        $doctor = $access->doctor($request->user());

        if (! $doctor || ($request->routeIs('doctor.examination.*') && ! $access->canExamine($request->user(), $doctor))) {
            if ($request->expectsJson()) {
                abort(403, 'Tài khoản không có quyền truy cập Doctor Portal.');
            }

            return response()->view(
                'doctor.forbidden',
                [
                    'message' => 'Tài khoản bác sĩ không hợp lệ, không hoạt động hoặc không có quyền truy cập.'
                ],
                403
            );
        }

        $request->attributes->set('current_doctor', $doctor);

        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'private, no-store, no-cache, must-revalidate'
        );

        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
