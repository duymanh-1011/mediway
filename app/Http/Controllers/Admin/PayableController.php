<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelPayableRequest;
use App\Http\Requests\Admin\UpdatePayableStatusRequest;
use App\Models\AuditLog;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderProgress;
use App\Models\ServiceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                        '%'.$keyword.'%'
                    )
                    ->orWhere(
                        'medical_visits.visit_code',
                        'like',
                        '%'.$keyword.'%'
                    )
                    ->orWhere(
                        'payables.description',
                        'like',
                        '%'.$keyword.'%'
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

        /*
        |--------------------------------------------------------------------------
        | Danh sách loại khoản thu
        |--------------------------------------------------------------------------
        */
        $paymentTypes = Payable::query()
            ->whereNotNull('payment_type')
            ->distinct()
            ->orderBy('payment_type')
            ->pluck('payment_type');

        /*
        |--------------------------------------------------------------------------
        | Danh sách trạng thái thanh toán
        |--------------------------------------------------------------------------
        */
        $paymentStatuses = PaymentStatus::query()
            ->orderBy('process_order')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'description',
                'is_active',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Phân trang
        |--------------------------------------------------------------------------
        */
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

                'serviceOrderItem.serviceStatus',
                'serviceOrderItem.result',

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
            ->where(
                'id',
                $payable->medical_visit_id
            )
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

        /*
        |--------------------------------------------------------------------------
        | Trạng thái có thể sử dụng
        |--------------------------------------------------------------------------
        */
        $paymentStatuses = PaymentStatus::query()
            ->where(
                'is_active',
                true
            )
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
    public function updateStatus(UpdatePayableStatusRequest $request, int $id): RedirectResponse
    {
        $payable = Payable::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $payable, $validated): void {
            MedicalVisit::whereKey($payable->medical_visit_id)->lockForUpdate()->firstOrFail();
            $item = $payable->service_order_item_id
                ? ServiceOrderItem::with(['serviceStatus', 'serviceOrder'])->whereKey($payable->service_order_item_id)->lockForUpdate()->firstOrFail()
                : null;
            $payable = Payable::with('paymentStatus')->whereKey($payable->id)->lockForUpdate()->firstOrFail();
            $newStatus = PaymentStatus::whereKey($validated['payment_status_id'])->where('is_active', true)->firstOrFail();
            $oldStatus = $payable->paymentStatus?->name;

            if ($oldStatus === 'CANCELLED' || $item?->serviceStatus?->name === 'CANCELLED') {
                throw ValidationException::withMessages(['payment_status_id' => 'Khoản thu hoặc dịch vụ đã hủy không thể được mở lại.']);
            }
            if ($item && $item->serviceOrder->medical_visit_id !== $payable->medical_visit_id) {
                throw ValidationException::withMessages(['payment_status_id' => 'Khoản thu không khớp với lượt khám của dịch vụ. Vui lòng kiểm tra lại phiếu chỉ định.']);
            }
            if ($newStatus->name === 'CANCELLED') {
                throw ValidationException::withMessages(['payment_status_id' => 'Để hủy khoản thu và dịch vụ chưa bắt đầu tương ứng, vui lòng dùng nút Hủy khoản thu.']);
            }
            $successfulTransaction = $payable->transactions()->where('transaction_result', 'SUCCESS')->orderByDesc('confirmed_at')->first();
            if (($oldStatus === 'PAID' || $payable->paid_at || $successfulTransaction) && $newStatus->name !== 'PAID') {
                throw ValidationException::withMessages(['payment_status_id' => 'Khoản thu đã thanh toán nên không thể đổi sang trạng thái khác. Lịch sử thanh toán được giữ nguyên.']);
            }
            if ($newStatus->name === 'PAID' && ! $successfulTransaction) {
                throw ValidationException::withMessages(['payment_status_id' => 'Chưa có giao dịch thanh toán thành công cho khoản thu này. Vui lòng mở form thanh toán để ghi nhận giao dịch.']);
            }
            if ($oldStatus === $newStatus->name) {
                return;
            }

            $oldValues = ['payment_status_id' => $payable->payment_status_id, 'payment_status' => $oldStatus,
                'paid_at' => $payable->paid_at?->format('Y-m-d H:i:s')];
            $paidAt = $newStatus->name === 'PAID'
                ? ($successfulTransaction->paid_at ?? $successfulTransaction->confirmed_at ?? now())
                : null;
            $payable->update(['payment_status_id' => $newStatus->id, 'paid_at' => $paidAt]);
            $this->writeAuditLog($request, $payable, 'STATUS_CHANGE', $oldValues, [
                'payment_status_id' => $newStatus->id, 'payment_status' => $newStatus->name,
                'paid_at' => $paidAt?->format('Y-m-d H:i:s'),
            ], 'Thay đổi trạng thái khoản phải thanh toán '.$payable->payable_code.'.');
        }, 3);

        return redirect()->route('admin.payables.show', $payable->id)->with('success', 'Cập nhật trạng thái thanh toán thành công.');
    }

    /**
     * Hủy nghiệp vụ khoản phải thanh toán.
     *
     * Không xóa bản ghi.
     */
    public function cancel(CancelPayableRequest $request, int $id): RedirectResponse
    {
        $payable = Payable::findOrFail($id);
        $reason = trim($request->validated()['cancel_reason']);
        try {
            DB::transaction(function () use ($request, $payable, $reason): void {
                MedicalVisit::whereKey($payable->medical_visit_id)->lockForUpdate()->firstOrFail();
                $item = $payable->service_order_item_id
                    ? ServiceOrderItem::with(['serviceStatus', 'serviceOrder'])->whereKey($payable->service_order_item_id)->lockForUpdate()->firstOrFail()
                    : null;
                $lockedPayable = Payable::with('paymentStatus')->whereKey($payable->id)->lockForUpdate()->firstOrFail();
                if ($item) {
                    $currentServiceStatus = ServiceStatus::whereKey($item->service_status_id)->lockForUpdate()->firstOrFail();
                    $item->setRelation('serviceStatus', $currentServiceStatus);
                    if ($currentServiceStatus->name !== 'CANCELLED' && ! $currentServiceStatus->allows_transition) {
                        throw ValidationException::withMessages(['cancel_reason' => 'Chưa thể hủy khoản thu vì dịch vụ đang bị khóa thay đổi trạng thái. Vui lòng kiểm tra mục Trạng thái dịch vụ.']);
                    }
                }
                if ($lockedPayable->paymentStatus?->name === 'PAID' || $lockedPayable->paid_at
                    || $lockedPayable->transactions()->where('transaction_result', 'SUCCESS')->exists()) {
                    throw ValidationException::withMessages(['cancel_reason' => 'Không thể hủy khoản đã thanh toán.']);
                }
                if ($item && ($item->serviceOrder->medical_visit_id !== $lockedPayable->medical_visit_id
                    || ! $item->canBeCancelled())) {
                    throw ValidationException::withMessages(['cancel_reason' => 'Chỉ được hủy dịch vụ chưa bắt đầu và chưa có kết quả.']);
                }

                $cancelPaymentStatus = PaymentStatus::where('name', 'CANCELLED')->where('is_active', true)->first();
                if (! $cancelPaymentStatus) {
                    throw new \RuntimeException('Chưa có trạng thái “Đã hủy” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái thanh toán.');
                }
                if ($item && $item->serviceStatus?->name !== 'CANCELLED') {
                    $cancelServiceStatus = ServiceStatus::where('name', 'CANCELLED')->where('is_active', true)->first();
                    if (! $cancelServiceStatus) {
                        throw new \RuntimeException('Chưa có trạng thái “Đã hủy” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái dịch vụ.');
                    }
                    $oldItem = ['service_status_id' => $item->service_status_id, 'service_status' => $item->serviceStatus?->name, 'execution_notes' => $item->execution_notes];
                    $notes = trim(($item->execution_notes ? $item->execution_notes.PHP_EOL : '').'Lý do hủy: '.$reason);
                    $item->update(['service_status_id' => $cancelServiceStatus->id, 'execution_notes' => $notes]);
                    AuditLog::create([
                        'user_id' => Auth::id(), 'role_name_snapshot' => Auth::user()?->role?->name,
                        'action' => 'STATUS_CHANGE', 'entity_type' => 'service_order_items',
                        'entity_id' => $item->id, 'entity_business_code' => null,
                        'old_values' => $oldItem,
                        'new_values' => ['service_status_id' => $cancelServiceStatus->id, 'service_status' => 'CANCELLED', 'execution_notes' => $notes],
                        'description' => 'Hủy dịch vụ do hủy khoản thu '.$lockedPayable->payable_code.'. Lý do: '.$reason,
                        'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'performed_at' => now(),
                    ]);
                }

                if ($lockedPayable->paymentStatus?->name !== 'CANCELLED') {
                    $oldValues = [
                        'payment_status_id' => $lockedPayable->payment_status_id,
                        'payment_status' => $lockedPayable->paymentStatus?->name, 'notes' => $lockedPayable->notes,
                        'paid_at' => $lockedPayable->paid_at?->format('Y-m-d H:i:s'),
                    ];
                    $lockedPayable->update([
                        'payment_status_id' => $cancelPaymentStatus->id, 'paid_at' => null,
                        'notes' => trim(($lockedPayable->notes ? $lockedPayable->notes.PHP_EOL : '').'Lý do hủy: '.$reason),
                    ]);
                    $this->writeAuditLog($request, $lockedPayable, 'STATUS_CHANGE', $oldValues, [
                        'payment_status_id' => $cancelPaymentStatus->id, 'payment_status' => 'CANCELLED',
                        'notes' => $lockedPayable->notes, 'paid_at' => null,
                    ], 'Hủy khoản phải thanh toán '.$lockedPayable->payable_code.'. Lý do: '.$reason);
                }
                if ($item) {
                    app(ServiceOrderProgress::class)->synchronize($item->service_order_id);
                }
            }, 3);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.payables.show', $payable->id)->with('success',
            $payable->service_order_item_id
                ? 'Đã hủy khoản thu '.$payable->payable_code.' và dịch vụ tương ứng. '.app(ServiceOrderProgress::class)->returnQueueMessage($payable->medical_visit_id)
                : 'Đã hủy khoản phải thanh toán.'
        );
    }

    /**
     * Ghi Audit Log cho thao tác tài chính.
     */
    private function writeAuditLog(
        Request $request,
        Payable $payable,
        string $action,
        array $oldValues,
        array $newValues,
        string $description
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Role snapshot
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Ghi Audit Log
        |--------------------------------------------------------------------------
        */
        DB::table('audit_logs')->insert([
            'user_id' => Auth::id(),

            'role_name_snapshot' => $roleNameSnapshot,

            'action' => $action,

            'entity_type' => 'payables',

            'entity_id' => $payable->id,

            'entity_business_code' => $payable->payable_code,

            'old_values' => json_encode(
                $oldValues,
                JSON_UNESCAPED_UNICODE
                    |
                    JSON_UNESCAPED_SLASHES
            ),

            'new_values' => json_encode(
                $newValues,
                JSON_UNESCAPED_UNICODE
                    |
                    JSON_UNESCAPED_SLASHES
            ),

            'description' => $description,

            'ip_address' => $request->ip(),

            'user_agent' => $request->userAgent(),

            'performed_at' => now(),

            'created_at' => now(),
        ]);
    }
}
