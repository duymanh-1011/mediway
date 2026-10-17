<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExaminationRoomQueue;
use App\Models\QueueStatus;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceOrderItemController extends Controller
{
    /**
     * Bắt đầu thực hiện một dịch vụ.
     *
     * Chỉ cho phép khi:
     * - Item đang PENDING.
     * - Có payable tương ứng.
     * - Trạng thái thanh toán cho phép thực hiện dịch vụ.
     */
    public function start(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ): RedirectResponse {
        $serviceOrderItem->load([
            'serviceStatus',
            'serviceOrder',
            'medicalService',
            'payable.paymentStatus',
        ]);

        $currentStatusName =
            $serviceOrderItem
                ->serviceStatus
                ?->name;

        if ($currentStatusName === 'PROCESSING') {
            return back()->with(
                'success',
                'Dịch vụ này đang được thực hiện.'
            );
        }

        if ($currentStatusName === 'COMPLETED') {
            return back()->with(
                'error',
                'Dịch vụ này đã hoàn thành.'
            );
        }

        if ($currentStatusName === 'CANCELLED') {
            return back()->with(
                'error',
                'Dịch vụ này đã bị hủy.'
            );
        }

        if ($currentStatusName !== 'PENDING') {
            return back()->with(
                'error',
                'Trạng thái hiện tại không cho phép bắt đầu thực hiện dịch vụ.'
            );
        }

        $payable =
            $serviceOrderItem->payable;

        if (!$payable) {
            return back()->with(
                'error',
                'Dịch vụ chưa có khoản phải thanh toán.'
            );
        }

        $paymentStatus =
            $payable->paymentStatus;

        if (!$paymentStatus) {
            return back()->with(
                'error',
                'Khoản phải thanh toán chưa có trạng thái hợp lệ.'
            );
        }

        if (!$paymentStatus->allows_service_execution) {
            return back()->with(
                'error',
                'Dịch vụ chưa đủ điều kiện thực hiện. Vui lòng hoàn tất thanh toán trước.'
            );
        }

        $processingStatus =
            ServiceStatus::query()
                ->where(
                    'name',
                    'PROCESSING'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (!$processingStatus) {
            return back()->with(
                'error',
                'Không tìm thấy trạng thái dịch vụ PROCESSING đang hoạt động.'
            );
        }

        DB::transaction(
            function () use (
                $request,
                $serviceOrderItem,
                $processingStatus
            ) {
                $lockedItem =
                    ServiceOrderItem::query()
                        ->with([
                            'serviceStatus',
                            'payable.paymentStatus',
                            'serviceOrder',
                            'medicalService',
                        ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $serviceOrderItem->id
                        );

                if (
                    $lockedItem
                        ->serviceStatus
                        ?->name
                    !==
                    'PENDING'
                ) {
                    return;
                }

                $lockedPayable =
                    $lockedItem->payable;

                if (
                    !$lockedPayable
                    ||
                    !$lockedPayable
                        ->paymentStatus
                    ||
                    !$lockedPayable
                        ->paymentStatus
                        ->allows_service_execution
                ) {
                    return;
                }

                $oldValues = [
                    'service_status_id' =>
                        $lockedItem
                            ->service_status_id,

                    'service_status' =>
                        $lockedItem
                            ->serviceStatus
                            ?->name,

                    'started_at' =>
                        $lockedItem
                            ->started_at
                            ?->format(
                                'Y-m-d H:i:s'
                            ),
                ];

                $startedAt =
                    $lockedItem->started_at
                    ?? now();

                $lockedItem->update([
                    'service_status_id' =>
                        $processingStatus->id,

                    'started_at' =>
                        $startedAt,
                ]);

                $newValues = [
                    'service_status_id' =>
                        $processingStatus->id,

                    'service_status' =>
                        'PROCESSING',

                    'started_at' =>
                        $startedAt->format(
                            'Y-m-d H:i:s'
                        ),
                ];

                $this->writeAuditLog(
                    $request,
                    $lockedItem,
                    $oldValues,
                    $newValues,
                    'Bắt đầu thực hiện dịch vụ'
                );
            }
        );

        $serviceOrderItem->refresh();
        $serviceOrderItem->load('serviceStatus');

        if (
            $serviceOrderItem
                ->serviceStatus
                ?->name
            !==
            'PROCESSING'
        ) {
            return back()->with(
                'error',
                'Không thể bắt đầu dịch vụ. Vui lòng kiểm tra trạng thái thanh toán và trạng thái dịch vụ.'
            );
        }

        return redirect()
            ->route(
                'admin.service-orders.show',
                $serviceOrderItem
                    ->service_order_id
            )
            ->with(
                'success',
                'Đã bắt đầu thực hiện dịch vụ.'
            );
    }


    /**
     * Hoàn tất dịch vụ.
     *
     * Khi item cuối cùng của phiếu hoàn thành:
     * - ServiceOrder -> COMPLETED.
     *
     * Khi tất cả dịch vụ của lượt khám hoàn thành:
     * - Tạo hàng đợi RETURN_AFTER_SERVICE / WAITING.
     * - MedicalVisit vẫn giữ WAITING_FOR_SERVICE.
     */
    public function complete(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ): RedirectResponse {
        $serviceOrderItem->load([
            'serviceStatus',
            'serviceOrder',
            'medicalService',
            'result',
        ]);

        $currentStatusName =
            $serviceOrderItem
                ->serviceStatus
                ?->name;

        if ($currentStatusName === 'COMPLETED') {
            return back()->with(
                'success',
                'Dịch vụ này đã hoàn thành.'
            );
        }

        if ($currentStatusName === 'CANCELLED') {
            return back()->with(
                'error',
                'Dịch vụ này đã bị hủy.'
            );
        }

        if ($currentStatusName === 'PENDING') {
            return back()->with(
                'error',
                'Dịch vụ chưa được bắt đầu thực hiện.'
            );
        }

        if ($currentStatusName !== 'PROCESSING') {
            return back()->with(
                'error',
                'Trạng thái hiện tại không cho phép hoàn tất dịch vụ.'
            );
        }

        if (!$serviceOrderItem->result) {
            return back()->with(
                'error',
                'Chưa thể hoàn tất dịch vụ vì chưa có kết quả.'
            );
        }

        $completedStatus =
            ServiceStatus::query()
                ->where(
                    'name',
                    'COMPLETED'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (!$completedStatus) {
            return back()->with(
                'error',
                'Không tìm thấy trạng thái dịch vụ COMPLETED đang hoạt động.'
            );
        }

        try {
            DB::transaction(
                function () use (
                    $request,
                    $serviceOrderItem,
                    $completedStatus
                ) {
                    $lockedItem =
                        ServiceOrderItem::query()
                            ->with([
                                'serviceStatus',
                                'serviceOrder',
                                'medicalService',
                                'result',
                            ])
                            ->lockForUpdate()
                            ->findOrFail(
                                $serviceOrderItem->id
                            );

                    if (
                        $lockedItem
                            ->serviceStatus
                            ?->name
                        !==
                        'PROCESSING'
                    ) {
                        return;
                    }

                    if (!$lockedItem->result) {
                        return;
                    }

                    $oldValues = [
                        'service_status_id' =>
                            $lockedItem
                                ->service_status_id,

                        'service_status' =>
                            $lockedItem
                                ->serviceStatus
                                ?->name,

                        'started_at' =>
                            $lockedItem
                                ->started_at
                                ?->format(
                                    'Y-m-d H:i:s'
                                ),

                        'completed_at' =>
                            $lockedItem
                                ->completed_at
                                ?->format(
                                    'Y-m-d H:i:s'
                                ),
                    ];

                    $completedAt =
                        $lockedItem->completed_at
                        ?? now();

                    $lockedItem->update([
                        'service_status_id' =>
                            $completedStatus->id,

                        'completed_at' =>
                            $completedAt,
                    ]);

                    $newValues = [
                        'service_status_id' =>
                            $completedStatus->id,

                        'service_status' =>
                            'COMPLETED',

                        'started_at' =>
                            $lockedItem
                                ->started_at
                                ?->format(
                                    'Y-m-d H:i:s'
                                ),

                        'completed_at' =>
                            $completedAt->format(
                                'Y-m-d H:i:s'
                            ),
                    ];

                    $this->writeAuditLog(
                        $request,
                        $lockedItem,
                        $oldValues,
                        $newValues,
                        'Hoàn tất dịch vụ'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Kiểm tra phiếu hiện tại đã hoàn tất toàn bộ chưa
                    |--------------------------------------------------------------------------
                    */

                    $serviceOrderId =
                        $lockedItem
                            ->service_order_id;

                    $orderHasIncompleteItems =
                        DB::table(
                            'service_order_items'
                        )
                            ->join(
                                'service_statuses',
                                'service_statuses.id',
                                '=',
                                'service_order_items.service_status_id'
                            )
                            ->where(
                                'service_order_items.service_order_id',
                                $serviceOrderId
                            )
                            ->where(
                                'service_statuses.name',
                                '!=',
                                'COMPLETED'
                            )
                            ->exists();

                    /*
                    |--------------------------------------------------------------------------
                    | Nếu tất cả item của phiếu đã hoàn thành
                    | → ServiceOrder = COMPLETED
                    |--------------------------------------------------------------------------
                    */

                    if (!$orderHasIncompleteItems) {
                        DB::table(
                            'service_orders'
                        )
                            ->where(
                                'id',
                                $serviceOrderId
                            )
                            ->update([
                                'status' =>
                                    'COMPLETED',

                                'updated_at' =>
                                    now(),
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Lấy lượt khám của phiếu
                    |--------------------------------------------------------------------------
                    */

                    $medicalVisitId =
                        DB::table(
                            'service_orders'
                        )
                            ->where(
                                'id',
                                $serviceOrderId
                            )
                            ->value(
                                'medical_visit_id'
                            );

                    if (!$medicalVisitId) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Kiểm tra toàn bộ dịch vụ của lượt khám
                    |
                    | Không chỉ kiểm tra phiếu hiện tại vì một lượt khám
                    | có thể có nhiều phiếu chỉ định.
                    |--------------------------------------------------------------------------
                    */

                    $visitHasIncompleteServices =
                        DB::table(
                            'service_order_items'
                        )
                            ->join(
                                'service_orders',
                                'service_orders.id',
                                '=',
                                'service_order_items.service_order_id'
                            )
                            ->join(
                                'service_statuses',
                                'service_statuses.id',
                                '=',
                                'service_order_items.service_status_id'
                            )
                            ->where(
                                'service_orders.medical_visit_id',
                                $medicalVisitId
                            )
                            ->where(
                                'service_statuses.name',
                                '!=',
                                'COMPLETED'
                            )
                            ->exists();

                    if ($visitHasIncompleteServices) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Chỉ tạo hàng đợi quay lại khi lượt khám
                    | đang WAITING_FOR_SERVICE.
                    |--------------------------------------------------------------------------
                    */

                    $medicalVisit =
                        DB::table(
                            'medical_visits'
                        )
                            ->join(
                                'visit_statuses',
                                'visit_statuses.id',
                                '=',
                                'medical_visits.visit_status_id'
                            )
                            ->where(
                                'medical_visits.id',
                                $medicalVisitId
                            )
                            ->select([
                                'medical_visits.id',
                                'medical_visits.examination_room_id',
                                'visit_statuses.name as visit_status_name',
                            ])
                            ->lockForUpdate()
                            ->first();

                    if (!$medicalVisit) {
                        return;
                    }

                    if (
                        $medicalVisit
                            ->visit_status_name
                        !==
                        'WAITING_FOR_SERVICE'
                    ) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Kiểm tra đã có hàng đợi quay lại đang hoạt động chưa
                    |
                    | Chỉ chặn WAITING / CALLED / IN_SERVICE.
                    | Nếu lượt quay lại cũ đã COMPLETED thì vẫn có thể
                    | phát sinh một chu kỳ dịch vụ mới sau đó.
                    |--------------------------------------------------------------------------
                    */

                    $activeReturnQueueExists =
                        DB::table(
                            'examination_room_queues'
                        )
                            ->join(
                                'queue_statuses',
                                'queue_statuses.id',
                                '=',
                                'examination_room_queues.queue_status_id'
                            )
                            ->where(
                                'examination_room_queues.medical_visit_id',
                                $medicalVisitId
                            )
                            ->where(
                                'examination_room_queues.queue_type',
                                'RETURN_AFTER_SERVICE'
                            )
                            ->whereIn(
                                'queue_statuses.name',
                                [
                                    'WAITING',
                                    'CALLED',
                                    'IN_SERVICE',
                                ]
                            )
                            ->exists();

                    if ($activeReturnQueueExists) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Trạng thái WAITING của hàng đợi
                    |--------------------------------------------------------------------------
                    */

                    $waitingQueueStatus =
                        QueueStatus::query()
                            ->where(
                                'name',
                                'WAITING'
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->first();

                    if (!$waitingQueueStatus) {
                        throw new \RuntimeException(
                            'Không tìm thấy trạng thái hàng đợi WAITING đang hoạt động.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Phải có phòng khám để xếp số hàng đợi
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$medicalVisit
                            ->examination_room_id
                    ) {
                        throw new \RuntimeException(
                            'Lượt khám chưa có phòng khám nên không thể tạo hàng đợi quay lại.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Sinh số thứ tự theo cùng phòng + cùng ngày
                    |
                    | Giữ đúng quy tắc đang dùng lúc check-in ban đầu.
                    |--------------------------------------------------------------------------
                    */

                    $lastQueueNumber =
                        ExaminationRoomQueue::query()
                            ->whereDate(
                                'queued_at',
                                now()->toDateString()
                            )
                            ->whereHas(
                                'medicalVisit',
                                function ($query) use (
                                    $medicalVisit
                                ) {
                                    $query->where(
                                        'examination_room_id',
                                        $medicalVisit
                                            ->examination_room_id
                                    );
                                }
                            )
                            ->orderByDesc(
                                'queue_number'
                            )
                            ->lockForUpdate()
                            ->value(
                                'queue_number'
                            );

                    $nextQueueNumber =
                        ((int) $lastQueueNumber)
                        + 1;

                    /*
                    |--------------------------------------------------------------------------
                    | Tạo hàng đợi quay lại sau dịch vụ
                    |--------------------------------------------------------------------------
                    */

                    ExaminationRoomQueue::create([
                        'medical_visit_id' =>
                            $medicalVisitId,

                        'queue_status_id' =>
                            $waitingQueueStatus->id,

                        'queue_number' =>
                            $nextQueueNumber,

                        'queue_type' =>
                            'RETURN_AFTER_SERVICE',

                        'queued_at' =>
                            now(),

                        'called_at' =>
                            null,

                        'service_started_at' =>
                            null,

                        'completed_at' =>
                            null,

                        'call_count' =>
                            0,

                        'notes' =>
                            'Quay lại bác sĩ sau khi hoàn tất các dịch vụ chỉ định.',
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Không đổi MedicalVisit sang RETURNING tại đây.
                    |
                    | ExaminationRoomQueueController::startService()
                    | sẽ đổi sang RETURNING khi hàng đợi này được
                    | gọi và bắt đầu phục vụ.
                    |--------------------------------------------------------------------------
                    */
                }
            );
        } catch (\RuntimeException $exception) {
            return back()->with(
                'error',
                $exception->getMessage()
            );
        }

        $serviceOrderItem->refresh();

        $serviceOrderItem->load([
            'serviceStatus',
            'result',
        ]);

        if (
            $serviceOrderItem
                ->serviceStatus
                ?->name
            !==
            'COMPLETED'
        ) {
            return back()->with(
                'error',
                'Không thể hoàn tất dịch vụ. Vui lòng kiểm tra trạng thái và kết quả dịch vụ.'
            );
        }

        return redirect()
            ->route(
                'admin.service-orders.show',
                $serviceOrderItem
                    ->service_order_id
            )
            ->with(
                'success',
                'Đã hoàn tất dịch vụ. Nếu tất cả dịch vụ của lượt khám đã hoàn thành, bệnh nhân đã được đưa vào hàng đợi quay lại bác sĩ.'
            );
    }


    /**
     * Ghi nhật ký thao tác.
     */
    private function writeAuditLog(
        Request $request,
        ServiceOrderItem $serviceOrderItem,
        array $oldValues,
        array $newValues,
        string $descriptionPrefix
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

        $orderCode =
            $serviceOrderItem
                ->serviceOrder
                ?->service_order_code;

        $serviceCode =
            $serviceOrderItem
                ->medicalService
                ?->service_code;

        $description =
            $descriptionPrefix
            . (
                $serviceCode
                    ? ' '
                        . $serviceCode
                    : ''
            )
            . (
                $orderCode
                    ? ' của phiếu '
                        . $orderCode
                    : ''
            )
            . '.';

        DB::table('audit_logs')
            ->insert([
                'user_id' =>
                    Auth::id(),

                'role_name_snapshot' =>
                    $roleNameSnapshot,

                'action' =>
                    'STATUS_CHANGE',

                'entity_type' =>
                    'service_order_items',

                'entity_id' =>
                    $serviceOrderItem->id,

                'entity_business_code' =>
                    null,

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
                    $request
                        ->userAgent(),

                'performed_at' =>
                    now(),

                'created_at' =>
                    now(),
            ]);
    }
}