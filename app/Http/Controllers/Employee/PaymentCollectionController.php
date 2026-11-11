<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Services\AppointmentPaymentDateGuard;
use App\Services\PaymentCollectionService;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentCollectionController extends Controller
{
    private function cashier(Request $request)
    {
        $access = app(EmployeeAccess::class);

        abort_unless(
            $request->user()
                && $access->allows(
                    $request->user(),
                    'payment.collect'
                ),
            403,
            'Bạn không có quyền thu tiền.'
        );

        return $access->employee($request->user());
    }

    public function create(
        Request $request,
        Payable $payable,
        AppointmentPaymentDateGuard $dateGuard
    ) {
        $employee = $this->cashier($request);
        $backQuery = PayableController::navigationQuery($request);

        $payable->load([
            'medicalVisit.patient',
            'medicalVisit.appointment',
            'paymentStatus',
            'transactions',
            'serviceOrderItem.serviceStatus',
        ]);

        /*
         * Chặn mở form khi không đúng ngày hẹn.
         */
        if ($reason = $dateGuard->unavailableReason($payable)) {
            return redirect()->route('employee.payments.show', array_merge(['payable' => $payable->id], $backQuery))
                ->withErrors(['payment_date' => $reason]);
        }

        $status = $payable->paymentStatus?->name;

        $hasSuccessfulPayment = $payable->transactions
            ->contains('transaction_result', 'SUCCESS');

        $serviceCancelled =
            $payable->serviceOrderItem?->serviceStatus?->name
            === 'CANCELLED';

        abort_unless(
            in_array(
                $status,
                ['UNPAID', 'PENDING'],
                true
            )
                && ! $serviceCancelled
                && (float) $payable->amount > 0
                && ! $hasSuccessfulPayment,
            404,
            'Khoản thu không còn đủ điều kiện thanh toán.'
        );

        return view(
            'employee.payments.collect',
            compact(
                'payable',
                'employee',
                'backQuery'
            )
        );
    }

    public function store(
        Request $request,
        Payable $payable,
        PaymentCollectionService $service,
        AppointmentPaymentDateGuard $dateGuard
    ) {
        $employee = $this->cashier($request);

        $data = $request->validate(
            [
                'collected_by_employee_id' => ['prohibited'],
                'amount' => ['prohibited'],
                'payment_method' => [
                    'required',
                    Rule::in(['CASH']),
                ],
                'provider_transaction_code' => [
                    'nullable',
                    'string',
                    'max:120',
                ],
                'payment_content' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'notes' => [
                    'nullable',
                    'string',
                ],
                'confirm_payment' => ['accepted'],
            ],
            [
                'collected_by_employee_id.prohibited' =>
                'Không được tự thay đổi người thu tiền.',

                'amount.prohibited' =>
                'Không được tự thay đổi số tiền.',

                'payment_method.required' =>
                'Vui lòng chọn phương thức thanh toán.',

                'payment_method.in' =>
                'Hiện chỉ hỗ trợ thanh toán tiền mặt.',

                'provider_transaction_code.string' =>
                'Mã tham chiếu không hợp lệ.',

                'provider_transaction_code.max' =>
                'Mã tham chiếu tối đa 120 ký tự.',

                'payment_content.string' =>
                'Nội dung thanh toán không hợp lệ.',

                'payment_content.max' =>
                'Nội dung thanh toán tối đa 255 ký tự.',

                'notes.string' =>
                'Ghi chú không hợp lệ.',

                'confirm_payment.accepted' =>
                'Vui lòng xác nhận đã nhận đủ tiền mặt.',
            ]
        );

        /*
         * Kiểm tra sớm để trả thông báo rõ ràng.
         *
         * Service vẫn phải kiểm tra lại
         * trong transaction sau khi khóa dữ liệu.
         */
        $dateGuard->assertCanPay($payable);

        $data['collected_by_employee_id'] = $employee->id;

        $backQuery = PayableController::navigationQuery($request);

        try {
            $service->collect($request, $payable, $data);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()->route('employee.payments.show', array_merge(['payable' => $payable->id], $backQuery))
                ->withErrors($exception->errors());
        }

        return redirect()->route(
            'employee.payments.show',
            array_merge(
                ['payable' => $payable->id],
                $backQuery
            )
        )->with(
            'success',
            'Đã ghi nhận thanh toán tiền mặt thành công.'
        );
    }
}
