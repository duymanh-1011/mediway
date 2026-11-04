<?php

namespace App\Http\Middleware;

use App\Support\EmployeeAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    public function handle(Request $request, Closure $next, ?string $capability = null): Response
    {
        if (! $request->user()) {
            return $request->isMethod('GET') ? redirect()->guest(route('patient.login')) : redirect()->route('patient.login');
        }
        // A revoked employee must still be able to end the current session.
        if ($request->routeIs('employee.logout')) {
            return $next($request);
        }
        $access = app(EmployeeAccess::class);
        if (! $access->employee($request->user())) {
            if ($request->expectsJson()) abort(403, 'Tài khoản không có quyền truy cập khu vực nhân viên.');
            return response()->view('employee.forbidden', ['message' => 'Tài khoản không có quyền truy cập khu vực nhân viên hoặc đã ngừng hoạt động.'], 403);
        }
        if ($capability !== null && ! $access->allows($request->user(), $capability)) {
            if ($request->expectsJson()) abort(403, 'Bạn không có quyền thực hiện chức năng này.');
            return response()->view('employee.forbidden', ['message' => 'Bạn không có quyền thực hiện chức năng này.'], 403);
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }
}
