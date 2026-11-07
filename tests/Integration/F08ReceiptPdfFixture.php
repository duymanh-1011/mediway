<?php

// Generate a printable synthetic receipt from the isolated F08 database only.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || $db->getDatabaseName() !== 'mediway_f08_isolated_20261105_5978f4ba') {
    throw new RuntimeException('Unsafe database');
}
$type = null;
foreach ($argv as $argument) if (str_starts_with($argument, '--type=')) $type = substr($argument, 7);
if ($type !== null && ! in_array($type, ['exam', 'service', 'prescription'], true)) throw new RuntimeException('Invalid receipt type');
$query = App\Models\PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')
    ->whereHas('payable.medicalVisit.patient')->with(['collector', 'payable.medicalVisit.patient']);
if ($type === 'exam') $query->whereHas('payable', fn ($q) => $q->where('payment_type', 'EXAMINATION_FEE'));
if ($type === 'service') $query->whereHas('payable.serviceOrderItem');
$transaction = $query->latest('id')->firstOrFail();
$payable = $transaction->payable;
if ($type === 'exam' && in_array('--exam-300000', $argv, true)) {
    // Synthetic view-only amounts for the A4 fixture; no database writes.
    $payable->amount = 300000;
    $transaction->amount = 300000;
}
if ($type === 'prescription') {
    // The isolated database has no paid medicine transaction; use real test prescription items with an in-memory payable only.
    $prescription = App\Models\Prescription::with('medicalVisit.patient')->whereHas('items')->whereHas('medicalVisit.patient')->firstOrFail();
    $itemCount = null;
    foreach ($argv as $argument) if (str_starts_with($argument, '--items=')) $itemCount = (int) substr($argument, 8);
    if ($itemCount !== null) {
        if (! in_array($itemCount, [1, 3, 10], true)) throw new RuntimeException('Invalid fixture item count');
        $baseItems = $prescription->items->values();
        $prescription->setRelation('items', collect(range(0, $itemCount - 1))->map(function ($i) use ($baseItems) {
            $item = clone $baseItems[$i % $baseItems->count()];
            $item->setRelation('medicine', clone $item->medicine);
            if (in_array('--long', $_SERVER['argv'], true)) {
                $item->medicine->name .= ' '.str_repeat('Thuốc thử nghiệm tên dài ', 4);
                $item->usage_instructions = str_repeat('Dùng theo hướng dẫn bác sĩ sau bữa ăn. ', 4);
            }
            return $item;
        }));
    }
    $payable = $payable->replicate();
    $payable->id = $transaction->payable_id;
    $payable->payment_type = 'MEDICINE_FEE';
    $payable->medical_visit_id = $prescription->medical_visit_id;
    $payable->prescription_id = $prescription->id;
    $payable->setRelation('medicalVisit', $prescription->medicalVisit);
    $payable->description = 'Tiền thuốc theo đơn thử nghiệm';
    $total = 0;
    foreach ($prescription->items as $index => $item) {
        $price = ($index + 1) * 10000;
        $item->quantity = $index + 1;
        $item->unit_price = $price;
        $item->line_total = ($index + 1) * $price;
        $total += ($index + 1) * $price;
    }
    $payable->setRelation('prescription', $prescription);
    $payable->amount = $total;
    $transaction->amount = $total;
}
if (in_array('--long', $argv, true)) {
    // Only mutate in-memory view data; never change the isolated database for this print stress case.
    $transaction->transaction_code = str_repeat('GD-THU-NGHIEM-', 8);
    $payable->medicalVisit->patient->full_name = str_repeat('Nguyễn Thị Ánh Tuyết ', 6);
    $payable->description = 'DAU-NOI-DUNG '.str_repeat('Nội dung khám và điều trị tiếng Việt có dấu. ', 90).' CUOI-NOI-DUNG';
}
$receiptType = app(App\Support\Payments\ReceiptTypeResolver::class)->resolve($payable);
if ($receiptType === null) throw new RuntimeException('Unsupported receipt type');
$receiptData = app(App\Support\Payments\ReceiptDataBuilder::class)->build($payable, $receiptType);
if ($receiptType === 'prescription' && in_array('--long', $argv, true)) {
    $receiptData['prescription']->prescription_code = str_repeat('DT-THU-NGHIEM-', 4);
    $receiptData['prescription']->general_instructions = "Dòng lời dặn thứ nhất của bác sĩ.\n".str_repeat('Dặn dò thử nghiệm dài theo đơn thuốc. ', 5);
    if ($payable->medicalVisit->patient->detail) {
        $payable->medicalVisit->patient->detail->current_address = str_repeat('Địa chỉ thử nghiệm có tên đường dài, ', 4);
    }
    foreach ($payable->medicalVisit->diagnoses as $diagnosis) {
        $diagnosis->diagnosis_description = str_repeat('Chẩn đoán thử nghiệm dài, ', 4);
    }
}
$html = view('employee.payments.receipt', [
    'transaction' => $transaction, 'payable' => $payable,
    'visit' => $payable->medicalVisit, 'patient' => $payable->medicalVisit->patient,
    'collector' => $transaction->collector, 'employee' => $transaction->collector,
    'receiptType' => $receiptType, ...$receiptData,
])->render();
$path = storage_path('app/f08-receipt-a4-'.Illuminate\Support\Str::ulid().'.html');
file_put_contents($path, $html);
echo $path;
