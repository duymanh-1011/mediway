<?php

namespace App\Support\Payments;

use App\Models\PaymentTransaction;

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
}
