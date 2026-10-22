<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceOrderRequest;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceStatus;
use App\Models\VisitStatus;
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

        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'service_order_code',
                        'like',
                        '%'.$keyword.'%'
                    )
                    ->orWhereHas(
                        'medicalVisit',
                        function ($visitQuery) use ($keyword) {
                            $visitQuery->where(
                                'visit_code',
                                'like',
                                '%'.$keyword.'%'
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
                                    '%'.$keyword.'%'
                                )
                                ->orWhere(
                                    'full_name',
                                    'like',
                                    '%'.$keyword.'%'
                                );
                        }
                    );
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

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

        $orderStatuses = ServiceOrder::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

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
     * Form tạo phiếu chỉ định dịch vụ.
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

        if (! in_array($visit->status?->name, ['IN_EXAMINATION', 'RETURNING'], true)) {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Chỉ được chỉ định dịch vụ khi đang khám hoặc đang khám lại sau dịch vụ.'
                );
        }

        if (! $visit->doctor_id) {
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

        $services = MedicalService::query()
            ->where(
                'status',
                'ACTIVE'
            )
            ->with('serviceGroups')
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

        if (! in_array($visit->status?->name, ['IN_EXAMINATION', 'RETURNING'], true)) {
            return redirect()
                ->route(
                    'admin.visits.show',
                    $visit
                )
                ->with(
                    'error',
                    'Chỉ được chỉ định dịch vụ khi đang khám hoặc đang khám lại sau dịch vụ.'
                );
        }

        if (! $visit->doctor_id) {
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

        $pendingServiceStatus = ServiceStatus::query()
            ->where(
                'name',
                'PENDING'
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (! $pendingServiceStatus) {
            throw ValidationException::withMessages([
                'medical_service_ids' => 'Không tìm thấy trạng thái dịch vụ PENDING đang hoạt động.',
            ]);
        }

        $unpaidPaymentStatus = PaymentStatus::query()
            ->where(
                'name',
                'UNPAID'
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (! $unpaidPaymentStatus) {
            throw ValidationException::withMessages([
                'medical_service_ids' => 'Không tìm thấy trạng thái thanh toán UNPAID đang hoạt động.',
            ]);
        }

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

        if (
            $services->count()
            !==
            $selectedServiceIds->count()
        ) {
            throw ValidationException::withMessages([
                'medical_service_ids' => 'Có dịch vụ không tồn tại hoặc đã ngừng hoạt động.',
            ]);
        }

        $order = DB::transaction(
            function () use (
                $request,
                $visit,
                $pendingServiceStatus,
                $unpaidPaymentStatus,
                $selectedServiceIds,
                $services
            ) {
                $visit = MedicalVisit::with('status')->whereKey($visit->id)->lockForUpdate()->firstOrFail();
                if (! in_array($visit->status?->name, ['IN_EXAMINATION', 'RETURNING'], true) || ! $visit->status?->allows_transition) {
                    throw ValidationException::withMessages(['medical_service_ids' => 'Lượt khám không còn ở giai đoạn cho phép chỉ định dịch vụ.']);
                }
                if (! VisitStatus::where('name', 'WAITING_FOR_SERVICE')->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages(['medical_service_ids' => 'Chưa cấu hình trạng thái chờ thực hiện dịch vụ đang hoạt động.']);
                }
                $services = MedicalService::whereIn('id', $selectedServiceIds)->where('status', 'ACTIVE')->lockForUpdate()->get()->keyBy('id');
                if ($services->count() !== $selectedServiceIds->count()) {
                    throw ValidationException::withMessages(['medical_service_ids' => 'Có dịch vụ vừa ngừng hoạt động. Vui lòng chọn lại.']);
                }
                /*
                |--------------------------------------------------------------------------
                | Sinh mã phiếu chỉ định
                |--------------------------------------------------------------------------
                */
                $lastOrder = ServiceOrder::query()
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $nextOrderNumber = 1;

                if ($lastOrder) {
                    $numericPart = (int) preg_replace(
                        '/\D/',
                        '',
                        $lastOrder->service_order_code
                    );

                    $nextOrderNumber =
                        $numericPart + 1;
                }

                do {
                    $serviceOrderCode =
                        'CD'
                        .str_pad(
                            (string) $nextOrderNumber,
                            6,
                            '0',
                            STR_PAD_LEFT
                        );

                    $orderExists = ServiceOrder::query()
                        ->where(
                            'service_order_code',
                            $serviceOrderCode
                        )
                        ->exists();

                    $nextOrderNumber++;
                } while ($orderExists);

                /*
                |--------------------------------------------------------------------------
                | Sinh số payable bắt đầu
                |--------------------------------------------------------------------------
                */
                $lastPayable = Payable::query()
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $nextPayableNumber = 1;

                if ($lastPayable) {
                    $numericPart = (int) preg_replace(
                        '/\D/',
                        '',
                        $lastPayable->payable_code
                    );

                    $nextPayableNumber =
                        $numericPart + 1;
                }

                /*
                |--------------------------------------------------------------------------
                | Tạo phiếu chỉ định
                |--------------------------------------------------------------------------
                */
                $order = ServiceOrder::create([
                    'service_order_code' => $serviceOrderCode,

                    'medical_visit_id' => $visit->id,

                    'ordered_by_doctor_id' => $visit->doctor_id,

                    'ordered_at' => now(),

                    'reason' => $request->validated('reason'),

                    'instructions' => $request->validated('instructions'),

                    'status' => 'ACTIVE',

                    'order_pdf_path' => null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Tạo từng dịch vụ + payable
                |--------------------------------------------------------------------------
                */
                foreach (
                    $selectedServiceIds as $serviceId
                ) {
                    $service = $services->get(
                        $serviceId
                    );

                    $item = ServiceOrderItem::create([
                        'service_order_id' => $order->id,

                        'medical_service_id' => $service->id,

                        'service_status_id' => $pendingServiceStatus->id,

                        'price_at_order' => $service->price,

                        'started_at' => null,

                        'completed_at' => null,

                        'performed_by_employee_id' => null,

                        'execution_notes' => null,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Sinh mã khoản phải thu
                    |--------------------------------------------------------------------------
                    */
                    do {
                        $payableCode =
                            'PT'
                            .str_pad(
                                (string) $nextPayableNumber,
                                6,
                                '0',
                                STR_PAD_LEFT
                            );

                        $payableExists =
                            Payable::query()
                                ->where(
                                    'payable_code',
                                    $payableCode
                                )
                                ->exists();

                        $nextPayableNumber++;
                    } while ($payableExists);

                    /*
                    |--------------------------------------------------------------------------
                    | Tạo khoản phải thanh toán
                    |--------------------------------------------------------------------------
                    */
                    Payable::create([
                        'payable_code' => $payableCode,

                        'medical_visit_id' => $visit->id,

                        'service_order_item_id' => $item->id,

                        'payment_type' => 'SERVICE_FEE',

                        'description' => 'Phí dịch vụ '
                            .$service->name
                            .' - '
                            .$service->service_code,

                        'amount' => $item->price_at_order,

                        'payment_status_id' => $unpaidPaymentStatus->id,

                        'due_at' => null,

                        'payment_qr_data' => null,

                        'generated_at' => now(),

                        'paid_at' => null,

                        'notes' => null,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Chuyển lượt khám sang chờ dịch vụ
                |--------------------------------------------------------------------------
                */
                if (
                    in_array(
                        $visit->status?->name,
                        [
                            'IN_EXAMINATION',
                            'RETURNING',
                        ],
                        true
                    )
                ) {
                    $waitingForServiceStatus =
                        DB::table('visit_statuses')
                            ->where(
                                'name',
                                'WAITING_FOR_SERVICE'
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->first();

                    if ($waitingForServiceStatus) {
                        $visit->update([
                            'visit_status_id' => $waitingForServiceStatus->id,
                        ]);
                    }
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
                'Tạo phiếu chỉ định và khoản phải thanh toán thành công.'
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
                    'payment_statuses.allows_service_execution',
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
