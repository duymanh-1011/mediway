<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('patient.login'));
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();
            if ($user?->canAccessAdmin()) return route('admin.dashboard');
            if ($user && app(\App\Support\EmployeeAccess::class)->employee($user)) return route('employee.home');
            if ($user?->canAccessPortal()) return route('patient.home');
            if ($user && Route::has('doctor.home')) {
                $user->load(['role', 'doctor']);
                if ($user->status === 'ACTIVE' && $user->role?->name === 'Doctor'
                    && $user->role?->status === 'ACTIVE' && $user->doctor?->status === 'ACTIVE') {
                    return route('doctor.home');
                }
            }
            return route('public.home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['otp']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() !== 403 || ! $request->is('admin/*') || $request->expectsJson()
                || ! Auth::check() || ! Auth::user()->canAccessAdmin()) {
                return null;
            }
            $showRoute = preg_replace('/\.edit$/', '.show', $request->route()?->getName() ?? '');
            $backUrl = Route::has($showRoute) && str_ends_with($showRoute, '.show')
                ? route($showRoute, $request->route()->parameters()) : route('admin.dashboard');
            $message = $exception->getMessage();
            if (in_array($message, ['', 'Forbidden', 'This action is unauthorized.'], true)) {
                $message = 'Bạn chưa được phép thực hiện thao tác này.';
            }

            return response()->view('admin.errors.forbidden', compact('backUrl', 'message'), 403);
        });
    })->create();
