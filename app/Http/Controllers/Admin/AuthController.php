<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (
            Auth::check()
            && Auth::user()->role
            && Auth::user()->role->name === 'Administrator'
        ) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
            ],
        ], [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (! Auth::attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => 'ACTIVE',
        ])) {
            return back()
                ->withErrors([
                    'phone' => 'Số điện thoại hoặc mật khẩu không chính xác.',
                ])
                ->onlyInput('phone');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->role || $user->role->name !== 'Administrator') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'phone' => 'Tài khoản không có quyền truy cập trang quản trị.',
                ])
                ->onlyInput('phone');
        }

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
}