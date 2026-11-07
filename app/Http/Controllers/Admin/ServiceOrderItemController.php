<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalVisit;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderProgress;
use App\Models\ServiceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $serviceOrderItem->serviceOrder->medicalVisit->assertServiceDataWritable();
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

        if (! $serviceOrderItem->serviceStatus->allows_transition) {
            return back()->with('error', 'Dịch vụ đang bị khóa thay đổi trạng thái. Vui lòng kiểm tra mục Trạng thái dịch vụ.');
        }

        if (! $payable) {
            return back()->with(
                'error',
                'Chưa có khoản thu cho dịch vụ này nên chưa thể bắt đầu. Vui lòng kiểm tra mục Khoản phải thanh toán.'
            );
        }

        $paymentStatus =
            $payable->paymentStatus;

        if (! $paymentStatus) {
            return back()->with(
                'error',
                'Trạng thái khoản thu chưa được cấu hình đúng. Vui lòng kiểm tra mục Trạng thái thanh toán.'
            );
        }

        if (! $paymentStatus->allows_service_execution) {
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

        if (! $processingStatus) {
            return back()->with(
                'error',
                'Chưa có trạng thái “Đang thực hiện” đang được sử dụng. Vui lòng kiểm tra mục Trạng thái dịch vụ.'
            );
        }

        DB::transaction(
            function () use (
                $request,
                $serviceOrderItem,
                $processingStatus
            ) {
                $visit = MedicalVisit::with('status')->whereKey($serviceOrderItem->serviceOrder->medical_visit_id)->lockForUpdate()->firstOrFail();
                $visit->assertServiceDataWritable();
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

                $currentStatus = ServiceStatus::whereKey($lockedItem->service_status_id)->lockForUpdate()->firstOrFail();
                if (! $currentStatus->allows_transition) {
                    throw ValidationException::withMessages(['service_status_id' => 'Dịch vụ đang bị khóa thay đổi trạng thái. Vui lòng kiểm tra mục Trạng thái dịch vụ.']);
                }

                if (
                    ! $lockedPayable
                    ||
                    ! $lockedPayable
                        ->paymentStatus
                    ||
                    ! $lockedPayable
                        ->paymentStatus
                        ->allows_service_execution
                ) {
                    return;
                }

                $oldValues = [
                    'service_status_id' => $lockedItem
                        ->service_status_id,

                    'service_status' => $lockedItem
                        ->serviceStatus
                        ?->name,

                    'started_at' => $lockedItem
                        ->started_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),
                ];

                $startedAt =
                    $lockedItem->started_at
                    ?? now();

                $lockedItem->update([
                    'service_status_id' => $processingStatus->id,

                    'started_at' => $startedAt,
                ]);

                $newValues = [
                    'service_status_id' => $processingStatus->id,

                    'service_status' => 'PROCESSING',

                    'started_at' => $startedAt->format(
                        'Y-m-d H:i:s'
                    ),
                ];

                app(ServiceOrderProgress::class)->auditItemTransition(
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
     * Khi tất cả dịch vụ chưa hủy của lượt khám hoàn thành:
     * - Tạo hàng đợi RETURN_AFTER_SERVICE / WAITING.
     * - MedicalVisit vẫn giữ WAITING_FOR_SERVICE.
     */
    public function complete(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ): RedirectResponse {
        $serviceOrderItem->serviceOrder->medicalVisit->assertServiceDataWritable();
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

        if (! $serviceOrderItem->serviceStatus->allows_transition) {
            return back()->with('error', 'Dịch vụ đang bị khóa thay đổi trạng thái. Vui lòng kiểm tra mục Trạng thái dịch vụ.');
        }

        if (! $serviceOrderItem->result?->hasContent()) {
            return back()->with(
                'error',
                'Chưa thể hoàn tất dịch vụ. Vui lòng nhập nội dung kết quả hoặc tải lên tệp PDF/ảnh kết quả hợp lệ.'
            );
        }

        try {
            DB::transaction(
                function () use (
                    $request,
                    $serviceOrderItem
                ) {
                    $visit = MedicalVisit::with('status')->whereKey($serviceOrderItem->serviceOrder->medical_visit_id)->lockForUpdate()->firstOrFail();
                    $visit->assertServiceDataWritable();
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

                    if (! $lockedItem->result?->hasContent()) {
                        return;
                    }

                    app(ServiceOrderProgress::class)->completeAdminItem($request, $lockedItem, $lockedItem->completed_at ?? now());
                }
            );
        } catch (\RuntimeException|ValidationException $exception) {
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
                'Chưa thể hoàn tất dịch vụ. Vui lòng tải lại trang, kiểm tra dịch vụ đang được thực hiện và đã có kết quả.'
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
                'Đã hoàn tất dịch vụ. '.app(ServiceOrderProgress::class)->returnQueueMessage($serviceOrderItem->serviceOrder->medical_visit_id)
            );
    }
}
