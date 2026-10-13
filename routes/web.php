<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\MedicalVisitController;
use App\Http\Controllers\Admin\ExaminationRoomQueueController;
use App\Http\Controllers\Admin\VisitClinicalNoteController;
use App\Http\Controllers\Admin\VisitDiagnosisController;
use App\Http\Controllers\Admin\VisitConclusionController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Route Đăng nhập
        Route::get('/login', [AuthController::class, 'showLoginForm'])
            ->name('login');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('login.submit');

        // Nhóm Route yêu cầu quyền Quản trị viên (AdminMiddleware)
        Route::middleware(AdminMiddleware::class)->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('logout');

            // Nhật ký hoạt động
            Route::get('/audit-logs', [AuditLogController::class, 'index'])
                ->name('audit-logs.index');

            Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])
                ->name('audit-logs.show');

            // ==========================================
            // PHẦN CHỨC NĂNG CỦA HUY (7 BẢNG VẬN HÀNH KHÁM & LÂM SÀNG)
            // ==========================================

            // 1. Quản lý Lượt khám (medical_visits)
            // 1. Lượt khám
    Route::get('/visits', [MedicalVisitController::class, 'index'])->name('visits.index');
    Route::get('/visits/{id}', [MedicalVisitController::class, 'show'])->name('visits.show');
    Route::patch('/visits/{id}/status', [MedicalVisitController::class, 'updateStatus'])->name('visits.updateStatus');

    // 2. Hàng đợi phòng khám
    Route::get('/queues', [ExaminationRoomQueueController::class, 'index'])->name('queues.index');
    Route::post('/queues/{id}/call', [ExaminationRoomQueueController::class, 'callPatient'])->name('queues.call');
    Route::post('/queues/{id}/start-service', [ExaminationRoomQueueController::class, 'startService'])->name('queues.startService');
    Route::post('/queues/{id}/complete', [ExaminationRoomQueueController::class, 'complete'])->name('queues.complete');
    Route::post('/queues/{id}/skip', [ExaminationRoomQueueController::class, 'skip'])->name('queues.skip');

    // 3. Nội dung khám (Quản lý danh sách & form độc lập)
    Route::get('/clinical-notes', [VisitClinicalNoteController::class, 'index'])->name('clinical-notes.index');
    Route::post('/visits/{visitId}/clinical-notes', [VisitClinicalNoteController::class, 'storeOrUpdate'])->name('clinical-notes.storeOrUpdate');

    // 4. Chẩn đoán (Quản lý danh sách & phân loại chẩn đoán)
    Route::get('/diagnoses', [VisitDiagnosisController::class, 'index'])->name('diagnoses.index');
    Route::post('/visits/{visitId}/diagnoses', [VisitDiagnosisController::class, 'store'])->name('diagnoses.store');
    Route::delete('/diagnoses/{id}', [VisitDiagnosisController::class, 'destroy'])->name('diagnoses.destroy');

    // 5. Kết luận lượt khám (Quản lý danh sách kết luận & tái khám)
    Route::get('/conclusions', [VisitConclusionController::class, 'index'])->name('conclusions.index');
    Route::post('/visits/{visitId}/conclusions', [VisitConclusionController::class, 'storeOrUpdate'])->name('conclusions.storeOrUpdate');
        });
    });
