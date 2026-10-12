<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {


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


                Route::resource('roles', RoleController::class);
            });
    });