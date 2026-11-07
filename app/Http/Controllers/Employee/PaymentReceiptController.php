<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        return view('employee.payments.receipt', [
            ...app(\App\Support\Payments\ReceiptViewData::class)->build($transaction),
            'employee' => $employee,
        ]);
    }
}
