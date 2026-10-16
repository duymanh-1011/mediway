<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PayableController extends Controller
{
    /**
     * Danh sách khoản phải thanh toán.
     */
    public function index(Request $request)
    {
        $query = Payable::query()
            ->leftJoin(
                'medical_visits',
                'medical_visits.id',
                '=',
                'payables.medical_visit_id'
            )
            ->leftJoin(
                'payment_statuses',
                'payment_statuses.id',
                '=',
                'payables.payment_status_id'
            )
            ->select([
                'payables.*',
                'medical_visits.visit_code',
                'payment_statuses.name as payment_status_name',
            ])
            ->withCount('transactions');

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
                        'payables.description',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc loại khoản phải thu
        |--------------------------------------------------------------------------
        */
        if ($request->filled('payment_type')) {
            $query->where(
                'payables.payment_type',
                $request->payment_type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái
        |--------------------------------------------------------------------------
        */
        if ($request->filled('payment_status_id')) {
            $query->where(
                'payables.payment_status_id',
                $request->payment_status_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc ngày tạo khoản phải thu
        |--------------------------------------------------------------------------
        */
        if ($request->filled('date_from')) {
            $query->whereDate(
                'payables.generated_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'payables.generated_at',
                '<=',
                $request->date_to
            );
        }

        $paymentTypes = Payable::query()
            ->whereNotNull('payment_type')
            ->distinct()
            ->orderBy('payment_type')
            ->pluck('payment_type');

        $paymentStatuses = PaymentStatus::query()
            ->orderBy('process_order')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'description',
                'is_active',
            ]);

        $payables = $query
            ->orderByDesc('payables.generated_at')
            ->orderByDesc('payables.id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.payables.index',
            compact(
                'payables',
                'paymentTypes',
                'paymentStatuses'
            )
        );
    }

    /**
     * Chi tiết khoản phải thanh toán.
     */
    public function show(int $id)
    {
        $payable = Payable::query()
            ->with([
                'paymentStatus',
                'transactions' => function ($query) {
                    $query
                        ->orderByDesc('started_at')
                        ->orderByDesc('id');
                },
            ])
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Lượt khám
        |--------------------------------------------------------------------------
        */
        $visit = DB::table('medical_visits')
            ->where('id', $payable->medical_visit_id)
            ->select([
                'id',
                'visit_code',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Dịch vụ liên quan
        |--------------------------------------------------------------------------
        |
        | EXAMINATION_FEE sẽ không có service_order_item_id.
        |
        */
        $serviceContext = null;

        if ($payable->service_order_item_id) {
            $serviceContext = DB::table('service_order_items')
                ->leftJoin(
                    'service_orders',
                    'service_orders.id',
                    '=',
                    'service_order_items.service_order_id'
                )
                ->leftJoin(
                    'medical_services',
                    'medical_services.id',
                    '=',
                    'service_order_items.medical_service_id'
                )
                ->where(
                    'service_order_items.id',
                    $payable->service_order_item_id
                )
                ->select([
                    'service_order_items.id as service_order_item_id',
                    'service_order_items.price_at_order',
                    'service_orders.service_order_code',
                    'medical_services.service_code',
                    'medical_services.name as service_name',
                ])
                ->first();
        }

        $paymentStatuses = PaymentStatus::query()
            ->where('is_active', true)
            ->orderBy('process_order')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'description',
            ]);

        return view(
            'admin.payables.show',
            compact(
                'payable',
                'visit',
                'serviceContext',
                'paymentStatuses'
            )
        );
    }

    /**
     * Thay đổi trạng thái khoản phải thanh toán.
     */
    public function updateStatus(Request $request, int $id)
    {
        $payable = Payable::query()
            ->with([
                'paymentStatus',
                'transactions',
            ])
            ->findOrFail($id);

        $validated = $request->validate([
            'payment_status_id' => [
                'required',
                'integer',
                Rule::exists(
                    'payment_statuses',
                    'id'
                )->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],

            'confirm_update' => [
                'accepted',
            ],
        ], [
            'payment_status_id.required' =>
            'Vui lòng chọn trạng thái thanh toán.',

            'payment_status_id.exists' =>
            'Trạng thái thanh toán không hợp lệ.',

            'confirm_update.accepted' =>
            'Bạn phải xác nhận trước khi thay đổi trạng thái.',
        ]);

        $newStatus = PaymentStatus::query()
            ->findOrFail($validated['payment_status_id']);

        $oldStatusName = $payable->paymentStatus?->name;

        /*
        |--------------------------------------------------------------------------
        | CANCELLED dùng thao tác Hủy nghiệp vụ riêng
        |--------------------------------------------------------------------------
        */
        if ($newStatus->name === 'CANCELLED') {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Để hủy khoản phải thanh toán, hãy dùng thao tác Hủy nghiệp vụ.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Không đưa khoản đã thanh toán quay ngược trạng thái
        |--------------------------------------------------------------------------
        |
        | Không có nghiệp vụ hoàn tiền trong phạm vi module hiện tại.
        |
        */
        if (
            $oldStatusName === 'PAID'
            && $newStatus->name !== 'PAID'
        ) {
            return back()->with(
                'error',
                'Khoản đã thanh toán không thể chuyển ngược trạng thái tại màn hình Admin.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Không tự suy diễn FAILED thành PAID
        |--------------------------------------------------------------------------
        |
        | Khi chuyển PAID phải thực sự có giao dịch SUCCESS.
        |
        */
        $successfulTransaction = $payable->transactions
            ->where('transaction_result', 'SUCCESS')
            ->sortByDesc('confirmed_at')
            ->first();

        if (
            $newStatus->name === 'PAID'
            && !$successfulTransaction
        ) {
            return back()->with(
                'error',
                'Không thể chuyển sang PAID vì chưa có giao dịch thanh toán SUCCESS.'
            );
        }

        if ($oldStatusName === $newStatus->name) {
            return back()->with(
                'success',
                'Khoản phải thanh toán đã ở trạng thái '
                    . $newStatus->name
                    . '.'
            );
        }

        $oldValues = [
            'payment_status_id' =>
            $payable->payment_status_id,

            'payment_status' =>
            $oldStatusName,

            'paid_at' =>
            $payable->paid_at
                ? $payable->paid_at->format('Y-m-d H:i:s')
                : null,
        ];

        DB::transaction(function () use (
            $request,
            $payable,
            $newStatus,
            $successfulTransaction,
            $oldValues
        ) {
            $paidAt = $payable->paid_at;

            if ($newStatus->name === 'PAID') {
                $paidAt =
                    $successfulTransaction->paid_at
                    ?? $successfulTransaction->confirmed_at
                    ?? now();
            }

            $payable->update([
                'payment_status_id' =>
                $newStatus->id,

                'paid_at' =>
                $paidAt,
            ]);

            $newValues = [
                'payment_status_id' =>
                $newStatus->id,

                'payment_status' =>
                $newStatus->name,

                'paid_at' =>
                $paidAt
                    ? $paidAt->format('Y-m-d H:i:s')
                    : null,
            ];

            $this->writeAuditLog(
                $request,
                $payable,
                'STATUS_CHANGE',
                $oldValues,
                $newValues,
                'Thay đổi trạng thái khoản phải thanh toán '
                    . $payable->payable_code
                    . ' từ '
                    . ($oldValues['payment_status'] ?? '—')
                    . ' sang '
                    . $newStatus->name
                    . '.'
            );
        });

        return redirect()
            ->route(
                'admin.payables.show',
                $payable->id
            )
            ->with(
                'success',
                'Cập nhật trạng thái thanh toán thành công.'
            );
    }

    /**
     * Hủy nghiệp vụ khoản phải thanh toán.
     *
     * Không xóa bản ghi.
     */
    public function cancel(Request $request, int $id)
    {
        $payable = Payable::query()
            ->with('paymentStatus')
            ->findOrFail($id);

        $request->validate([
            'confirm_cancel' => [
                'accepted',
            ],

            'cancel_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ], [
            'confirm_cancel.accepted' =>
            'Bạn phải xác nhận trước khi hủy khoản phải thanh toán.',

            'cancel_reason.required' =>
            'Vui lòng nhập lý do hủy.',
        ]);

        $currentStatus = $payable->paymentStatus?->name;

        if ($currentStatus === 'PAID') {
            return back()->with(
                'error',
                'Không thể hủy khoản đã thanh toán.'
            );
        }

        if ($currentStatus === 'CANCELLED') {
            return back()->with(
                'success',
                'Khoản phải thanh toán này đã được hủy trước đó.'
            );
        }

        $cancelStatus = PaymentStatus::query()
            ->where('name', 'CANCELLED')
            ->first();

        if (!$cancelStatus) {
            return back()->with(
                'error',
                'Không tìm thấy trạng thái CANCELLED trong cấu hình thanh toán.'
            );
        }

        $oldValues = [
            'payment_status_id' =>
            $payable->payment_status_id,

            'payment_status' =>
            $currentStatus,

            'notes' =>
            $payable->notes,

            'paid_at' =>
            $payable->paid_at
                ? $payable->paid_at->format('Y-m-d H:i:s')
                : null,
        ];

        DB::transaction(function () use (
            $request,
            $payable,
            $cancelStatus,
            $oldValues
        ) {
            $cancelReason = trim(
                $request->input('cancel_reason')
            );

            $newNotes = $payable->notes
                ? $payable->notes
                . PHP_EOL
                . 'Lý do hủy: '
                . $cancelReason
                : 'Lý do hủy: '
                . $cancelReason;

            $payable->update([
                'payment_status_id' =>
                $cancelStatus->id,

                'paid_at' =>
                null,

                'notes' =>
                $newNotes,
            ]);

            $newValues = [
                'payment_status_id' =>
                $cancelStatus->id,

                'payment_status' =>
                'CANCELLED',

                'notes' =>
                $newNotes,

                'paid_at' =>
                null,
            ];

            $this->writeAuditLog(
                $request,
                $payable,
                'STATUS_CHANGE',
                $oldValues,
                $newValues,
                'Hủy khoản phải thanh toán '
                    . $payable->payable_code
                    . '. Lý do: '
                    . $cancelReason
            );
        });

        return redirect()
            ->route(
                'admin.payables.show',
                $payable->id
            )
            ->with(
                'success',
                'Đã hủy khoản phải thanh toán.'
            );
    }

    /**
     * Ghi audit log cho thao tác tài chính.
     */
    private function writeAuditLog(
        Request $request,
        Payable $payable,
        string $action,
        array $oldValues,
        array $newValues,
        string $description
    ): void {
        $roleNameSnapshot = null;

        if (Auth::check()) {
            $roleNameSnapshot = DB::table('users')
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
                ->value('roles.name');
        }

        DB::table('audit_logs')->insert([
            'user_id' =>
            Auth::id(),

            'role_name_snapshot' =>
            $roleNameSnapshot,

            'action' =>
            $action,

            'entity_type' =>
            'payables',

            'entity_id' =>
            $payable->id,

            'entity_business_code' =>
            $payable->payable_code,

            'old_values' =>
            json_encode(
                $oldValues,
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
            $description,

            'ip_address' =>
            $request->ip(),

            'user_agent' =>
            $request->userAgent(),

            'performed_at' =>
            now(),

            'created_at' =>
            now(),
        ]);
    }
}
