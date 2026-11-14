<?php

namespace App\Http\Middleware;

use App\Support\DoctorAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('patient.login'));
        }

        if ($request->routeIs('doctor.logout')) {
            return $next($request);
        }

        abort_unless(app(DoctorAccess::class)->doctor($request->user()), 403, 'Tài khoản không có hồ sơ bác sĩ đang hoạt động.');

        if (! $request->routeIs('doctor.home', 'doctor.workspace.select')) {
            $workspace = $request->session()->get('doctor_workspace_mode');
            if (! in_array($workspace, ['examination', 'services'], true)) {
                $request->session()->forget('doctor_workspace_mode');
                return redirect()->route('doctor.home');
            }

            $route = (string) $request->route()?->getName();
            $serviceRoute = str_starts_with($route, 'doctor.services.')
                || str_starts_with($route, 'doctor.laboratory.')
                || str_starts_with($route, 'doctor.imaging.');
            if ($serviceRoute && $workspace !== 'services') {
                abort(403, 'Chức năng này không thuộc không gian làm việc đã chọn.');
            }
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
