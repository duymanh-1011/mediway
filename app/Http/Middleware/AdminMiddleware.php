<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('vi');
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        if (! $user->canAccessAdmin()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'auth' => 'Bạn không có quyền truy cập khu vực quản trị.',
                ]);
        }

        return $next($request);
    }
}
