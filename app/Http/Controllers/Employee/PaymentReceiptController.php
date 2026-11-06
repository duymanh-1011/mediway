<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Payments\ReceiptDataBuilder;
use App\Support\Payments\ReceiptTypeResolver;

class PaymentReceiptController extends Controller
{
    /**
     * F08: Biên lai thanh toán tiền mặt.
     *
     * Chỉ đọc giao dịch đã thành công.
     * Không cập nhật Payable hoặc PaymentTransaction.
     */
    public function show(
        Request $request,
        PaymentTransaction $transaction
    ): View {
        $user = $request->user();
        $access = app(EmployeeAccess::class);

        // Không tin vào việc ẩn nút trên giao diện.
        abort_unless(
            $user
                && $access->allows($user, 'payment.view'),
            403,
            'Bạn không có quyền xem biên lai thanh toán.'
        );

        $employee = $access->employee($user);

        // Lần triển khai này chỉ hỗ trợ tiền mặt.
        // Giao dịch chưa thành công không có biên lai thu tiền.
        abort_unless(
            $transaction->transaction_result === 'SUCCESS'
                && $transaction->payment_method === 'CASH',
            404,
            'Không tìm thấy biên lai hợp lệ.'
        );

        // Chỉ đọc những quan hệ đã được khai báo trong model.
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

        return view('employee.payments.receipt', [
            'employee' => $employee,
            'transaction' => $transaction,
            'payable' => $payable,
            'visit' => $payable->medicalVisit,
            'patient' => $payable->medicalVisit?->patient,
            'collector' => $transaction->collector,
            'receiptType' => $receiptType,
            ...$receiptData,
        ]);
    }
}
