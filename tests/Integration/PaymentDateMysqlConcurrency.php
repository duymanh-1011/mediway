<?php

// Standalone test only: creates synthetic records in the explicitly named F08 test database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
});

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

if (DB::getDriverName() !== 'mysql' || DB::getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') {
    throw new RuntimeException('Refusing to run outside the isolated F08 test database.');
}
foreach ([1, 4] as $id) {
    if (! App\Models\Employee::activeCashiers()->whereKey($id)->exists()) throw new RuntimeException('Two test cashiers required.');
}
$tag = substr((string) Str::ulid(), -10);
$today = now('Asia/Ho_Chi_Minh')->toDateString();
$tomorrow = now('Asia/Ho_Chi_Minh')->addDay()->toDateString();
$fixture = function (string $suffix) use ($tag, $today): array {
    $appointment = (array) DB::table('appointments')->where('id', 4)->first();
    unset($appointment['id']);
    $appointment['appointment_code'] = 'F08'.$tag.$suffix;
    $appointment['checkin_token'] = 'F08-'.$tag.$suffix;
    $appointment['appointment_date'] = $today;
    $appointmentId = DB::table('appointments')->insertGetId($appointment);
    $visit = (array) DB::table('medical_visits')->where('id', 4)->first();
    unset($visit['id']);
    $visit['visit_code'] = 'V'.$tag.$suffix;
    $visit['appointment_id'] = $appointmentId;
    $visitId = DB::table('medical_visits')->insertGetId($visit);
    $item = (array) DB::table('service_order_items')->first();
    $order = (array) DB::table('service_orders')->where('id', $item['service_order_id'])->first();
    unset($order['id']);
    $order['service_order_code'] = 'F'.$tag.$suffix;
    $order['medical_visit_id'] = $visitId;
    $orderId = DB::table('service_orders')->insertGetId($order);
    unset($item['id']);
    $item['service_order_id'] = $orderId;
    $item['service_status_id'] = DB::table('service_statuses')->where('name', 'PENDING')->value('id') ?? $item['service_status_id'];
    $itemId = DB::table('service_order_items')->insertGetId($item);
    $payable = (array) DB::table('payables')->where('id', 12)->first();
    unset($payable['id']);
    $payable['payable_code'] = 'P'.$tag.$suffix;
    $payable['medical_visit_id'] = $visitId;
    $payable['payment_status_id'] = DB::table('payment_statuses')->where('name', 'UNPAID')->value('id');
    $payable['payment_type'] = 'SERVICE_FEE';
    $payable['amount'] = $item['price_at_order'];
    $payable['service_order_item_id'] = $itemId;
    $payable['prescription_id'] = null;
    $payable['paid_at'] = null;
    return [$appointmentId, $visitId, DB::table('payables')->insertGetId($payable)];
};
$worker = function (int $payable, int $employee, float $at): Process {
    return new Process([PHP_BINARY, 'tests/Support/f08_mysql_worker.php', (string) $payable, (string) $employee, (string) $at], base_path(), null, null, 30);
};
$decode = function (Process $process): array {
    if (! $process->isSuccessful()) throw new RuntimeException('Worker failure: '.$process->getErrorOutput().' '.$process->getOutput());
    return json_decode(trim($process->getOutput()), true, 512, JSON_THROW_ON_ERROR);
};
$assert = function (bool $ok, string $message): void { if (! $ok) throw new RuntimeException($message); };

[$appointment, $visit, $payable] = $fixture('A');
$at = microtime(true) + 2;
$a = $worker($payable, 1, $at); $b = $worker($payable, 4, $at);
$a->start(); $b->start(); $a->wait(); $b->wait();
$out = [$decode($a), $decode($b)];
$states = array_column($out, 'status'); sort($states);
$assert($states === ['rejected', 'success'], 'Expected exactly one success and one rejection');
$success = DB::table('payment_transactions')->where('payable_id', $payable)->where('transaction_result', 'SUCCESS')->get();
$assert($success->count() === 1 && (int) $success[0]->collected_by_employee_id === (int) collect($out)->firstWhere('status', 'success')['collector'], 'Wrong transaction or collector');
$assert(DB::table('payables')->where('id', $payable)->value('payment_status_id') === DB::table('payment_statuses')->where('name', 'PAID')->value('id'), 'Incorrect status');
echo "Two cashiers: one success, one rejection, correct collector and PAID.\n";

[$appointment, $visit, $payable] = $fixture('B');
$auditBefore = DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count();
DB::beginTransaction();
DB::table('appointments')->where('id', $appointment)->lockForUpdate()->first();
$process = $worker($payable, 4, microtime(true) + 1); $process->start();
usleep(2000000);
DB::table('appointments')->where('id', $appointment)->update(['appointment_date' => $tomorrow]);
DB::commit();
$process->wait(); $out = $decode($process);
$assert($out['status'] === 'rejected' && isset($out['errors']['payment_date']), 'Payment should reject changed date');
$assert(DB::table('payment_transactions')->where('payable_id', $payable)->count() === 0, 'Unexpected transaction');
$assert(DB::table('examination_room_queues')->where('medical_visit_id', $visit)->count() === 0, 'Unexpected queue');
$assert(DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count() === $auditBefore, 'Unexpected payment audit');
$assert(DB::table('payables')->where('id', $payable)->value('payment_status_id') === DB::table('payment_statuses')->where('name', 'UNPAID')->value('id'), 'Unexpected status change');
echo "Locked date change: rejected after commit, no transaction, queue, audit or status change.\n";

[$appointment, $visit, $payable] = $fixture('C');
$slotId = DB::table('appointments')->where('id', $appointment)->value('appointment_time_slot_id');
$slot = (array) DB::table('appointment_time_slots')->where('id', $slotId)->first();
$schedule = (array) DB::table('doctor_schedules')->where('id', $slot['doctor_schedule_id'])->first();
unset($schedule['id']);
$schedule['work_date'] = $today;
$scheduleId = DB::table('doctor_schedules')->insertGetId($schedule);
unset($slot['id']);
$slot['doctor_schedule_id'] = $scheduleId;
$slotId = DB::table('appointment_time_slots')->insertGetId($slot);
DB::table('appointments')->where('id', $appointment)->update(['appointment_time_slot_id' => $slotId]);
$examTemplate = DB::table('payables')->where('payment_type', 'EXAMINATION_FEE')->first();
if (! $examTemplate) throw new RuntimeException('Missing examination fee template');
DB::table('payables')->where('id', $payable)->update([
    'payment_type' => $examTemplate->payment_type,
    'description' => $examTemplate->description,
    'amount' => $examTemplate->amount,
]);
DB::table('medical_visits')->where('id', $visit)->update(['visit_status_id' => DB::table('visit_statuses')->where('name', 'WAITING')->value('id')]);
$extras = [];
foreach (range(1, 12) as $i) $extras[] = $fixture('X'.$i)[0];
$marker = storage_path('logs/f08-deadlock-'.$tag);
$blocker = new Process([PHP_BINARY, 'tests/Support/f08_mysql_deadlock_blocker.php', (string) $appointment, (string) $visit, $marker, implode(',', $extras)], base_path(), null, null, 30);
$blocker->start();
$deadline = microtime(true) + 10;
while (! file_exists($marker.'.ready') && $blocker->isRunning() && microtime(true) < $deadline) usleep(10000);
$assert(file_exists($marker.'.ready'), 'Blocker did not acquire appointment lock: '.$blocker->getErrorOutput());
$payment = new Process([PHP_BINARY, 'tests/Support/f08_mysql_worker.php', (string) $payable, '4', (string) microtime(true), $marker], base_path(), null, null, 30);
$payment->start(); $blocker->wait(); $payment->wait();
$out = $decode($payment);
$assert($blocker->isSuccessful(), 'Blocker failed: '.$blocker->getErrorOutput().'; payment: '.json_encode($out));
$assert($out['status'] === 'success', 'Payment failed after deadlock: '.json_encode($out));
$attempts = (int) file_get_contents($marker.'.attempts');
$assert($attempts === 2, 'Did not observe one payment deadlock and retry; blocker outcome: '.$blocker->getOutput().', attempts: '.$attempts);
$transactions = DB::table('payment_transactions')->where('payable_id', $payable)->get();
$assert($transactions->count() === 1 && $transactions[0]->transaction_result === 'SUCCESS', 'Duplicate or orphan transaction');
$assert(DB::table('audit_logs')->where('entity_type', 'payment_transactions')->where('entity_id', $transactions[0]->id)->count() === 1, 'Wrong payment audit count');
$assert(DB::table('examination_room_queues')->where('medical_visit_id', $visit)->where('queue_type', 'INITIAL_EXAM')->count() === 1, 'Wrong queue count');
$assert(DB::table('payables')->where('id', $payable)->value('payment_status_id') === DB::table('payment_statuses')->where('name', 'PAID')->value('id'), 'Wrong status after retry');
echo "Controlled MySQL deadlock: payment retried once, one success, queue and audit.\n";

[$appointment, $visit, $payable] = $fixture('E');
$auditBefore = DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count();
DB::beginTransaction();
DB::table('appointments')->where('id', $appointment)->lockForUpdate()->first();
try {
    $payment = new Process([PHP_BINARY, 'tests/Support/f08_mysql_worker.php', (string) $payable, '4', (string) microtime(true), 'exhaustion'], base_path(), null, null, 20);
    $payment->run();
    $out = $decode($payment);
    $assert($out['status'] === 'rejected' && isset($out['errors']['payment_method']) && $out['attempts'] === 3, 'Expected three lock timeouts and a user-facing error: '.json_encode($out));
    $assert($out['transaction_level'] === 0 && $out['connection_usable'], 'Worker left a transaction open');
} finally {
    DB::rollBack();
}
$assert(DB::table('payment_transactions')->where('payable_id', $payable)->count() === 0, 'Unexpected transaction after retry exhaustion');
$assert(DB::table('payables')->where('id', $payable)->value('payment_status_id') === DB::table('payment_statuses')->where('name', 'UNPAID')->value('id'), 'Unexpected payment status');
$assert(DB::table('examination_room_queues')->where('medical_visit_id', $visit)->count() === 0, 'Unexpected queue');
$assert(DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count() === $auditBefore, 'Unexpected audit');
echo "Retry exhaustion: three lock timeouts, no financial side effects, transaction closed.\n";
