<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->canAccessAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): Response
    {
        app()->setLocale('vi');
        $credentials = $request->validate([
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
            'password' => [
                'required',
                'string',
            ],
        ], [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $limits = [
            'admin-login:credentials:'.hash('sha256', $credentials['phone'].'|'.$request->ip()) => 5,
            'admin-login:ip:'.hash('sha256', (string) $request->ip()) => 30,
        ];
        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                return $this->throttledResponse($request, max(1, RateLimiter::availableIn($key)));
            }
        }

        if (! Auth::attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => 'ACTIVE',
        ])) {
            $this->recordFailedLogin(array_keys($limits));

            return back()
                ->withErrors([
                    'phone' => 'Số điện thoại hoặc mật khẩu không chính xác.',
                ])
                ->onlyInput('phone');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->canAccessAdmin()) {
            $this->recordFailedLogin(array_keys($limits));
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'phone' => 'Tài khoản không có quyền truy cập trang quản trị.',
                ])
                ->onlyInput('phone');
        }

        RateLimiter::clear(array_key_first($limits));

        return redirect()
            ->intended(route('admin.dashboard'))
            ->with('success', 'Đăng nhập thành công.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('success', 'Đăng xuất thành công.');
    }

    /** @param list<string> $keys */
    private function recordFailedLogin(array $keys): void
    {
        foreach ($keys as $key) {
            RateLimiter::hit($key, 60);
        }
    }

    private function throttledResponse(Request $request, int $seconds): Response
    {
        $message = 'Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau '.$seconds.' giây.';
        $headers = ['Retry-After' => (string) $seconds];
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'errors' => ['phone' => [$message]]], 429, $headers);
        }
        $request->flashOnly('phone');

        return response()->view('admin.auth.login', [
            'errors' => (new ViewErrorBag)->put('default', new MessageBag(['phone' => $message])),
        ], 429, $headers);
    }
}
