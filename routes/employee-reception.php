<?php

use App\Http\Controllers\Employee\ReceptionAppointmentController;
use App\Http\Controllers\Employee\ReceptionDashboardController;
use App\Http\Controllers\Employee\WalkInReceptionController;
use App\Http\Middleware\EmployeeMiddleware;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

Route::prefix('nhan-vien')->name('employee.')
    ->middleware([EmployeeMiddleware::class, AuthenticateSession::class])->group(function (): void {
        Route::middleware(EmployeeMiddleware::class.':appointment.view')->group(function (): void {
            Route::get('/tiep-don', [ReceptionDashboardController::class, 'index'])->name('reception.index');
            Route::get('/lich-hen', [ReceptionAppointmentController::class, 'index'])->name('appointments.index');
            Route::post('/lich-hen/loc', [ReceptionAppointmentController::class, 'filter'])->name('appointments.filter');
            Route::get('/lich-hen/{appointment}', [ReceptionAppointmentController::class, 'show'])->name('appointments.show');
        });
        Route::post('/lich-hen/{appointment}/tiep-nhan', [ReceptionAppointmentController::class, 'checkIn'])
            ->middleware(EmployeeMiddleware::class.':reception.check_in')->name('appointments.check-in');
        Route::middleware(EmployeeMiddleware::class.':reception.check_in')->group(function (): void {
            Route::get('/tiep-don/truc-tiep', [WalkInReceptionController::class, 'create'])->name('walk-in.create');
            Route::post('/tiep-don/truc-tiep/tim-benh-nhan', [WalkInReceptionController::class, 'search'])->name('walk-in.search');
            Route::post('/tiep-don/truc-tiep', [WalkInReceptionController::class, 'store'])->name('walk-in.store');
            Route::get('/tiep-don/truc-tiep/{visit}', [WalkInReceptionController::class, 'show'])->name('walk-in.show');
        });
    });
