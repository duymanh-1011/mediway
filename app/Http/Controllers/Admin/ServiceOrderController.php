<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceOrderController extends Controller
{
    /**
     * Danh sách phiếu chỉ định dịch vụ.
     */
    public function index(Request $request)
    {
        $query = ServiceOrder::query()
            ->with([
                'medicalVisit:id,visit_code',
                'orderedByDoctor:id,doctor_code,full_name',
            ])
            ->withCount('items');

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        |
        | Tìm theo:
        | - Mã phiếu chỉ định
        | - Mã lượt khám
        | - Mã bác sĩ
        | - Tên bác sĩ
        |
        */
        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'service_order_code',
                        'like',
                        '%' . $keyword . '%'
                    )

                    ->orWhereHas(
                        'medicalVisit',
                        function ($visitQuery) use ($keyword) {
                            $visitQuery->where(
                                'visit_code',
                                'like',
                                '%' . $keyword . '%'
                            );
                        }
                    )

                    ->orWhereHas(
                        'orderedByDoctor',
                        function ($doctorQuery) use ($keyword) {
                            $doctorQuery
                                ->where(
                                    'doctor_code',
                                    'like',
                                    '%' . $keyword . '%'
                                )
                                ->orWhere(
                                    'full_name',
                                    'like',
                                    '%' . $keyword . '%'
                                );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái phiếu
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc ngày chỉ định
        |--------------------------------------------------------------------------
        */
        if ($request->filled('date_from')) {
            $query->whereDate(
                'ordered_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'ordered_at',
                '<=',
                $request->date_to
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Danh sách trạng thái hiện có
        |--------------------------------------------------------------------------
        |
        | Lấy trực tiếp từ DB để không tự hard-code thêm trạng thái.
        |
        */
        $orderStatuses = ServiceOrder::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        /*
        |--------------------------------------------------------------------------
        | Phân trang
        |--------------------------------------------------------------------------
        */
        $orders = $query
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.service-orders.index',
            compact(
                'orders',
                'orderStatuses'
            )
        );
    }


    /**
     * Chi tiết phiếu chỉ định.
     */
    public function show(int $id)
    {
        /*
        |--------------------------------------------------------------------------
        | Phiếu + dữ liệu liên quan
        |--------------------------------------------------------------------------
        |
        | Work yêu cầu detail phải có:
        | - item
        | - service
        | - price_at_order
        | - status
        | - performer
        | - timestamps
        | - result
        | - payable
        |
        */
        $order = ServiceOrder::query()
            ->with([
                'medicalVisit',

                'orderedByDoctor',

                'items' => function ($itemQuery) {
                    $itemQuery->orderBy('id');
                },

                'items.medicalService:id,service_code,name',

                'items.serviceStatus:id,name,description',

                'items.performer:id,employee_code,full_name',

                'items.result',

                'items.result.verifier:id,business_code',
            ])
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Khoản phải thanh toán liên quan
        |--------------------------------------------------------------------------
        |
        | Hiện chưa tạo Payable model vì module Payable làm vào Thứ Sáu.
        | Nhưng Work yêu cầu Service Order detail phải liên kết payable.
        |
        | payables.service_order_item_id là FK + UNIQUE và có thể NULL.
        | Vì vậy tạm đọc trực tiếp bằng Query Builder.
        |
        */
        $itemIds = $order->items
            ->pluck('id')
            ->filter()
            ->values();

        $payablesByItem = collect();

        if ($itemIds->isNotEmpty()) {
            $payablesByItem = DB::table('payables')
                ->leftJoin(
                    'payment_statuses',
                    'payment_statuses.id',
                    '=',
                    'payables.payment_status_id'
                )
                ->whereIn(
                    'payables.service_order_item_id',
                    $itemIds
                )
                ->select([
                    'payables.*',
                    'payment_statuses.name as payment_status_name',
                ])
                ->get()
                ->keyBy('service_order_item_id');
        }

        return view(
            'admin.service-orders.show',
            compact(
                'order',
                'payablesByItem'
            )
        );
    }
}
