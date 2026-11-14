<?php

namespace App\Support\Doctor;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $request->isMethod('GET')
                ? redirect()->guest(route('patient.login'))
                : redirect()->route('patient.login');
        }

        // Vẫn cho tài khoản Doctor đăng xuất khi bị khóa.
        if (
            $request->routeIs('doctor.logout')
            && $user->role?->name === 'Doctor'
        ) {
            return $next($request);
        }

        $access = app(DoctorAccess::class);

        // Kiểm tra tài khoản, role và hồ sơ Doctor.
        $doctor = $access->doctor($user);

        if (! $doctor) {
            return $this->forbidden($request);
        }

        // Chỉ tài khoản bác sĩ hợp lệ mới truy cập chức năng khám.
        if (
            $request->routeIs('doctor.examination.*')
            && ! $access->canExamine($user, $doctor)
        ) {
            return $this->forbidden($request);
        }

        // Đặt bác sĩ đã xác minh vào request.
        $request->attributes->set(
            'current_doctor',
            $doctor
        );

        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'private, no-store, no-cache, must-revalidate'
        );

        $response->headers->set(
            'Pragma',
            'no-cache'
        );

        return $response;
    }

    private function forbidden(Request $request): Response
    {
        $message = 'Tài khoản không có quyền truy cập chức năng này.';

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        return response()->view(
            'doctor.forbidden',
            ['message' => $message],
            403
        );
    }
}
