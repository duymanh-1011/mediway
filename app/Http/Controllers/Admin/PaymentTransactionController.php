<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentTransactionController extends Controller
{
    /**
     * Danh sách giao dịch thanh toán.
     *
     * Chỉ đọc / tra cứu.
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
     * Chi tiết giao dịch.
     *
     * Không có edit/update/delete.
     */
    public function show(int $id)
    {
        $transaction = PaymentTransaction::query()
            ->with([
                'payable.paymentStatus',
            ])
            ->findOrFail($id);

        $payable = $transaction->payable;

        /*
        |--------------------------------------------------------------------------
        | Lượt khám liên quan
        |--------------------------------------------------------------------------
        */
        $visit = null;

        if ($payable) {
            $visit = DB::table('medical_visits')
                ->where(
                    'id',
                    $payable->medical_visit_id
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

        if ($transaction->collected_by_employee_id) {
            $collector = DB::table('employees')
                ->where(
                    'id',
                    $transaction->collected_by_employee_id
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
}
