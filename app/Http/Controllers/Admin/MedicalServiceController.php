<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMedicalServiceRequest;
use App\Http\Requests\Admin\UpdateMedicalServiceRequest;
use App\Models\MedicalService;
use App\Models\ServiceGroup;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalServiceController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $serviceGroupId =
            $request->get('service_group_id');

        $status =
            $request->get('status');

        $minPrice =
            $request->get('min_price');

        $maxPrice =
            $request->get('max_price');

        $query =
            MedicalService::query()
                ->with('serviceGroup');

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'service_code',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'name',
                        'like',
                        "%{$keyword}%"
                    );
                }
            );
        }

        if (!empty($serviceGroupId)) {

            $query->where(
                'service_group_id',
                $serviceGroupId
            );
        }

        if (
            in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        if (
            $minPrice !== null
            && $minPrice !== ''
        ) {
            $query->where(
                'price',
                '>=',
                $minPrice
            );
        }

        if (
            $maxPrice !== null
            && $maxPrice !== ''
        ) {
            $query->where(
                'price',
                '<=',
                $maxPrice
            );
        }

        $medicalServices =
            $query
                ->orderBy('display_order')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString();

        $serviceGroups =
            ServiceGroup::query()
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();

        $totalServices =
            MedicalService::count();

        $activeServices =
            MedicalService::where(
                'status',
                'ACTIVE'
            )->count();

        $inactiveServices =
            MedicalService::where(
                'status',
                'INACTIVE'
            )->count();

        return view(
            'admin.medical-services.index',
            compact(
                'medicalServices',
                'serviceGroups',
                'keyword',
                'serviceGroupId',
                'status',
                'minPrice',
                'maxPrice',
                'totalServices',
                'activeServices',
                'inactiveServices'
            )
        );
    }

    public function create()
    {
        $serviceGroups =
            ServiceGroup::query()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();

        return view(
            'admin.medical-services.create',
            compact('serviceGroups')
        );
    }

    public function store(
        StoreMedicalServiceRequest $request
    ) {
        $medicalService =
            MedicalService::create(
                $request->validated()
            );

        return redirect()
            ->route(
                'admin.medical-services.show',
                $medicalService
            )
            ->with(
                'success',
                'Thêm dịch vụ y tế thành công.'
            );
    }

    public function show(
        MedicalService $medicalService
    ) {
        $medicalService->load(
            'serviceGroup'
        );

        /*
        |--------------------------------------------------------------------------
        | Số lần dịch vụ đã được chỉ định
        |--------------------------------------------------------------------------
        */

        $serviceOrderItemCount =
            DB::table('service_order_items')
                ->where(
                    'medical_service_id',
                    $medicalService->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Tổng doanh thu lý thuyết theo giá snapshot của order item
        |--------------------------------------------------------------------------
        */

        $orderedValue =
            DB::table('service_order_items')
                ->where(
                    'medical_service_id',
                    $medicalService->id
                )
                ->sum('price_at_order');

        /*
        |--------------------------------------------------------------------------
        | Các lần chỉ định gần đây
        |--------------------------------------------------------------------------
        */

        $recentOrderItems =
            DB::table('service_order_items')
                ->join(
                    'service_orders',
                    'service_order_items.service_order_id',
                    '=',
                    'service_orders.id'
                )
                ->join(
                    'medical_visits',
                    'service_orders.medical_visit_id',
                    '=',
                    'medical_visits.id'
                )
                ->join(
                    'patients',
                    'medical_visits.patient_id',
                    '=',
                    'patients.id'
                )
                ->join(
                    'service_statuses',
                    'service_order_items.service_status_id',
                    '=',
                    'service_statuses.id'
                )
                ->where(
                    'service_order_items.medical_service_id',
                    $medicalService->id
                )
                ->select([
                    'service_order_items.id',
                    'service_order_items.price_at_order',
                    'service_order_items.started_at',
                    'service_order_items.completed_at',

                    'service_orders.service_order_code',
                    'service_orders.ordered_at',

                    'medical_visits.visit_code',

                    'patients.patient_code',
                    'patients.full_name as patient_name',

                    'service_statuses.name as service_status',
                ])
                ->orderByDesc(
                    'service_orders.ordered_at'
                )
                ->limit(10)
                ->get();

        return view(
            'admin.medical-services.show',
            compact(
                'medicalService',
                'serviceOrderItemCount',
                'orderedValue',
                'recentOrderItems'
            )
        );
    }

    public function edit(
        MedicalService $medicalService
    ) {
        $serviceGroups =
            ServiceGroup::query()
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();

        return view(
            'admin.medical-services.edit',
            compact(
                'medicalService',
                'serviceGroups'
            )
        );
    }

    public function update(
        UpdateMedicalServiceRequest $request,
        MedicalService $medicalService
    ) {
        $medicalService->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'admin.medical-services.show',
                $medicalService
            )
            ->with(
                'success',
                'Cập nhật dịch vụ y tế thành công.'
            );
    }

    public function destroy(
        MedicalService $medicalService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Đã được chỉ định?
        |--------------------------------------------------------------------------
        */

        $hasOrderItems =
            DB::table('service_order_items')
                ->where(
                    'medical_service_id',
                    $medicalService->id
                )
                ->exists();

        if ($hasOrderItems) {

            $medicalService->update([
                'status' => 'INACTIVE',
            ]);

            return redirect()
                ->route(
                    'admin.medical-services.index'
                )
                ->with(
                    'success',
                    'Dịch vụ đã được sử dụng trong chỉ định nên không thể xóa. Hệ thống đã chuyển dịch vụ sang trạng thái INACTIVE.'
                );
        }

        $medicalService->delete();

        return redirect()
            ->route(
                'admin.medical-services.index'
            )
            ->with(
                'success',
                'Xóa dịch vụ y tế thành công.'
            );
    }
}