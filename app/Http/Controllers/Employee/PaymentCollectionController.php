<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\QrPayment;
use App\Services\AppointmentPaymentDateGuard;
use App\Services\PaymentCollectionService;
use App\Services\QrPaymentService;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentCollectionController extends Controller
{
    private function cashier(Request $request)
    {
        $access = app(EmployeeAccess::class);

        abort_unless(
            $request->user()
                && $access->allows(
                    $request->user(),
                    'payment.collect',
                    $request->session()->get('employee_workspace_mode')
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

        $isService = $payable->payment_type === 'SERVICE_FEE';
        $payables = $isService
            ? Payable::query()->outstandingServiceFeesForVisit($payable->medicalVisit)
                ->with('serviceOrderItem.medicalService')->orderBy('id')->get()
            : collect([$payable]);

        $activeQr = QrPayment::supports($payable)
            ? QrPayment::blocking()->where('medical_visit_id', $payable->medical_visit_id)->with('items')->first()
            : null;
        $reservedIds = $activeQr?->items->pluck('payable_id')->map(fn ($id) => (int) $id)->all() ?? [];
        $qrAvailable = QrPayment::supports($payable) && ! $activeQr && QrPaymentService::driver() !== null;

        return response()->view('employee.payments.collect', compact(
            'payable', 'payables', 'employee', 'backQuery', 'isService', 'activeQr', 'reservedIds', 'qrAvailable'
        ))->header('Cache-Control', 'private, no-store');
    }

    public function store(
        Request $request,
        Payable $payable,
        PaymentCollectionService $service,
        AppointmentPaymentDateGuard $dateGuard,
        QrPaymentService $qrService
    ) {
        $employee = $this->cashier($request);
        $methods = ['CASH'];
        if (QrPayment::supports($payable) && QrPaymentService::driver()) {
            $methods[] = 'QR';
        }

        $data = $request->validate(
            [
                'collected_by_employee_id' => ['prohibited'],
                'amount' => ['prohibited'],
                'payment_method' => [
                    'required',
                    Rule::in($methods),
                ],
                'payable_ids' => [Rule::requiredIf($request->input('payment_method') === 'QR' || $request->has('selection_submitted')), 'array', 'min:1', 'max:100'],
                'payable_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
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
                'confirm_payment' => ['exclude_unless:payment_method,CASH', 'accepted'],
            ],
            [
                'collected_by_employee_id.prohibited' =>
                'Không được tự thay đổi người thu tiền.',

                'amount.prohibited' =>
                'Không được tự thay đổi số tiền.',

                'payment_method.required' =>
                'Vui lòng chọn phương thức thanh toán.',

                'payment_method.in' =>
                'Phương thức thanh toán này chưa khả dụng cho khoản thu.',

                'payable_ids.required' => 'Vui lòng chọn ít nhất một khoản thu.',
                'payable_ids.min' => 'Vui lòng chọn ít nhất một khoản thu.',
                'payable_ids.max' => 'Vui lòng chọn tối đa 100 khoản thu mỗi lần thanh toán.',

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
        $parameters = ['payable' => $payable->id, ...$backQuery];
        $ids = array_map('intval', $data['payable_ids'] ?? [$payable->id]);
        $isService = $payable->payment_type === 'SERVICE_FEE';

        try {
            if (! $isService && $ids !== [(int) $payable->id]) {
                throw ValidationException::withMessages(['payable_ids' => 'Vui lòng thanh toán riêng khoản thu này.']);
            }

            if ($data['payment_method'] === 'QR') {
                if ($isService && Payable::query()->where('medical_visit_id', $payable->medical_visit_id)
                    ->where('payment_type', 'SERVICE_FEE')->whereKey($ids)->count() !== count($ids)) {
                    throw ValidationException::withMessages(['payable_ids' => 'Chọn các khoản phí dịch vụ trong cùng lượt khám.']);
                }

                return to_route('employee.payments.qr.show', $qrService->create($payable->medicalVisit, $employee, $ids));
            }

            $transaction = $isService && isset($data['payable_ids'])
                ? $service->collectServiceFees($request, $payable->medicalVisit, $ids, $data)
                : $service->collect($request, $payable, $data);
        } catch (ValidationException $exception) {
            if ($request->has('payable_ids')) {
                return to_route('employee.payments.collect.create', $parameters)->withErrors($exception->errors())->withInput();
            }

            return to_route('employee.payments.show', $parameters)
                ->withErrors($exception->errors());
        }

        $response = $transaction->cash_payment_batch_id
            ? to_route('employee.payments.receipt', $transaction)
            : to_route('employee.payments.show', ['payable' => $transaction->payable_id, ...$backQuery]);

        return $response->with('success', 'Đã ghi nhận thanh toán tiền mặt thành công.');
    }
}
