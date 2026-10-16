<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ServiceStatusController;
use App\Http\Controllers\Admin\PaymentStatusController;
use App\Http\Controllers\Admin\ServiceOrderController;
use App\Http\Controllers\Admin\ServiceResultController;
use App\Http\Controllers\Admin\PrescriptionController;
use App\Http\Controllers\Admin\PayableController;
use App\Http\Controllers\Admin\PaymentTransactionController;
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
            Route::get('/service-orders', [ServiceOrderController::class, 'index'])
                ->name('service-orders.index');

            Route::get('/service-orders/{id}', [ServiceOrderController::class, 'show'])
                ->name('service-orders.show');

            Route::get('/service-results', [ServiceResultController::class, 'index'])
                ->name('service-results.index');

            Route::get('/service-results/{id}', [ServiceResultController::class, 'show'])
                ->name('service-results.show');

            Route::get('/service-results/{id}/edit', [ServiceResultController::class, 'edit'])
                ->name('service-results.edit');

            Route::patch('/service-results/{id}', [ServiceResultController::class, 'update'])
                ->name('service-results.update');
            Route::get('/prescriptions', [PrescriptionController::class, 'index'])
                ->name('prescriptions.index');

            Route::get('/prescriptions/{id}', [PrescriptionController::class, 'show'])
                ->name('prescriptions.show');

            Route::get('/prescriptions/{id}/edit', [PrescriptionController::class, 'edit'])
                ->name('prescriptions.edit');

            Route::patch('/prescriptions/{id}', [PrescriptionController::class, 'update'])
                ->name('prescriptions.update');

            Route::get('/payables', [PayableController::class, 'index'])
                ->name('payables.index');

            Route::get('/payables/{id}', [PayableController::class, 'show'])
                ->name('payables.show');

            Route::patch('/payables/{id}/status', [PayableController::class, 'updateStatus'])
                ->name('payables.update-status');

            Route::patch('/payables/{id}/cancel', [PayableController::class, 'cancel'])
                ->name('payables.cancel');


            Route::get(
                '/payment-transactions',
                [PaymentTransactionController::class, 'index']
            )->name('payment-transactions.index');

            Route::get(
                '/payment-transactions/{id}',
                [PaymentTransactionController::class, 'show']
            )->name('payment-transactions.show');
        });
    });
