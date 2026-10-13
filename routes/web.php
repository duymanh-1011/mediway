<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ServiceStatusController;
use App\Http\Controllers\Admin\PaymentStatusController;
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


        Route::middleware(AdminMiddleware::class)->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('logout');
                
            Route::get('/audit-logs', [AuditLogController::class, 'index'])
                ->name('audit-logs.index');

            Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])
                ->name('audit-logs.show');

            Route::get(
                '/service-statuses',
                [ServiceStatusController::class, 'index']
            )->name('service-statuses.index');

            Route::get(
                '/service-statuses/{id}',
                [ServiceStatusController::class, 'show']
            )->name('service-statuses.show');

            Route::get(
                '/service-statuses/{id}/edit',
                [ServiceStatusController::class, 'edit']
            )->name('service-statuses.edit');

            Route::patch(
                '/service-statuses/{id}',
                [ServiceStatusController::class, 'update']
            )->name('service-statuses.update');

            Route::get(
                '/payment-statuses',
                [PaymentStatusController::class, 'index']
            )->name('payment-statuses.index');

            Route::get(
                '/payment-statuses/{id}',
                [PaymentStatusController::class, 'show']
            )->name('payment-statuses.show');

            Route::get(
                '/payment-statuses/{id}/edit',
                [PaymentStatusController::class, 'edit']
            )->name('payment-statuses.edit');

            Route::patch(
                '/payment-statuses/{id}',
                [PaymentStatusController::class, 'update']
            )->name('payment-statuses.update');
        });
    });
