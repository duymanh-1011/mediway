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

use App\Http\Controllers\Admin\VisitStatusController;
use App\Http\Controllers\Admin\QueueStatusController;
use App\Http\Controllers\Admin\MedicalVisitController;
use App\Http\Controllers\Admin\ExaminationRoomQueueController;
use App\Http\Controllers\Admin\VisitClinicalNoteController;
use App\Http\Controllers\Admin\MedicalVisitClinicalNoteController;
use App\Http\Controllers\Admin\VisitDiagnosisController;
use App\Http\Controllers\Admin\MedicalVisitDiagnosisController;
use App\Http\Controllers\Admin\VisitConclusionController;
use App\Http\Controllers\Admin\MedicalVisitConclusionController;
use App\Http\Controllers\Admin\ServiceOrderItemController;

use App\Http\Middleware\AdminMiddleware;


/*
|--------------------------------------------------------------------------
| Trang gốc
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('admin.login');
});


/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

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
            | Nhật ký hoạt động
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/audit-logs',
                [AuditLogController::class, 'index']
            )->name('audit-logs.index');

            Route::get(
                '/audit-logs/{id}',
                [AuditLogController::class, 'show']
            )->name('audit-logs.show');


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
            )->name('users.update-status');

            Route::patch(
                'users/{user}/reset-password',
                [UserController::class, 'resetPassword']
            )->name('users.reset-password');

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
            )->name('administrators.update-status');

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
                ->name('patients.document');

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
                ->name('doctors.document');

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
                ->name('employees.document');

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
            */

            Route::get(
                '/doctor-schedules/calendar',
                [DoctorScheduleController::class, 'calendar']
            )->name('doctor-schedules.calendar');

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
                '/appointments/{appointment}/check-in',
                [AppointmentController::class, 'checkIn']
            )->name(
                'appointments.check-in'
            );

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
            | Trạng thái lượt khám
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'visit-statuses',
                VisitStatusController::class
            )->only([
                'index',
                'show',
                'edit',
                'update',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Trạng thái hàng đợi
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'queue-statuses',
                QueueStatusController::class
            )->only([
                'index',
                'show',
                'edit',
                'update',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Lượt khám
            |--------------------------------------------------------------------------
            */

            Route::patch(
                '/visits/{medicalVisit}/status',
                [MedicalVisitController::class, 'updateStatus']
            )->name(
                'visits.update-status'
            );

            Route::resource(
                'visits',
                MedicalVisitController::class
            )->only([
                'index',
                'show',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Hàng đợi phòng khám
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/queues/{queue}/call',
                [ExaminationRoomQueueController::class, 'call']
            )->name(
                'queues.call'
            );

            Route::post(
                '/queues/{queue}/start-service',
                [
                    ExaminationRoomQueueController::class,
                    'startService',
                ]
            )->name(
                'queues.start-service'
            );

            Route::post(
                '/queues/{queue}/complete',
                [ExaminationRoomQueueController::class, 'complete']
            )->name(
                'queues.complete'
            );

            Route::post(
                '/queues/{queue}/skip',
                [ExaminationRoomQueueController::class, 'skip']
            )->name(
                'queues.skip'
            );

            Route::resource(
                'queues',
                ExaminationRoomQueueController::class
            )->only([
                'index',
                'show',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Nội dung khám
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/visits/{visit}/clinical-note/create',
                [
                    MedicalVisitClinicalNoteController::class,
                    'create',
                ]
            )->name(
                'visits.clinical-note.create'
            );

            Route::post(
                '/visits/{visit}/clinical-note',
                [
                    MedicalVisitClinicalNoteController::class,
                    'store',
                ]
            )->name(
                'visits.clinical-note.store'
            );

            Route::resource(
                'clinical-notes',
                VisitClinicalNoteController::class
            )->only([
                'index',
                'show',
                'edit',
                'update',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Chẩn đoán
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/visits/{visit}/diagnoses/create',
                [
                    MedicalVisitDiagnosisController::class,
                    'create',
                ]
            )->name(
                'visits.diagnoses.create'
            );

            Route::post(
                '/visits/{visit}/diagnoses',
                [
                    MedicalVisitDiagnosisController::class,
                    'store',
                ]
            )->name(
                'visits.diagnoses.store'
            );

            Route::resource(
                'diagnoses',
                VisitDiagnosisController::class
            )->only([
                'index',
                'show',
                'edit',
                'update',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Kết luận lượt khám
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/visits/{visit}/conclusion/create',
                [
                    MedicalVisitConclusionController::class,
                    'create',
                ]
            )->name(
                'visits.conclusion.create'
            );

            Route::post(
                '/visits/{visit}/conclusion',
                [
                    MedicalVisitConclusionController::class,
                    'store',
                ]
            )->name(
                'visits.conclusion.store'
            );

            Route::resource(
                'conclusions',
                VisitConclusionController::class
            )->only([
                'index',
                'show',
                'edit',
                'update',
            ]);


            /*
            |--------------------------------------------------------------------------
            | PHẦN CỦA TẠO
            |--------------------------------------------------------------------------
            */


            /*
            |--------------------------------------------------------------------------
            | Trạng thái dịch vụ
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/service-statuses',
                [ServiceStatusController::class, 'index']
            )->name(
                'service-statuses.index'
            );

            Route::get(
                '/service-statuses/{id}',
                [ServiceStatusController::class, 'show']
            )->name(
                'service-statuses.show'
            );

            Route::get(
                '/service-statuses/{id}/edit',
                [ServiceStatusController::class, 'edit']
            )->name(
                'service-statuses.edit'
            );

            Route::patch(
                '/service-statuses/{id}',
                [ServiceStatusController::class, 'update']
            )->name(
                'service-statuses.update'
            );


            /*
            |--------------------------------------------------------------------------
            | Trạng thái thanh toán
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/payment-statuses',
                [PaymentStatusController::class, 'index']
            )->name(
                'payment-statuses.index'
            );

            Route::get(
                '/payment-statuses/{id}',
                [PaymentStatusController::class, 'show']
            )->name(
                'payment-statuses.show'
            );

            Route::get(
                '/payment-statuses/{id}/edit',
                [PaymentStatusController::class, 'edit']
            )->name(
                'payment-statuses.edit'
            );

            Route::patch(
                '/payment-statuses/{id}',
                [PaymentStatusController::class, 'update']
            )->name(
                'payment-statuses.update'
            );


            /*
            |--------------------------------------------------------------------------
            | Chỉ định dịch vụ
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/visits/{visit}/service-orders/create',
                [ServiceOrderController::class, 'create']
            )->name(
                'visits.service-orders.create'
            );

            Route::post(
                '/visits/{visit}/service-orders',
                [ServiceOrderController::class, 'store']
            )->name(
                'visits.service-orders.store'
            );

            Route::get(
                '/service-orders',
                [ServiceOrderController::class, 'index']
            )->name(
                'service-orders.index'
            );

            Route::get(
                '/service-orders/{id}',
                [ServiceOrderController::class, 'show']
            )->name(
                'service-orders.show'
            );


            /*
            |--------------------------------------------------------------------------
            | Thực hiện dịch vụ
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/service-order-items/{serviceOrderItem}/start',
                [
                    ServiceOrderItemController::class,
                    'start',
                ]
            )->name(
                'service-order-items.start'
            );

            Route::post(
                '/service-order-items/{serviceOrderItem}/complete',
                [
                    ServiceOrderItemController::class,
                    'complete',
                ]
            )->name(
                'service-order-items.complete'
            );


            /*
            |--------------------------------------------------------------------------
            | Kết quả dịch vụ
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/service-order-items/{serviceOrderItem}/service-result/create',
                [
                    ServiceResultController::class,
                    'create',
                ]
            )->name(
                'service-order-items.service-results.create'
            );

            Route::post(
                '/service-order-items/{serviceOrderItem}/service-result',
                [
                    ServiceResultController::class,
                    'store',
                ]
            )->name(
                'service-order-items.service-results.store'
            );

            Route::get(
                '/service-results',
                [ServiceResultController::class, 'index']
            )->name(
                'service-results.index'
            );

            Route::get(
                '/service-results/{id}',
                [ServiceResultController::class, 'show']
            )->name(
                'service-results.show'
            );

            Route::get(
                '/service-results/{id}/edit',
                [ServiceResultController::class, 'edit']
            )->name(
                'service-results.edit'
            );

            Route::patch(
                '/service-results/{id}',
                [ServiceResultController::class, 'update']
            )->name(
                'service-results.update'
            );


            /*
            |--------------------------------------------------------------------------
            | Đơn thuốc
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/prescriptions',
                [PrescriptionController::class, 'index']
            )->name(
                'prescriptions.index'
            );

            Route::get(
                '/prescriptions/{id}',
                [PrescriptionController::class, 'show']
            )->name(
                'prescriptions.show'
            );

            Route::get(
                '/prescriptions/{id}/edit',
                [PrescriptionController::class, 'edit']
            )->name(
                'prescriptions.edit'
            );

            Route::patch(
                '/prescriptions/{id}',
                [PrescriptionController::class, 'update']
            )->name(
                'prescriptions.update'
            );


            /*
            |--------------------------------------------------------------------------
            | Khoản phải thanh toán
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/payables',
                [PayableController::class, 'index']
            )->name(
                'payables.index'
            );

            Route::get(
                '/payables/{id}',
                [PayableController::class, 'show']
            )->name(
                'payables.show'
            );

            Route::patch(
                '/payables/{id}/status',
                [PayableController::class, 'updateStatus']
            )->name(
                'payables.update-status'
            );

            Route::patch(
                '/payables/{id}/cancel',
                [PayableController::class, 'cancel']
            )->name(
                'payables.cancel'
            );


            /*
            |--------------------------------------------------------------------------
            | Giao dịch thanh toán
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/payables/{payable}/payment-transactions/create',
                [PaymentTransactionController::class, 'create']
            )->name(
                'payables.payment-transactions.create'
            );

            Route::post(
                '/payables/{payable}/payment-transactions',
                [PaymentTransactionController::class, 'store']
            )->name(
                'payables.payment-transactions.store'
            );

            Route::get(
                '/payment-transactions',
                [PaymentTransactionController::class, 'index']
            )->name(
                'payment-transactions.index'
            );

            Route::get(
                '/payment-transactions/{id}',
                [PaymentTransactionController::class, 'show']
            )->name(
                'payment-transactions.show'
            );


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