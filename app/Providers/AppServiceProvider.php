<?php

namespace App\Providers;

use App\Contracts\PatientOtpSender;
use App\Services\LocalDemoPatientOtpSender;
use App\Services\EsmsDemoPatientOtpSender;
use App\Models\Administrator;
use App\Models\BusinessCodeGenerator;
use App\Models\Doctor;
use App\Models\DoctorDetail;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\ExaminationRoomQueue;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PatientDetail;
use App\Models\PersonalHealthRecord;
use App\Models\Role;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;
use App\Models\VisitClinicalNote;
use App\Models\VisitConclusion;
use App\Models\VisitDiagnosis;
use App\Observers\AdministrativeAuditObserver;
use App\Observers\ClinicalAuditObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PatientOtpSender::class, fn () => config('portal.esms.enabled')
            ? app(EsmsDemoPatientOtpSender::class) : app(LocalDemoPatientOtpSender::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([User::class, Administrator::class, Role::class,
            Doctor::class, DoctorDetail::class, Employee::class, EmployeeDetail::class,
            Patient::class, PatientDetail::class, PersonalHealthRecord::class] as $model) {
            $model::observe(AdministrativeAuditObserver::class);
        }

        foreach ([MedicalVisit::class, ExaminationRoomQueue::class, VisitClinicalNote::class,
            VisitDiagnosis::class, VisitConclusion::class, ServiceOrder::class, ServiceOrderItem::class] as $model) {
            $model::observe(ClinicalAuditObserver::class);
        }

        foreach (BusinessCodeGenerator::CATALOGS as $field => [$model]) {
            $model::creating(function (Model $record) use ($field): void {
                if (! $record->getAttribute($field)) {
                    $record->setAttribute($field, app(BusinessCodeGenerator::class)->next($field));
                }
            });
            $model::updating(function (Model $record) use ($field): void {
                if ($record->isDirty($field)) {
                    throw ValidationException::withMessages([$field => 'Mã đã cấp được giữ nguyên, không thể thay đổi thủ công.']);
                }
            });
        }

        View::composer('admin.layouts.partials.business-code', function (\Illuminate\View\View $view): void {
            $data = $view->getData();
            $record = $data['record'] ?? null;
            $view->with('displayCode', $record?->getAttribute($data['field']) ?? app(BusinessCodeGenerator::class)->preview($data['field']));
        });

        Paginator::defaultView('admin.layouts.partials.pagination');
        Paginator::defaultSimpleView('admin.layouts.partials.pagination');
        Lang::addLines(
            Arr::dot(['validation' => config('admin.validation', [])]), 'vi'
        );
    }
}
