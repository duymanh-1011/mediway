<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceOrderRequest;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     * Form tạo phiếu chỉ định dịch vụ cho một lượt khám.
     */
    public function create(MedicalVisit $visit)
    {
        $visit->load([
            'patient',
            'doctor',
            'specialty',
            'examinationRoom',
            'status',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Không cho chỉ định khi lượt khám đã hoàn tất
        |--------------------------------------------------------------------------
        */
        if ($visit->status?->name === 'COMPLETED') {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Lượt khám đã hoàn tất, không thể tạo thêm chỉ định dịch vụ.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Lượt khám phải có bác sĩ
        |--------------------------------------------------------------------------
        */
        if (!$visit->doctor_id) {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Lượt khám chưa có bác sĩ phụ trách.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chỉ lấy dịch vụ đang hoạt động
        |--------------------------------------------------------------------------
        */
        $services = MedicalService::query()
            ->where(
                'status',
                'ACTIVE'
            )
            ->with('serviceGroup')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view(
            'admin.service-orders.create',
            compact(
                'visit',
                'services'
            )
        );
    }

    /**
     * Lưu phiếu chỉ định dịch vụ.
     */
    public function store(
        StoreServiceOrderRequest $request,
        MedicalVisit $visit
    ) {
        $visit->load([
            'doctor',
            'status',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra trạng thái lượt khám
        |--------------------------------------------------------------------------
        */
        if ($visit->status?->name === 'COMPLETED') {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Lượt khám đã hoàn tất, không thể tạo thêm chỉ định dịch vụ.'
                );
        }

        if (!$visit->doctor_id) {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Lượt khám chưa có bác sĩ phụ trách.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Trạng thái mặc định của từng dịch vụ
        |--------------------------------------------------------------------------
        */
        $pendingStatus = ServiceStatus::query()
            ->where(
                'name',
                'PENDING'
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$pendingStatus) {
            throw ValidationException::withMessages([
                'medical_service_ids' =>
                    'Không tìm thấy trạng thái dịch vụ PENDING đang hoạt động.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Lấy các dịch vụ người dùng chọn
        |--------------------------------------------------------------------------
        */
        $selectedServiceIds = collect(
            $request->validated('medical_service_ids')
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        $services = MedicalService::query()
            ->whereIn(
                'id',
                $selectedServiceIds
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->get()
            ->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | Không cho dùng dịch vụ đã ngừng hoạt động
        |--------------------------------------------------------------------------
        */
        if ($services->count() !== $selectedServiceIds->count()) {
            throw ValidationException::withMessages([
                'medical_service_ids' =>
                    'Có dịch vụ không tồn tại hoặc đã ngừng hoạt động.',
            ]);
        }

        $order = DB::transaction(
            function () use (
                $request,
                $visit,
                $pendingStatus,
                $selectedServiceIds,
                $services
            ) {
                /*
                |--------------------------------------------------------------------------
                | Sinh mã phiếu CDxxxxxx
                |--------------------------------------------------------------------------
                */
                $lastOrder = ServiceOrder::query()
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $nextNumber = 1;

                if ($lastOrder) {
                    $numericPart = (int) preg_replace(
                        '/\D/',
                        '',
                        $lastOrder->service_order_code
                    );

                    $nextNumber = $numericPart + 1;
                }

                do {
                    $serviceOrderCode =
                        'CD'
                        . str_pad(
                            (string) $nextNumber,
                            6,
                            '0',
                            STR_PAD_LEFT
                        );

                    $exists = ServiceOrder::query()
                        ->where(
                            'service_order_code',
                            $serviceOrderCode
                        )
                        ->exists();

                    $nextNumber++;
                } while ($exists);

                /*
                |--------------------------------------------------------------------------
                | Tạo phiếu chỉ định
                |--------------------------------------------------------------------------
                */
                $order = ServiceOrder::create([
                    'service_order_code' =>
                        $serviceOrderCode,

                    'medical_visit_id' =>
                        $visit->id,

                    'ordered_by_doctor_id' =>
                        $visit->doctor_id,

                    'ordered_at' =>
                        now(),

                    'reason' =>
                        $request->validated('reason'),

                    'instructions' =>
                        $request->validated('instructions'),

                    'status' =>
                        'ACTIVE',

                    'order_pdf_path' =>
                        null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Tạo từng dịch vụ trong phiếu
                |--------------------------------------------------------------------------
                */
                foreach ($selectedServiceIds as $serviceId) {
                    $service = $services->get(
                        $serviceId
                    );

                    ServiceOrderItem::create([
                        'service_order_id' =>
                            $order->id,

                        'medical_service_id' =>
                            $service->id,

                        'service_status_id' =>
                            $pendingStatus->id,

                        /*
                        | Snapshot giá tại thời điểm chỉ định.
                        */
                        'price_at_order' =>
                            $service->price,

                        'started_at' =>
                            null,

                        'completed_at' =>
                            null,

                        'performed_by_employee_id' =>
                            null,

                        'execution_notes' =>
                            null,
                    ]);
                }

                return $order;
            }
        );

        return redirect()
            ->route(
                'admin.service-orders.show',
                $order->id
            )
            ->with(
                'success',
                'Tạo phiếu chỉ định dịch vụ thành công.'
            );
    }

    /**
     * Chi tiết phiếu chỉ định.
     */
    public function show(int $id)
    {
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
                ->keyBy(
                    'service_order_item_id'
                );
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