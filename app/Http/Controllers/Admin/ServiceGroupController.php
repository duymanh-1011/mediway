<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceGroupRequest;
use App\Http\Requests\Admin\UpdateServiceGroupRequest;
use App\Models\ServiceGroup;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceGroupController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $status =
            $request->get('status');

        $query =
            ServiceGroup::query();

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'service_group_code',
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

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Danh sách
        |--------------------------------------------------------------------------
        */

        $serviceGroups =
            $query
                ->withCount('medicalServices')
                ->orderBy('display_order')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Thống kê
        |--------------------------------------------------------------------------
        */

        $totalServiceGroups =
            ServiceGroup::count();

        $activeServiceGroups =
            ServiceGroup::where(
                'status',
                'ACTIVE'
            )->count();

        $inactiveServiceGroups =
            ServiceGroup::where(
                'status',
                'INACTIVE'
            )->count();

        return view(
            'admin.service-groups.index',
            compact(
                'serviceGroups',
                'keyword',
                'status',
                'totalServiceGroups',
                'activeServiceGroups',
                'inactiveServiceGroups'
            )
        );
    }

    public function create()
    {
        return view(
            'admin.service-groups.create'
        );
    }

    public function store(
        StoreServiceGroupRequest $request
    ) {
        $serviceGroup =
            ServiceGroup::create(
                $request->validated()
            );

        return redirect()
            ->route(
                'admin.service-groups.show',
                $serviceGroup
            )
            ->with(
                'success',
                'Thêm nhóm dịch vụ thành công.'
            );
    }

    public function show(
        ServiceGroup $serviceGroup
    ) {
        /*
        |--------------------------------------------------------------------------
        | Tổng dịch vụ thuộc nhóm
        |--------------------------------------------------------------------------
        */

        $totalServices =
            DB::table('medical_services')
                ->where(
                    'service_group_id',
                    $serviceGroup->id
                )
                ->count();

        $activeServices =
            DB::table('medical_services')
                ->where(
                    'service_group_id',
                    $serviceGroup->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count();

        $inactiveServices =
            DB::table('medical_services')
                ->where(
                    'service_group_id',
                    $serviceGroup->id
                )
                ->where(
                    'status',
                    'INACTIVE'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Danh sách dịch vụ
        |--------------------------------------------------------------------------
        */

        $medicalServices =
            DB::table('medical_services')
                ->where(
                    'service_group_id',
                    $serviceGroup->id
                )
                ->select([
                    'id',
                    'service_code',
                    'name',
                    'price',
                    'unit',
                    'estimated_duration_minutes',
                    'display_order',
                    'status',
                ])
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();

        return view(
            'admin.service-groups.show',
            compact(
                'serviceGroup',
                'totalServices',
                'activeServices',
                'inactiveServices',
                'medicalServices'
            )
        );
    }

    public function edit(
        ServiceGroup $serviceGroup
    ) {
        return view(
            'admin.service-groups.edit',
            compact('serviceGroup')
        );
    }

    public function update(
        UpdateServiceGroupRequest $request,
        ServiceGroup $serviceGroup
    ) {
        $serviceGroup->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'admin.service-groups.show',
                $serviceGroup
            )
            ->with(
                'success',
                'Cập nhật nhóm dịch vụ thành công.'
            );
    }

    public function destroy(
        ServiceGroup $serviceGroup
    ) {
        /*
        |--------------------------------------------------------------------------
        | Kiểm tra nhóm đã có dịch vụ
        |--------------------------------------------------------------------------
        */

        $hasServices =
            DB::table('medical_services')
                ->where(
                    'service_group_id',
                    $serviceGroup->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Đã được sử dụng -> INACTIVE
        |--------------------------------------------------------------------------
        */

        if ($hasServices) {

            $serviceGroup->update([
                'status' => 'INACTIVE',
            ]);

            return redirect()
                ->route(
                    'admin.service-groups.index'
                )
                ->with(
                    'success',
                    'Nhóm dịch vụ đang chứa dịch vụ nên không thể xóa. Hệ thống đã chuyển nhóm sang trạng thái INACTIVE.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chưa được sử dụng -> hard delete
        |--------------------------------------------------------------------------
        */

        $serviceGroup->delete();

        return redirect()
            ->route(
                'admin.service-groups.index'
            )
            ->with(
                'success',
                'Xóa nhóm dịch vụ thành công.'
            );
    }
}