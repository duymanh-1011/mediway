<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Services\PaymentCollectionService;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentCollectionController extends Controller
{
    private function cashier(Request $request)
    {
        $access = app(EmployeeAccess::class);
        abort_unless($request->user() && $access->allows($request->user(), 'payment.collect'), 403);
        return $access->employee($request->user());
    }

    public function create(Request $request, Payable $payable)
    {
        $employee = $this->cashier($request);
        $payable->load(['medicalVisit.patient', 'paymentStatus', 'transactions', 'serviceOrderItem.serviceStatus']);
        abort_unless(in_array($payable->paymentStatus?->name, ['UNPAID', 'PENDING'], true)
            && $payable->serviceOrderItem?->serviceStatus?->name !== 'CANCELLED'
            && (float) $payable->amount > 0 && ! $payable->transactions->contains('transaction_result', 'SUCCESS'), 404);
        $backQuery = $request->only(['q', 'payment_type', 'payment_status_id', 'payment_method', 'date_from', 'date_to', 'page']);
        return view('employee.payments.collect', compact('payable', 'employee', 'backQuery'));
    }

    public function store(Request $request, Payable $payable, PaymentCollectionService $service)
    {
        $employee = $this->cashier($request);
        $data = $request->validate([
            'collected_by_employee_id' => ['prohibited'],
            'amount' => ['prohibited'],
            'payment_method' => ['required', Rule::in(['CASH'])],
            'provider_transaction_code' => ['nullable', 'string', 'max:120'],
            'payment_content' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'confirm_payment' => ['accepted'],
        ]);
        $data['collected_by_employee_id'] = $employee->id;
        $service->collect($request, $payable, $data);
        $backQuery = $request->query->all();
        return redirect()->route('employee.payments.show', array_merge(['payable' => $payable->id], $backQuery))
            ->with('success', 'Đã ghi nhận thanh toán thành công.');
    }
}
