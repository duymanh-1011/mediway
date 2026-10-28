<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Payable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $patient =
            $this->currentPatient();

        /*
        |--------------------------------------------------------------------------
        | Chỉ lấy khoản phải thanh toán của bệnh nhân hiện tại
        |--------------------------------------------------------------------------
        */

        $payables =
            Payable::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    $patient->id
                )
            )
            ->with([
                'medicalVisit',
                'paymentStatus',
                'transactions' => function ($query) {
                    $query
                        ->orderByDesc(
                            'started_at'
                        )
                        ->orderByDesc(
                            'id'
                        );
                },
            ])
            ->orderByDesc(
                'generated_at'
            )
            ->orderByDesc(
                'id'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tổng hợp chỉ để hiển thị
        |--------------------------------------------------------------------------
        */

        $totalAmount =
            $payables->sum(
                fn(Payable $payable) =>
                (float) $payable->amount
            );

        $outstandingPayables =
            $payables->filter(
                fn(Payable $payable) =>
                ! in_array(
                    $payable
                        ->paymentStatus
                        ?->name,
                    [
                        'PAID',
                        'CANCELLED',
                    ],
                    true
                )
            );

        $outstandingAmount =
            $outstandingPayables->sum(
                fn(Payable $payable) =>
                (float) $payable->amount
            );

        $successfulTransactions =
            $payables
            ->flatMap(
                fn(Payable $payable) =>
                $payable->transactions
            )
            ->where(
                'transaction_result',
                'SUCCESS'
            )
            ->count();

        $summary = [
            'payable_count' =>
            $payables->count(),

            'total_amount' =>
            $totalAmount,

            'outstanding_count' =>
            $outstandingPayables->count(),

            'outstanding_amount' =>
            $outstandingAmount,

            'successful_transaction_count' =>
            $successfulTransactions,
        ];

        return view(
            'portal.payments.index',
            compact(
                'payables',
                'summary'
            )
        );
    }

    private function currentPatient(): Patient
    {
        $user =
            Auth::user();

        $user?->loadMissing(
            'patient'
        );

        $patient =
            $user?->patient;

        if (
            ! $patient
            ||
            $patient->status
            !==
            'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'auth' =>
                'Hồ sơ bệnh nhân không còn đủ điều kiện sử dụng Patient Portal.',
            ]);
        }

        return $patient;
    }
}
