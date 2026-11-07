<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') {
    throw new RuntimeException('Unsafe database');
}
[$script, $payableId, $employeeId, $startAt] = $argv;
$employee = App\Models\Employee::findOrFail($employeeId);
Illuminate\Support\Facades\Auth::login($employee->user);
$request = Illuminate\Http\Request::create('/test', 'POST');
$payable = App\Models\Payable::findOrFail($payableId);
while (microtime(true) < (float) $startAt) { usleep(1000); }
try {
    $transaction = app(App\Services\PaymentCollectionService::class)->collect($request, $payable, [
        'payment_method' => 'CASH', 'collected_by_employee_id' => (int) $employeeId,
    ]);
    echo json_encode(['status' => 'success', 'id' => $transaction->id, 'code' => $transaction->transaction_code, 'collector' => $transaction->collected_by_employee_id]);
} catch (Illuminate\Validation\ValidationException $e) {
    echo json_encode(['status' => 'rejected', 'errors' => $e->errors()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit(1);
}
