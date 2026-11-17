<?php

namespace App\Support\Payments;

use App\Models\PaymentTransaction;
use App\Models\QrPayment;

/** Shared receipt data and consistency checks; caller enforces access. */
class ReceiptViewData
{
    public function build(PaymentTransaction $transaction): array
    {
        abort_unless($transaction->transaction_result === 'SUCCESS' && $transaction->payment_method === 'CASH', 404);
        $transaction->load([
            'collector',
            'payable.paymentStatus',
            'payable.medicalVisit.patient',
        ]);

        $payable = $transaction->payable;

        abort_unless($payable !== null, 404);
        $receiptType = app(ReceiptTypeResolver::class)->resolve($payable);
        abort_unless($receiptType !== null, 404);
        $receiptData = app(ReceiptDataBuilder::class)->build($payable, $receiptType);

        // The exam receipt has one fee total and spells out the transaction amount.
        // Refuse an inconsistent record rather than issue an ambiguous receipt.
        if ($receiptType === 'exam') {
            abort_unless(
                (int) round((float) $payable->amount * 100) === (int) round((float) $transaction->amount * 100),
                422,
                'Số tiền giao dịch không khớp phí khám; không thể lập hóa đơn.'
            );
        }

        if ($receiptType === 'service') {
            abort_unless(
                (int) round((float) $receiptData['serviceItem']->price_at_order * 100) === (int) round((float) $transaction->amount * 100),
                422,
                'Số tiền giao dịch không khớp giá dịch vụ; không thể lập hóa đơn.'
            );
        }

        if ($receiptType === 'prescription') {
            $totalCents = 0;
            foreach ($receiptData['prescription']->items as $item) {
                $lineCents = (int) round((float) $item->line_total * 100);
                abort_unless($lineCents === (int) round((float) $item->quantity * (float) $item->unit_price * 100), 422, 'Thành tiền thuốc không khớp số lượng và đơn giá.');
                $totalCents += $lineCents;
            }
            abort_unless($totalCents === (int) round((float) $transaction->amount * 100), 422, 'Tổng tiền thuốc không khớp giao dịch; không thể lập hóa đơn.');
        }

        // Nếu hệ thống có phân quyền theo cơ sở/phòng,
        // cần bổ sung kiểm tra phạm vi của payable tại đây
        // theo policy đã được nhóm thống nhất.
        // Không tự suy đoán phạm vi từ dữ liệu client.

        return [
            'transaction' => $transaction,
            'payable' => $payable,
            'visit' => $payable->medicalVisit,
            'patient' => $payable->medicalVisit?->patient,
            'collector' => $transaction->collector,
            'receiptType' => $receiptType,
            ...$receiptData,
        ];
    }

    public function buildForQr(QrPayment $payment): array
    {
        abort_unless($payment->status === 'PAID', 404, 'Không tìm thấy biên lai hợp lệ.');
        $payment->load([
            'medicalVisit.patient.detail', 'medicalVisit.patient.user', 'medicalVisit.examinationRoom',
            'items' => fn ($items) => $items->orderBy('id'),
            'items.payable.serviceOrderItem.medicalService', 'creator',
        ]);
        $visit = $payment->medicalVisit;
        abort_unless($visit?->patient && $payment->paid_at && $payment->items->isNotEmpty(), 422, 'Thiếu thông tin để lập phiếu thu QR.');

        $allocations = PaymentTransaction::where('qr_payment_id', $payment->id)
            ->where('payment_method', 'QR')->where('transaction_result', 'SUCCESS')->get()->keyBy('payable_id');
        $total = $payment->items->sum('amount');
        abort_unless($total === $payment->amount && $allocations->count() === $payment->items->count(), 422, 'Tổng tiền phiếu thu QR không khớp giao dịch.');

        $firstPayable = $payment->items->first()->payable;
        $isExam = $firstPayable?->payment_type === 'EXAMINATION_FEE';
        abort_if($isExam && $payment->items->count() !== 1, 422, 'Phiếu thu QR phí khám phải có một khoản thu.');
        $expectedType = $isExam ? 'EXAMINATION_FEE' : 'SERVICE_FEE';
        $lines = $payment->items->map(function ($item) use ($payment, $allocations, $isExam, $expectedType): array {
            $payable = $item->payable;
            $allocation = $allocations->get($item->payable_id);
            abort_unless($payable && (int) $payable->medical_visit_id === (int) $payment->medical_visit_id
                && $payable->payment_type === $expectedType
                && $allocation?->amount === number_format($item->amount, 2, '.', ''),
                422, 'Khoản thu QR không khớp với giao dịch.');

            return [
                $isExam ? 'Phí khám bệnh' : $item->description,
                $isExam ? 'Lần' : $payable->serviceOrderItem?->medicalService?->unit,
                $isExam ? 1 : null,
                $item->amount,
                $item->amount,
                $payable->payable_code,
            ];
        });

        return [
            'receiptType' => $isExam ? 'exam' : 'service',
            'receiptNumber' => (string) $payment->order_code,
            'receiptAmount' => $payment->amount,
            'receiptPaidAt' => $payment->paid_at,
            'receiptLines' => $lines,
            'receiptIdentifiers' => [
                'Phương thức:' => 'Chuyển khoản QR',
                'Mã tham chiếu:' => $payment->provider_reference,
            ],
            'visit' => $visit,
            'patient' => $visit->patient,
            'collector' => $payment->creator,
        ];
    }

    public function buildForCashBatch(PaymentTransaction $transaction): array
    {
        $batch = $transaction->cashPaymentBatch;
        abort_unless($batch, 404);

        $transactions = $batch->transactions()->with('payable.serviceOrderItem.medicalService')->orderBy('id')->get();
        $first = $transactions->first();
        $lines = $transactions->map(function (PaymentTransaction $line) use ($batch, $first): array {
            $payable = $line->payable;
            $item = $payable?->serviceOrderItem;
            abort_unless($line->transaction_result === 'SUCCESS' && $line->payment_method === 'CASH'
                && $line->collected_by_employee_id === $first->collected_by_employee_id
                && $payable?->payment_type === 'SERVICE_FEE' && (int) $payable->medical_visit_id === (int) $batch->medical_visit_id
                && $item?->medicalService && $item->price_at_order === $line->amount,
                422, 'Khoản thu không khớp với giao dịch trong biên lai.');

            return [$item->medicalService->name, $item->medicalService->unit, 1, $line->amount, $line->amount, $payable->payable_code];
        });

        return [
            ...$this->build($first),
            'receiptNumber' => $batch->receipt_code,
            'receiptAmount' => $transactions->sum('amount'),
            'receiptPaidAt' => $first->paid_at,
            'receiptLines' => $lines,
            'receiptIdentifiers' => array_filter([
                'Phương thức:' => 'Tiền mặt',
                'Mã tham chiếu:' => $first->provider_transaction_code,
            ]),
        ];
    }
}
