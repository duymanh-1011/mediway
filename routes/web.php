
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

                Route::get('/dashboard', [DashboardController::class, 'index'])
                    ->name('dashboard');

                Route::post('/logout', [AuthController::class, 'logout'])
                    ->name('logout');

                Route::resource('roles', RoleController::class);

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


                Route::patch(
                    'administrators/{administrator}/status',
                    [AdministratorController::class, 'updateStatus']
                )->name('administrators.update-status');

                Route::resource(
                    'administrators',
                    AdministratorController::class
                )->except(['destroy']);

                Route::get(
                    'patients/{patient}/documents/{type}',
                    [PatientController::class, 'document']
                )
                    ->where(
                        'type',
                        'avatar|citizen-id-front|citizen-id-back|profile-pdf'
                    )
                    ->name('patients.document');

          
                Route::resource('patients', PatientController::class)
                    ->except(['destroy']);

   
                Route::resource(
                    'personal-health-records',
                    PersonalHealthRecordController::class
                )->except(['destroy']);

                Route::get(
                    'doctors/{doctor}/documents/{type}',
                    [DoctorController::class, 'document']
                )
                    ->where(
                        'type',
                        'avatar|citizen-id-front|citizen-id-back|practice-license|profile-pdf'
                    )
                    ->name('doctors.document');

                Route::resource('doctors', DoctorController::class)
                    ->except(['destroy']);
            });
    });
