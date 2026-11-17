<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MedicalVisit;
use App\Models\QrPayment;
use App\Services\QrPaymentService;
use App\Support\EmployeeAccess;
use App\Support\Payments\ReceiptViewData;
use Illuminate\Http\Request;

class QrPaymentController extends Controller
{
    public function create(Request $request, MedicalVisit $visit, QrPaymentService $service)
    {
        $data = $request->validate(['exam_fee' => ['nullable', 'integer', 'min:1']]);
        $examFee = isset($data['exam_fee']) ? (int) $data['exam_fee'] : null;
        $active = QrPayment::blocking()->where('medical_visit_id', $visit->id)->latest('id')->first();
        if ($active) {
            $response = to_route('employee.payments.qr.show', $active);
            $matches = $examFee === null
                ? $active->items()->whereHas('payable', fn ($query) => $query->where('payment_type', 'SERVICE_FEE'))->exists()
                : $active->items()->where('payable_id', $examFee)->exists();
            if (! $matches) {
                $response->withErrors(['qr' => 'Đang có mã QR cho khoản thu khác trong lượt khám này. Hãy xử lý mã đang mở trước.']);
            }

            return $response;
        }
        abort_unless(QrPaymentService::driver(), 404);
        $payable = $service->candidates($visit, $examFee)->first();

        return $payable
            ? to_route('employee.payments.collect.create', ['payable' => $payable, 'method' => 'QR'])
            : to_route('employee.payments.index')->withErrors(['qr' => 'Không có khoản thu phù hợp để thanh toán.']);
    }

    public function store(Request $request, MedicalVisit $visit, QrPaymentService $service)
    {
        $data = $request->validate([
            'payable_ids' => ['required', 'array', 'min:1', 'max:100'],
            'payable_ids.*' => ['required', 'integer', 'distinct'],
            'amount' => ['prohibited'],
        ], ['payable_ids.required' => 'Chọn ít nhất một khoản thu để thanh toán.']);
        return to_route('employee.payments.qr.show', $service->create($visit, $this->employee($request), array_map('intval', $data['payable_ids'])));
    }

    public function show(Request $request, QrPayment $payment)
    {
        $payment->load(['medicalVisit.patient', 'creator', 'items.payable:id,medical_visit_id,payment_type,payable_code']);

        return response()->view('employee.payments.qr-show', [
            'payment' => $payment, 'employee' => $this->employee($request),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function status(QrPayment $payment)
    {
        return response()->json([
            'status' => $payment->status, 'label' => $payment->displayStatus(),
            'expired' => $payment->expires_at->isPast(), 'review' => (bool) $payment->review_reason,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function simulate(QrPayment $payment, QrPaymentService $service)
    {
        $service->simulate($payment);

        return to_route('employee.payments.qr.show', $payment);
    }

    public function verify(QrPayment $payment, QrPaymentService $service)
    {
        $service->refresh($payment);

        return to_route('employee.payments.qr.show', $payment);
    }

    public function cancel(QrPayment $payment, QrPaymentService $service)
    {
        $service->cancel($payment);

        $payable = $payment->items()->firstOrFail()->payable;

        return redirect(QrPaymentService::selectionUrl($payable));
    }

    public function receipt(QrPayment $payment, ReceiptViewData $receipt)
    {
        return response()->view('employee.payments.receipt', [
            ...$receipt->buildForQr($payment),
            'receiptBackUrl' => route('employee.payments.qr.show', $payment),
            'receiptBackLabel' => 'Quay lại phiếu thanh toán',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function webhook(Request $request, QrPaymentService $service)
    {
        abort_unless($request->isJson() && strlen($request->getContent()) <= 16384, 400);
        $payload = json_decode($request->getContent(), true);
        abort_unless(is_array($payload), 400);
        $service->verifyWebhook($payload);

        return response()->json(['received' => true]);
    }

    private function employee(Request $request): Employee
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        abort_unless($employee, 403);

        return $employee;
    }
}
