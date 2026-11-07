<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') throw new RuntimeException('Unsafe database');
if (($argv[1] ?? '') === 'create') {
    $tag = substr((string) Str::ulid(), -10);
    $appointment = (array) DB::table('appointments')->where('id', 4)->first();
    unset($appointment['id']);
    $appointment['appointment_code'] = 'H'.$tag;
    $appointment['checkin_token'] = 'H-'.$tag;
    $appointment['appointment_date'] = now('Asia/Ho_Chi_Minh')->toDateString();
    $appointmentId = DB::table('appointments')->insertGetId($appointment);
    $visit = (array) DB::table('medical_visits')->where('id', 4)->first();
    unset($visit['id']);
    $visit['visit_code'] = 'H'.$tag;
    $visit['appointment_id'] = $appointmentId;
    $visitId = DB::table('medical_visits')->insertGetId($visit);
    $item = (array) DB::table('service_order_items')->first();
    $order = (array) DB::table('service_orders')->where('id', $item['service_order_id'])->first();
    unset($order['id']);
    $order['service_order_code'] = 'H'.$tag;
    $order['medical_visit_id'] = $visitId;
    $orderId = DB::table('service_orders')->insertGetId($order);
    unset($item['id']);
    $item['service_order_id'] = $orderId;
    $item['service_status_id'] = DB::table('service_statuses')->where('name', 'PENDING')->value('id') ?? $item['service_status_id'];
    $itemId = DB::table('service_order_items')->insertGetId($item);
    $payable = (array) DB::table('payables')->where('id', 12)->first();
    unset($payable['id']);
    $payable['payable_code'] = 'H'.$tag;
    $payable['medical_visit_id'] = $visitId;
    $payable['payment_type'] = 'SERVICE_FEE';
    $payable['amount'] = $item['price_at_order'];
    $payable['payment_status_id'] = DB::table('payment_statuses')->where('name', 'UNPAID')->value('id');
    $payable['service_order_item_id'] = $itemId;
    $payable['prescription_id'] = null;
    $payable['paid_at'] = null;
    $payableId = DB::table('payables')->insertGetId($payable);
    echo json_encode(['id' => $payableId, 'code' => $payable['payable_code'], 'visit' => $visitId, 'appointment' => $appointmentId,
        'admin_phone' => App\Models\User::where('business_code', 'QT000001')->value('phone'),
        'cashier_phone' => App\Models\User::where('business_code', 'NV000004')->value('phone'),
        'admin_collector' => 1, 'cashier_collector' => 4,
        'admin_user' => App\Models\User::where('business_code', 'QT000001')->value('id'),
        'cashier_user' => App\Models\User::where('business_code', 'NV000004')->value('id')]);
} elseif (($argv[1] ?? '') === 'check') {
    $id = (int) ($argv[2] ?? 0);
    $payable = App\Models\Payable::with('paymentStatus')->findOrFail($id);
    $transactions = DB::table('payment_transactions')->where('payable_id', $id)->get();
    $audit = $transactions->count() === 1 ? DB::table('audit_logs')->where('entity_type', 'payment_transactions')->where('entity_id', $transactions[0]->id)->get() : collect();
    echo json_encode(['status' => $payable->paymentStatus?->name, 'transactions' => $transactions->count(),
        'result' => $transactions->first()?->transaction_result, 'collector' => $transactions->first()?->collected_by_employee_id,
        'audit' => $audit->count(), 'audit_user' => $audit->first()?->user_id,
        'queue' => DB::table('examination_room_queues')->where('medical_visit_id', $payable->medical_visit_id)->count()]);
}
