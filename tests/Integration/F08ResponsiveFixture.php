<?php

// Synthetic, additive fixtures only; this must never run on the real Mediway database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') throw new RuntimeException('Unsafe database');
$tag = substr((string) Str::ulid(), -9);
$ids = [];
foreach (['today' => ['UNPAID', 0], 'pending' => ['PENDING', 0], 'cancelled' => ['CANCELLED', 0], 'future' => ['UNPAID', 1], 'past' => ['UNPAID', -1], 'long' => ['UNPAID', 0]] as $name => [$status, $offset]) {
    $appointment = (array) DB::table('appointments')->where('id', 4)->first();
    unset($appointment['id']);
    $appointment['appointment_code'] = 'UI'.$tag.$name;
    $appointment['checkin_token'] = 'UI-'.$tag.$name;
    $appointment['appointment_date'] = now('Asia/Ho_Chi_Minh')->addDays($offset)->toDateString();
    $appointmentId = DB::table('appointments')->insertGetId($appointment);
    $visit = (array) DB::table('medical_visits')->where('id', 4)->first();
    unset($visit['id']);
    $visit['visit_code'] = 'UI'.$tag.$name;
    $visit['appointment_id'] = $appointmentId;
    $visitId = DB::table('medical_visits')->insertGetId($visit);
    $payable = (array) DB::table('payables')->where('id', 12)->first();
    unset($payable['id']);
    $payable['payable_code'] = 'UI'.$tag.$name;
    $payable['medical_visit_id'] = $visitId;
    $payable['payment_type'] = 'SERVICE_FEE';
    $payable['payment_status_id'] = DB::table('payment_statuses')->where('name', $status)->value('id');
    $payable['service_order_item_id'] = null;
    $payable['prescription_id'] = null;
    $payable['paid_at'] = null;
    if ($name === 'long') $payable['description'] = str_repeat('Nội dung dịch vụ thử nghiệm dài có dấu. ', 5);
    $ids[$name] = DB::table('payables')->insertGetId($payable);
}
$receipt = App\Models\PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereHas('payable')->latest('id')->firstOrFail();
$ids['paid'] = $receipt->payable_id;
$ids['receipt'] = $receipt->id;
echo json_encode($ids);
