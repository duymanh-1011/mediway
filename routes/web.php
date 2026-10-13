<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdministratorController;

use App\Http\Middleware\AdminMiddleware;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Đăng nhập Admin
        Route::get('/login', [AuthController::class, 'showLoginForm'])
            ->name('login');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('login.submit');

        Route::middleware(AdminMiddleware::class)
            ->group(function () {

                // Dashboard
                Route::get('/dashboard', [DashboardController::class, 'index'])
                    ->name('dashboard');

                // Đăng xuất
                Route::post('/logout', [AuthController::class, 'logout'])
                    ->name('logout');

                // Ngày 1: Quản lý vai trò
                Route::resource('roles', RoleController::class);

                // Ngày 2 - Phần A: Quản lý tài khoản
                Route::patch(
                    'users/{user}/status',
                    [UserController::class, 'updateStatus']
                )->name('users.update-status');

                Route::patch(
                    'users/{user}/reset-password',
                    [UserController::class, 'resetPassword']
                )->name('users.reset-password');

                Route::resource('users', UserController::class)
                    ->except(['destroy']);

                // Ngày 2 - Phần B: Quản lý quản trị viên
                Route::patch(
                    'administrators/{administrator}/status',
                    [AdministratorController::class, 'updateStatus']
                )->name('administrators.update-status');

                Route::resource(
                    'administrators',
                    AdministratorController::class
                )->except(['destroy']);
            });
    });