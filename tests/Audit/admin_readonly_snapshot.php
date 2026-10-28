<?php

use App\Http\Middleware\AdminMiddleware;
use App\Models\MedicalVisit;
use App\Models\ServiceOrderItem;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// SELECT-only snapshot. Never migrate, seed, repair or print patient information.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway') {
    throw new RuntimeException('Snapshot chỉ được đọc database MySQL mediway.');
}
$out = ['database' => DB::getDatabaseName(), 'engine' => DB::selectOne('SELECT VERSION() AS version')->version];
if (in_array('--test-processes', $argv, true)) {
    echo DB::table('information_schema.processlist')->where('db', 'mediway_codex_test_20261019')
        ->get(['id', 'time', 'state', 'command'])->toJson(JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}
$routes = collect(Route::getRoutes())->filter(fn ($r) => str_starts_with($r->uri(), 'admin/'));
$out['routes'] = ['total' => $routes->count(), 'without_admin_guard' => $routes
    ->reject(fn ($r) => in_array(AdminMiddleware::class, $r->gatherMiddleware(), true))
    ->map(fn ($r) => $r->getName())->values()->all()];
$out['index_paths'] = $routes->filter(fn ($r) => in_array('GET', $r->methods(), true)
    && str_ends_with($r->getName() ?? '', '.index') && ! str_contains($r->uri(), '{'))
    ->map(fn ($r) => '/'.$r->uri())->values()->all();
$tables = ['users' => 'users', 'administrators' => 'administrators', 'roles' => 'roles', 'patients' => 'patients',
    'personal-health-records' => 'personal_health_records', 'doctors' => 'doctors', 'employees' => 'employees',
    'specialties' => 'specialties', 'examination-rooms' => 'examination_rooms', 'service-groups' => 'service_groups',
    'medical-services' => 'medical_services', 'disease-groups' => 'disease_groups', 'diseases' => 'diseases', 'medicines' => 'medicines',
    'appointment-statuses' => 'appointment_statuses', 'doctor-schedules' => 'doctor_schedules', 'appointment-time-slots' => 'appointment_time_slots',
    'appointments' => 'appointments', 'visit-statuses' => 'visit_statuses', 'queue-statuses' => 'queue_statuses',
    'visits' => 'medical_visits', 'queues' => 'examination_room_queues', 'clinical-notes' => 'visit_clinical_notes',
    'diagnoses' => 'visit_diagnoses', 'conclusions' => 'visit_conclusions', 'service-statuses' => 'service_statuses',
    'payment-statuses' => 'payment_statuses', 'service-orders' => 'service_orders', 'service-results' => 'service_results',
    'prescriptions' => 'prescriptions', 'payables' => 'payables', 'payment-transactions' => 'payment_transactions', 'audit-logs' => 'audit_logs'];
$out['detail_form_paths'] = [];
foreach ($routes as $route) {
    $name = $route->getName();
    if (! in_array('GET', $route->methods(), true) || ! preg_match('/\.(create|edit|show)$/', $name ?? '')) {
        continue;
    }
    $module = explode('.', $name)[1];
    $parameters = [];
    foreach ($route->parameterNames() as $parameter) {
        $table = match ($parameter) {
            'visit', 'medicalVisit' => 'medical_visits', 'doctorSchedule' => 'doctor_schedules',
            'appointmentTimeSlot' => 'appointment_time_slots', 'serviceOrderItem' => 'service_order_items', 'payable' => 'payables',
            default => $tables[$module] ?? null,
        };
        if (! $table || ! ($id = DB::table($table)->value('id'))) {
            continue 2;
        }
        $parameters[$parameter] = $id;
    }
    $out['detail_form_paths'][] = route($name, $parameters, false);
}
$out['totals'] = [];
foreach (['users', 'patients', 'doctors', 'employees', 'appointments', 'medical_visits', 'service_orders', 'service_results', 'payables', 'payment_transactions'] as $table) {
    $out['totals'][$table] = DB::table($table)->count();
}
$out['migrations_missing'] = collect(glob(__DIR__.'/../../database/migrations/*.php'))
    ->map(fn ($f) => basename($f, '.php'))->diff(DB::table('migrations')->pluck('migration'))->values()->all();
$out['wrong_profile_roles'] = [];
foreach (['patients' => 'Patient', 'doctors' => 'Doctor', 'employees' => 'Employee', 'administrators' => 'Administrator'] as $table => $role) {
    $out['wrong_profile_roles'][$table] = DB::table($table)->join('users', 'users.id', '=', $table.'.user_id')
        ->join('roles', 'roles.id', '=', 'users.role_id')->where('roles.name', '!=', $role)->count();
}
$out['orphan_foreign_keys'] = [];
foreach (Schema::getTables() as $tableInfo) {
    $table = $tableInfo['name'];
    foreach (Schema::getForeignKeys($table) as $fk) {
        $query = DB::table($table.' as child')->leftJoin($fk['foreign_table'].' as parent', function ($join) use ($fk) {
            foreach ($fk['columns'] as $i => $column) {
                $join->on('child.'.$column, '=', 'parent.'.$fk['foreign_columns'][$i]);
            }
        });
        foreach ($fk['columns'] as $column) {
            $query->whereNotNull('child.'.$column);
        }
        $count = $query->whereNull('parent.'.$fk['foreign_columns'][0])->count();
        if ($count) {
            $out['orphan_foreign_keys'][$table.'.'.implode(',', $fk['columns'])] = $count;
        }
    }
}
$out['appointment_schedule_mismatch'] = DB::table('appointments as a')
    ->join('appointment_time_slots as slot', 'slot.id', '=', 'a.appointment_time_slot_id')
    ->join('doctor_schedules as s', 's.id', '=', 'slot.doctor_schedule_id')
    ->where(fn ($q) => $q->whereColumn('a.doctor_id', '!=', 's.doctor_id')->orWhereColumn('a.specialty_id', '!=', 's.specialty_id')
        ->orWhereColumn('a.examination_room_id', '!=', 's.examination_room_id')->orWhereColumn('a.appointment_date', '!=', 's.work_date'))->count();
$out['duplicate_live_patient_slot'] = DB::table('appointments as a')->join('appointment_statuses as s', 's.id', '=', 'a.appointment_status_id')
    ->where('s.name', '!=', 'CANCELLED')->groupBy('a.patient_id', 'a.appointment_time_slot_id')->havingRaw('COUNT(*) > 1')->get(['a.patient_id'])->count();
$out['visit_appointment_mismatch'] = DB::table('medical_visits as v')->join('appointments as a', 'a.id', '=', 'v.appointment_id')
    ->where(fn ($q) => $q->whereColumn('v.patient_id', '!=', 'a.patient_id')->orWhereColumn('v.doctor_id', '!=', 'a.doctor_id')
        ->orWhereColumn('v.specialty_id', '!=', 'a.specialty_id')->orWhereColumn('v.examination_room_id', '!=', 'a.examination_room_id'))->count();
$out['completed_visits_unresolved_services'] = MedicalVisit::whereHas('status', fn ($q) => $q->where('name', 'COMPLETED'))
    ->whereHas('serviceOrders.items', fn ($q) => $q->unresolved())->count();
$out['completed_visits_without_diagnosis_or_conclusion'] = MedicalVisit::whereHas('status', fn ($q) => $q->where('name', 'COMPLETED'))
    ->where(fn ($q) => $q->whereDoesntHave('diagnoses')->orWhereDoesntHave('conclusion'))->count();
$out['completed_services_without_result_content'] = ServiceOrderItem::with('result')->whereHas('serviceStatus', fn ($q) => $q->where('name', 'COMPLETED'))
    ->get()->filter(fn ($item) => ! $item->result?->hasContent())->count();
$out['completed_services_without_performer'] = ServiceOrderItem::whereHas('serviceStatus', fn ($q) => $q->where('name', 'COMPLETED'))
    ->whereNull('performed_by_employee_id')->count();
$out['cash_success_without_collector'] = DB::table('payment_transactions')->where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereNull('collected_by_employee_id')->count();
$out['payable_service_visit_mismatch'] = DB::table('payables as p')->join('service_order_items as i', 'i.id', '=', 'p.service_order_item_id')
    ->join('service_orders as o', 'o.id', '=', 'i.service_order_id')->whereColumn('p.medical_visit_id', '!=', 'o.medical_visit_id')->count();
$out['paid_without_successful_transaction'] = DB::table('payables as p')->join('payment_statuses as s', 's.id', '=', 'p.payment_status_id')
    ->where('s.name', 'PAID')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('payment_transactions as t')->whereColumn('t.payable_id', 'p.id')->where('t.transaction_result', 'SUCCESS'))->count();
$out['multiple_successes_per_payable'] = DB::table('payment_transactions')->where('transaction_result', 'SUCCESS')->groupBy('payable_id')->havingRaw('COUNT(*) > 1')->get(['payable_id'])->count();
$out['result_before_service_start'] = DB::table('service_results as r')->join('service_order_items as i', 'i.id', '=', 'r.service_order_item_id')->whereColumn('r.resulted_at', '<', 'i.started_at')->count();
$out['follow_up_before_visit'] = DB::table('visit_conclusions as c')->join('medical_visits as v', 'v.id', '=', 'c.medical_visit_id')
    ->where('has_follow_up', true)->whereRaw('DATE(c.follow_up_date) < DATE(v.checked_in_at)')->count();
$out['health_without_any_measurement'] = DB::table('personal_health_records')->where(function ($q) {
    foreach (['height_cm', 'weight_kg', 'systolic_blood_pressure', 'diastolic_blood_pressure', 'heart_rate', 'body_temperature', 'spo2', 'respiratory_rate', 'blood_glucose'] as $field) {
        $q->whereNull($field);
    }
})->count();
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
