<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PersonalHealthRecordController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\EmployeeController;

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


        /*
        |--------------------------------------------------------------------------
        | Đăng nhập
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/login',
            [AuthController::class, 'showLoginForm']
        )->name('login');


        Route::post(
            '/login',
            [AuthController::class, 'login']
        )->name('login.submit');


        /*
        |--------------------------------------------------------------------------
        | Khu vực Admin
        |--------------------------------------------------------------------------
        */

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
            | Vai trò
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'roles',
                RoleController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Tài khoản
            |--------------------------------------------------------------------------
            */

            Route::patch(
                'users/{user}/status',
                [UserController::class, 'updateStatus']
            )->name(
                'users.update-status'
            );


            Route::patch(
                'users/{user}/reset-password',
                [UserController::class, 'resetPassword']
            )->name(
                'users.reset-password'
            );


            Route::resource(
                'users',
                UserController::class
            )->except([
                'destroy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Quản trị viên
            |--------------------------------------------------------------------------
            */

            Route::patch(
                'administrators/{administrator}/status',
                [AdministratorController::class, 'updateStatus']
            )->name(
                'administrators.update-status'
            );


            Route::resource(
                'administrators',
                AdministratorController::class
            )->except([
                'destroy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Bệnh nhân
            |--------------------------------------------------------------------------
            */

            Route::get(
                'patients/{patient}/documents/{type}',
                [PatientController::class, 'document']
            )
                ->where(
                    'type',
                    'avatar|citizen-id-front|citizen-id-back|profile-pdf'
                )
                ->name(
                    'patients.document'
                );


            Route::resource(
                'patients',
                PatientController::class
            )->except([
                'destroy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Hồ sơ sức khỏe cá nhân
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'personal-health-records',
                PersonalHealthRecordController::class
            )->except([
                'destroy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Bác sĩ
            |--------------------------------------------------------------------------
            */

            Route::get(
                'doctors/{doctor}/documents/{type}',
                [DoctorController::class, 'document']
            )
                ->where(
                    'type',
                    'avatar|citizen-id-front|citizen-id-back|practice-license|profile-pdf'
                )
                ->name(
                    'doctors.document'
                );


            Route::resource(
                'doctors',
                DoctorController::class
            )->except([
                'destroy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Nhân viên
            |--------------------------------------------------------------------------
            */

            Route::get(
                'employees/{employee}/documents/{type}',
                [EmployeeController::class, 'document']
            )
                ->where(
                    'type',
                    'avatar|citizen-id-front|citizen-id-back|profile-pdf'
                )
                ->name(
                    'employees.document'
                );


            Route::resource(
                'employees',
                EmployeeController::class
            )->except([
                'destroy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Chuyên khoa
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'specialties',
                SpecialtyController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Phòng khám
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'examination-rooms',
                ExaminationRoomController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Nhóm dịch vụ
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'service-groups',
                ServiceGroupController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Dịch vụ y tế
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'medical-services',
                MedicalServiceController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Nhóm bệnh
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'disease-groups',
                DiseaseGroupController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Danh mục bệnh
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'diseases',
                DiseaseController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Thuốc
            |--------------------------------------------------------------------------
            */

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
            |
            | Calendar phải đặt trước resource để Laravel không hiểu
            | "calendar" là {doctorSchedule}.
            |
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
            | Đăng xuất
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/logout',
                [AuthController::class, 'logout']
            )->name(
                'logout'
            );
        });
    });