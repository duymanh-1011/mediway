<?php

namespace App\Services;

use App\Models\MedicalVisit;
use App\Models\ExaminationRoom;
use App\Models\ExaminationRoomQueue;
use App\Models\QueueStatus;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\ServiceOrderItem;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PaymentCollectionService
{
    public function collect(Request $request, Payable $payable, array $validated): PaymentTransaction
    {
        if (($validated['payment_method'] ?? null) !== 'CASH') {
            throw ValidationException::withMessages(['payment_method' => 'Thanh toán QR chưa có cơ chế xác minh tiền về.']);
        }
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

        if (! $paidStatus) {
            throw ValidationException::withMessages([
                'payment_method' => 'Chưa cấu hình trạng thái “Đã thanh toán” cho phép thực hiện dịch vụ. Vui lòng kiểm tra mục Trạng thái thanh toán.',
            ]);
        }

        try {
        return DB::transaction(
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
                    $visit = MedicalVisit::with(['status', 'appointment'])->whereKey($payable->medical_visit_id)->lockForUpdate()->firstOrFail();
                    $collector = Employee::whereKey($validated['collected_by_employee_id'])->where('status', 'ACTIVE')->lockForUpdate()->first();
                    $detail = $collector?->detail()->lockForUpdate()->first();
                    if (! $collector || $detail?->employee_type !== 'CASHIER') {
                        throw ValidationException::withMessages(['collected_by_employee_id' => 'Nhân viên thu ngân không còn hợp lệ hoặc không hoạt động. Vui lòng chọn lại.']);
                    }
                    $item = $payable->service_order_item_id
                        ? ServiceOrderItem::with(['serviceStatus', 'serviceOrder'])->whereKey($payable->service_order_item_id)->lockForUpdate()->first()
                        : null;
                    if ($item && ($item->serviceStatus?->name === 'CANCELLED'
                        || ! $item->serviceOrder || (int) $item->serviceOrder->medical_visit_id !== (int) $payable->medical_visit_id)) {
                        throw ValidationException::withMessages(['payment_method' => 'Không thể thanh toán dịch vụ đã hủy hoặc không khớp lượt khám.']);
                    }
                    $lockedPayable =
                        Payable::query()
                            ->with('paymentStatus')
                            ->lockForUpdate()
                            ->findOrFail(
                                $payable->id
                            );

                    if ((int) $lockedPayable->medical_visit_id !== (int) $visit->id) {
                        throw ValidationException::withMessages(['payment_date' => 'Lượt khám của khoản thu đã thay đổi. Vui lòng thử lại.']);
                    }

                    if ($lockedPayable->payment_type === 'SERVICE_FEE' && (
                        ! $item || (int) $lockedPayable->service_order_item_id !== (int) $item->id
                        || ! $item->serviceOrder || (int) $item->serviceOrder->medical_visit_id !== (int) $visit->id
                        || ! $item->medicalService || $item->price_at_order === null || (float) $item->price_at_order < 0
                        || (int) round((float) $lockedPayable->amount * 100) !== (int) round((float) $item->price_at_order * 100)
                    )) {
                        throw ValidationException::withMessages(['payment_method' => 'Khoản dịch vụ thiếu thông tin chỉ định hoặc giá không khớp. Vui lòng liên hệ bộ phận phụ trách trước khi thu tiền.']);
                    }

                    // Đọc lại lịch hẹn có khóa sau các khóa hiện hữu; không tin quan hệ đã nạp trước transaction.
                    if ($visit->appointment_id) {
                        $visit->setRelation('appointment', \App\Models\Appointment::query()
                            ->whereKey($visit->appointment_id)->lockForUpdate()->first());
                    }
                    $lockedPayable->setRelation('medicalVisit', $visit);
                    app(AppointmentPaymentDateGuard::class)->assertCanPay($lockedPayable);

                    $lockedStatus =
                        $lockedPayable
                            ->paymentStatus
                            ?->name;

                    if ((float) $lockedPayable->amount <= 0 || $lockedPayable->transactions()->where('transaction_result', 'SUCCESS')->exists()) {
                        throw ValidationException::withMessages(['payment_method' => 'Khoản thu không hợp lệ hoặc đã có giao dịch thành công.']);
                    }

                    if (! in_array($lockedStatus, ['UNPAID', 'PENDING'], true)) {
                        throw ValidationException::withMessages(['payment_method' => 'Khoản thu không ở trạng thái có thể thanh toán.']);
                    }

                    if ($lockedStatus === 'PAID') {
                        throw ValidationException::withMessages([
                            'payment_method' => 'Khoản phải thanh toán đã được thanh toán bởi một giao dịch khác.',
                        ]);
                    }

                    if ($lockedStatus === 'CANCELLED') {
                        throw ValidationException::withMessages([
                            'payment_method' => 'Khoản phải thanh toán đã bị hủy.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Sinh mã giao dịch GDxxxxxx
                    |--------------------------------------------------------------------------
                    */
                    $transactionCode = 'GD'.(string) \Illuminate\Support\Str::ulid();

                    $now = now();

                    /*
                    |--------------------------------------------------------------------------
                    | Tạo giao dịch thành công
                    |--------------------------------------------------------------------------
                    */
                    $transaction =
                        PaymentTransaction::create([
                            'transaction_code' => $transactionCode,

                            'payable_id' => $lockedPayable->id,

                            /*
                            | Không nhận số tiền từ form.
                            | Luôn lấy số tiền của payable.
                            */
                            'amount' => $lockedPayable->amount,

                            'payment_method' => $validated[
                                    'payment_method'
                                ],

                            'transaction_result' => 'SUCCESS',

                            'provider_transaction_code' => $validated[
                                    'provider_transaction_code'
                                ] ?? null,

                            'started_at' => $now,

                            'paid_at' => $now,

                            'confirmed_at' => $now,

                            'collected_by_employee_id' => $collector->id,

                            'payment_content' => $validated[
                                    'payment_content'
                                ] ?? null,

                            'failure_reason' => null,

                            'notes' => $validated[
                                    'notes'
                                ] ?? null,
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Chuyển payable sang PAID
                    |--------------------------------------------------------------------------
                    */
                    $lockedPayable->update([
                        'payment_status_id' => $paidStatus->id,

                        'paid_at' => $now,
                    ]);

                    // Tiếp nhận từ lịch hẹn chỉ vào hàng đợi sau khi phí khám được thu.
                    // Lượt khám cũ đã có hàng đợi hoặc đã bắt đầu không bị tạo trùng.
                    if ($lockedPayable->payment_type === 'EXAMINATION_FEE' && ($visit->appointment_id || $visit->reception_source === 'WALK_IN')
                        && $visit->status?->name === 'WAITING'
                        && ! $visit->queues()->where('queue_type', 'INITIAL_EXAM')->exists()) {
                        $visit->assertExaminationFeePaid();
                        if (($visit->appointment_id && ! $visit->appointment?->isScheduledForToday())
                            || (! $visit->appointment_id && ! $visit->checked_in_at?->isToday())) {
                            throw ValidationException::withMessages(['payment_method' => 'Không thể xếp hàng cho lịch khám khác ngày. Vui lòng kiểm tra lại lịch hẹn trước khi thu phí.']);
                        }
                        $waiting = QueueStatus::where('name', 'WAITING')->where('is_active', true)->first();
                        if (! $waiting) {
                            throw ValidationException::withMessages(['payment_method' => 'Chưa cấu hình trạng thái hàng đợi đang chờ.']);
                        }
                        ExaminationRoom::whereKey($visit->examination_room_id)->lockForUpdate()->firstOrFail();
                        $lastNumber = ExaminationRoomQueue::whereDate('queued_at', now()->toDateString())
                            ->whereHas('medicalVisit', fn ($q) => $q->where('examination_room_id', $visit->examination_room_id))
                            ->max('queue_number');
                        ExaminationRoomQueue::create([
                            'medical_visit_id' => $visit->id, 'queue_status_id' => $waiting->id,
                            'queue_number' => ((int) $lastNumber) + 1, 'queue_type' => 'INITIAL_EXAM',
                            'queued_at' => now(), 'call_count' => 0,
                        ]);
                    }

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
            , 3);
        } catch (\Throwable $exception) {
            if (! (new \Illuminate\Database\ConcurrencyErrorDetector())->causedByConcurrencyError($exception)) {
                throw $exception;
            }

            report($exception);
            throw ValidationException::withMessages([
                'payment_method' => 'Khoản thu đang được xử lý bởi giao dịch khác. Vui lòng kiểm tra lại trạng thái và thử lại.',
            ]);
        }

    }

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
            'transaction_code' => $transaction
                ->transaction_code,

            'payable_id' => $transaction
                ->payable_id,

            'payable_code' => $payable
                ->payable_code,

            'amount' => $transaction
                ->amount,

            'payment_method' => $transaction
                ->payment_method,

            'transaction_result' => $transaction
                ->transaction_result,

            'collected_by_employee_id' => $transaction->collected_by_employee_id,

            'provider_transaction_code' => $transaction
                ->provider_transaction_code,

            'paid_at' => $transaction
                ->paid_at
                ?->format(
                    'Y-m-d H:i:s'
                ),
        ];

        DB::table('audit_logs')
            ->insert([
                'user_id' => Auth::id(),

                'role_name_snapshot' => $roleNameSnapshot,

                'action' => 'CREATE',

                'entity_type' => 'payment_transactions',

                'entity_id' => $transaction->id,

                'entity_business_code' => $transaction
                    ->transaction_code,

                'old_values' => json_encode(
                    [],
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

                'description' => 'Ghi nhận thanh toán '
                    .$transaction
                        ->transaction_code
                    .' cho khoản '
                    .$payable
                        ->payable_code
                    .'.',

                'ip_address' => $request->ip(),

                'user_agent' => $request
                    ->userAgent(),

                'performed_at' => now(),

                'created_at' => now(),
            ]);
    }
}
