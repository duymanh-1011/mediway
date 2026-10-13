<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\ExaminationRoomController;
use App\Http\Controllers\Admin\ServiceGroupController;
use App\Http\Controllers\Admin\MedicalServiceController;

use App\Http\Middleware\AdminMiddleware;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix('admin')
    ->name('admin.')
    ->group(function () {


        Route::get(
            '/login',
            [AuthController::class, 'showLoginForm']
        )->name('login');

        Route::post(
            '/login',
            [AuthController::class, 'login']
        )->name('login.submit');


        Route::middleware(
            AdminMiddleware::class
        )->group(function () {

            Route::get(
                '/dashboard',
                [DashboardController::class, 'index']
            )->name('dashboard');

            Route::resource(
                'specialties',
                SpecialtyController::class
            );

            Route::resource(
                'examination-rooms',
                ExaminationRoomController::class
            );

            Route::resource(
                'service-groups',
                ServiceGroupController::class
            );

            Route::resource(
                'medical-services',
                MedicalServiceController::class
            );

            Route::post(
                '/logout',
                [AuthController::class, 'logout']
            )->name('logout');
        });
    });