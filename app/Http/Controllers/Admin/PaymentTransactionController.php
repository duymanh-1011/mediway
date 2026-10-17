<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentTransactionRequest;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentTransactionController extends Controller
{
    /**
     * Danh sách giao dịch thanh toán.
     */
    public function index(Request $request)
    {
        $query = PaymentTransaction::query()
            ->leftJoin(
                'payables',
                'payables.id',
                '=',
                'payment_transactions.payable_id'
            )
            ->leftJoin(
                'medical_visits',
                'medical_visits.id',
                '=',
                'payables.medical_visit_id'
            )
            ->leftJoin(
                'employees',
                'employees.id',
                '=',
                'payment_transactions.collected_by_employee_id'
            )
            ->select([
                'payment_transactions.*',
                'payables.payable_code',
                'payables.payment_type',
                'medical_visits.visit_code',
                'employees.employee_code',
                'employees.full_name as collector_name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */
        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'payment_transactions.transaction_code',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'payables.payable_code',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'medical_visits.visit_code',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'payment_transactions.provider_transaction_code',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc phương thức
        |--------------------------------------------------------------------------
        */
        if ($request->filled('payment_method')) {
            $query->where(
                'payment_transactions.payment_method',
                $request->payment_method
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc kết quả
        |--------------------------------------------------------------------------
        */
        if ($request->filled('transaction_result')) {
            $query->where(
                'payment_transactions.transaction_result',
                $request->transaction_result
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc ngày bắt đầu giao dịch
        |--------------------------------------------------------------------------
        */
        if ($request->filled('date_from')) {
            $query->whereDate(
                'payment_transactions.started_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'payment_transactions.started_at',
                '<=',
                $request->date_to
            );
        }

        $paymentMethods = PaymentTransaction::query()
            ->whereNotNull('payment_method')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method');

        $transactionResults = PaymentTransaction::query()
            ->whereNotNull('transaction_result')
            ->distinct()
            ->orderBy('transaction_result')
            ->pluck('transaction_result');

        $transactions = $query
            ->orderByDesc('payment_transactions.started_at')
            ->orderByDesc('payment_transactions.id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.payment-transactions.index',
            compact(
                'transactions',
                'paymentMethods',
                'transactionResults'
            )
        );
    }

    /**
     * Form ghi nhận thanh toán.
     */
    public function create(Payable $payable)
    {
        $payable->load([
            'paymentStatus',
            'medicalVisit',
            'serviceOrderItem.medicalService',
            'serviceOrderItem.serviceOrder',
        ]);

        $currentStatus =
            $payable->paymentStatus?->name;

        /*
        |--------------------------------------------------------------------------
        | Khoản đã thanh toán
        |--------------------------------------------------------------------------
        */
        if ($currentStatus === 'PAID') {
            return redirect()
                ->route(
                    'admin.payables.show',
                    $payable->id
                )
                ->with(
                    'error',
                    'Khoản phải thanh toán này đã được thanh toán.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Khoản đã hủy
        |--------------------------------------------------------------------------
        */
        if ($currentStatus === 'CANCELLED') {
            return redirect()
                ->route(
                    'admin.payables.show',
                    $payable->id
                )
                ->with(
                    'error',
                    'Khoản phải thanh toán này đã bị hủy.'
                );
        }

        return view(
            'admin.payment-transactions.create',
            compact(
                'payable'
            )
        );
    }

    /**
     * Ghi nhận thanh toán thành công.
     */
    public function store(
        StorePaymentTransactionRequest $request,
        Payable $payable
    ) {
        $payable->load([
            'paymentStatus',
        ]);

        $currentStatus =
            $payable->paymentStatus?->name;

        /*
        |--------------------------------------------------------------------------
        | Không cho thu lại khoản đã thanh toán
        |--------------------------------------------------------------------------
        */
        if ($currentStatus === 'PAID') {
            return redirect()
                ->route(
                    'admin.payables.show',
                    $payable->id
                )
                ->with(
                    'error',
                    'Khoản phải thanh toán này đã được thanh toán.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Không cho thanh toán khoản đã hủy
        |--------------------------------------------------------------------------
        */
        if ($currentStatus === 'CANCELLED') {
            return redirect()
                ->route(
                    'admin.payables.show',
                    $payable->id
                )
                ->with(
                    'error',
                    'Khoản phải thanh toán này đã bị hủy.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Trạng thái PAID
        |--------------------------------------------------------------------------
        */
        $paidStatus = PaymentStatus::query()
            ->where(
                'name',
                'PAID'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'allows_service_execution',
                true
            )
            ->first();

        if (!$paidStatus) {
            throw ValidationException::withMessages([
                'payment_method' =>
                    'Không tìm thấy trạng thái PAID hợp lệ cho phép thực hiện dịch vụ.',
            ]);
        }

        $validated =
            $request->validated();

        $transaction =
            DB::transaction(
                function () use (
                    $request,
                    $payable,
                    $paidStatus,
                    $validated
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Khóa payable trong lúc thanh toán
                    |--------------------------------------------------------------------------
                    */
                    $lockedPayable =
                        Payable::query()
                            ->with('paymentStatus')
                            ->lockForUpdate()
                            ->findOrFail(
                                $payable->id
                            );

                    $lockedStatus =
                        $lockedPayable
                            ->paymentStatus
                            ?->name;

                    if ($lockedStatus === 'PAID') {
                        throw ValidationException::withMessages([
                            'payment_method' =>
                                'Khoản phải thanh toán đã được thanh toán bởi một giao dịch khác.',
                        ]);
                    }

                    if ($lockedStatus === 'CANCELLED') {
                        throw ValidationException::withMessages([
                            'payment_method' =>
                                'Khoản phải thanh toán đã bị hủy.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Sinh mã giao dịch GDxxxxxx
                    |--------------------------------------------------------------------------
                    */
                    $lastTransaction =
                        PaymentTransaction::query()
                            ->lockForUpdate()
                            ->orderByDesc('id')
                            ->first();

                    $nextNumber = 1;

                    if ($lastTransaction) {
                        $numericPart =
                            (int) preg_replace(
                                '/\D/',
                                '',
                                $lastTransaction
                                    ->transaction_code
                            );

                        $nextNumber =
                            $numericPart + 1;
                    }

                    do {
                        $transactionCode =
                            'GD'
                            . str_pad(
                                (string) $nextNumber,
                                6,
                                '0',
                                STR_PAD_LEFT
                            );

                        $exists =
                            PaymentTransaction::query()
                                ->where(
                                    'transaction_code',
                                    $transactionCode
                                )
                                ->exists();

                        $nextNumber++;
                    } while ($exists);

                    $now = now();

                    /*
                    |--------------------------------------------------------------------------
                    | Tạo giao dịch thành công
                    |--------------------------------------------------------------------------
                    */
                    $transaction =
                        PaymentTransaction::create([
                            'transaction_code' =>
                                $transactionCode,

                            'payable_id' =>
                                $lockedPayable->id,

                            /*
                            | Không nhận số tiền từ form.
                            | Luôn lấy số tiền của payable.
                            */
                            'amount' =>
                                $lockedPayable->amount,

                            'payment_method' =>
                                $validated[
                                    'payment_method'
                                ],

                            'transaction_result' =>
                                'SUCCESS',

                            'provider_transaction_code' =>
                                $validated[
                                    'provider_transaction_code'
                                ] ?? null,

                            'started_at' =>
                                $now,

                            'paid_at' =>
                                $now,

                            'confirmed_at' =>
                                $now,

                            /*
                            | Hiện chức năng này thao tác trong khu vực Admin.
                            | Chưa ánh xạ tài khoản Admin sang employee,
                            | nên không tự gán sai employee_id.
                            */
                            'collected_by_employee_id' =>
                                null,

                            'payment_content' =>
                                $validated[
                                    'payment_content'
                                ] ?? null,

                            'failure_reason' =>
                                null,

                            'notes' =>
                                $validated[
                                    'notes'
                                ] ?? null,
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Chuyển payable sang PAID
                    |--------------------------------------------------------------------------
                    */
                    $lockedPayable->update([
                        'payment_status_id' =>
                            $paidStatus->id,

                        'paid_at' =>
                            $now,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Audit Log
                    |--------------------------------------------------------------------------
                    */
                    $this->writeAuditLog(
                        $request,
                        $transaction,
                        $lockedPayable
                    );

                    return $transaction;
                }
            );

        return redirect()
            ->route(
                'admin.payment-transactions.show',
                $transaction->id
            )
            ->with(
                'success',
                'Thanh toán thành công. Khoản phải thanh toán đã chuyển sang PAID.'
            );
    }

    /**
     * Chi tiết giao dịch.
     */
    public function show(int $id)
    {
        $transaction =
            PaymentTransaction::query()
                ->with([
                    'payable.paymentStatus',
                ])
                ->findOrFail($id);

        $payable =
            $transaction->payable;

        /*
        |--------------------------------------------------------------------------
        | Lượt khám liên quan
        |--------------------------------------------------------------------------
        */
        $visit = null;

        if ($payable) {
            $visit =
                DB::table('medical_visits')
                    ->where(
                        'id',
                        $payable
                            ->medical_visit_id
                    )
                    ->select([
                        'id',
                        'visit_code',
                    ])
                    ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Nhân viên thu tiền
        |--------------------------------------------------------------------------
        */
        $collector = null;

        if (
            $transaction
                ->collected_by_employee_id
        ) {
            $collector =
                DB::table('employees')
                    ->where(
                        'id',
                        $transaction
                            ->collected_by_employee_id
                    )
                    ->select([
                        'id',
                        'employee_code',
                        'full_name',
                    ])
                    ->first();
        }

        return view(
            'admin.payment-transactions.show',
            compact(
                'transaction',
                'payable',
                'visit',
                'collector'
            )
        );
    }

    /**
     * Ghi Audit Log cho giao dịch thanh toán.
     */
    private function writeAuditLog(
        Request $request,
        PaymentTransaction $transaction,
        Payable $payable
    ): void {
        $roleNameSnapshot = null;

        if (Auth::check()) {
            $roleNameSnapshot =
                DB::table('users')
                    ->join(
                        'roles',
                        'roles.id',
                        '=',
                        'users.role_id'
                    )
                    ->where(
                        'users.id',
                        Auth::id()
                    )
                    ->value(
                        'roles.name'
                    );
        }

        $newValues = [
            'transaction_code' =>
                $transaction
                    ->transaction_code,

            'payable_id' =>
                $transaction
                    ->payable_id,

            'payable_code' =>
                $payable
                    ->payable_code,

            'amount' =>
                $transaction
                    ->amount,

            'payment_method' =>
                $transaction
                    ->payment_method,

            'transaction_result' =>
                $transaction
                    ->transaction_result,

            'provider_transaction_code' =>
                $transaction
                    ->provider_transaction_code,

            'paid_at' =>
                $transaction
                    ->paid_at
                    ?->format(
                        'Y-m-d H:i:s'
                    ),
        ];

        DB::table('audit_logs')
            ->insert([
                'user_id' =>
                    Auth::id(),

                'role_name_snapshot' =>
                    $roleNameSnapshot,

                'action' =>
                    'CREATE',

                'entity_type' =>
                    'payment_transactions',

                'entity_id' =>
                    $transaction->id,

                'entity_business_code' =>
                    $transaction
                        ->transaction_code,

                'old_values' =>
                    json_encode(
                        [],
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                    ),

                'new_values' =>
                    json_encode(
                        $newValues,
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                    ),

                'description' =>
                    'Ghi nhận thanh toán '
                    . $transaction
                        ->transaction_code
                    . ' cho khoản '
                    . $payable
                        ->payable_code
                    . '.',

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request
                        ->userAgent(),

                'performed_at' =>
                    now(),

                'created_at' =>
                    now(),
            ]);
    }
}