<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\ExaminationRoomController;
use App\Http\Controllers\Admin\ServiceGroupController;
use App\Http\Controllers\Admin\MedicalServiceController;
use App\Http\Controllers\Admin\DiseaseGroupController;
use App\Http\Controllers\Admin\DiseaseController;
use App\Http\Controllers\Admin\MedicineController;
use App\Http\Controllers\Admin\AppointmentStatusController;
use App\Http\Controllers\Admin\DoctorScheduleController;
use App\Http\Controllers\Admin\AppointmentTimeSlotController;
use App\Http\Controllers\Admin\AppointmentController;

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


            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/dashboard',
                [DashboardController::class, 'index']
            )->name('dashboard');


            /*
            |--------------------------------------------------------------------------
            | Danh mục khám
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Bệnh và thuốc
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'disease-groups',
                DiseaseGroupController::class
            );


            Route::resource(
                'diseases',
                DiseaseController::class
            );


            Route::resource(
                'medicines',
                MedicineController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Trạng thái lịch hẹn
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'appointment-statuses',
                AppointmentStatusController::class
            )->only([
                'index',
                'show',
                'edit',
                'update',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Lịch làm việc bác sĩ
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/doctor-schedules/calendar',
                [DoctorScheduleController::class, 'calendar']
            )->name(
                'doctor-schedules.calendar'
            );


            Route::resource(
                'doctor-schedules',
                DoctorScheduleController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Khung giờ khám
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/doctor-schedules/{doctorSchedule}/time-slots/create',
                [AppointmentTimeSlotController::class, 'create']
            )->name(
                'doctor-schedules.time-slots.create'
            );


            Route::post(
                '/doctor-schedules/{doctorSchedule}/time-slots',
                [AppointmentTimeSlotController::class, 'store']
            )->name(
                'doctor-schedules.time-slots.store'
            );


            Route::get(
                '/appointment-time-slots/{appointmentTimeSlot}/edit',
                [AppointmentTimeSlotController::class, 'edit']
            )->name(
                'appointment-time-slots.edit'
            );


            Route::put(
                '/appointment-time-slots/{appointmentTimeSlot}',
                [AppointmentTimeSlotController::class, 'update']
            )->name(
                'appointment-time-slots.update'
            );


            Route::delete(
                '/appointment-time-slots/{appointmentTimeSlot}',
                [AppointmentTimeSlotController::class, 'destroy']
            )->name(
                'appointment-time-slots.destroy'
            );


            /*
            |--------------------------------------------------------------------------
            | Lịch hẹn
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/appointments/{appointment}/cancel',
                [AppointmentController::class, 'cancel']
            )->name(
                'appointments.cancel'
            );


            Route::resource(
                'appointments',
                AppointmentController::class
            )->only([
                'index',
                'create',
                'store',
                'show',
                'edit',
                'update',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Logout
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/logout',
                [AuthController::class, 'logout']
            )->name('logout');
        });
    });