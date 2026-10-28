<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDiseaseGroupRequest;
use App\Http\Requests\Admin\UpdateDiseaseGroupRequest;
use App\Models\DiseaseGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiseaseGroupController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $status =
            $request->get('status');

        $query =
            DiseaseGroup::query();

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'disease_group_code',
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
        | Trạng thái
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
        | Đếm số bệnh trong từng nhóm
        |--------------------------------------------------------------------------
        */

        $query->select('disease_groups.*')
            ->selectSub(
                function ($subQuery) {

                    $subQuery
                        ->from('diseases')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn(
                            'diseases.disease_group_id',
                            'disease_groups.id'
                        );
                },
                'diseases_count'
            );

        $diseaseGroups =
            $query
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Thống kê
        |--------------------------------------------------------------------------
        */

        $totalDiseaseGroups =
            DiseaseGroup::count();

        $activeDiseaseGroups =
            DiseaseGroup::where(
                'status',
                'ACTIVE'
            )->count();

        $inactiveDiseaseGroups =
            DiseaseGroup::where(
                'status',
                'INACTIVE'
            )->count();

        return view(
            'admin.disease-groups.index',
            compact(
                'diseaseGroups',
                'keyword',
                'status',
                'totalDiseaseGroups',
                'activeDiseaseGroups',
                'inactiveDiseaseGroups'
            )
        );
    }

    public function create()
    {
        return view(
            'admin.disease-groups.create'
        );
    }

    public function store(
        StoreDiseaseGroupRequest $request
    ) {
        $diseaseGroup =
            DiseaseGroup::create(
                $request->validated()
            );

        return redirect()
            ->route(
                'admin.disease-groups.show',
                $diseaseGroup
            )
            ->with(
                'success',
                'Thêm nhóm bệnh thành công.'
            );
    }

    public function show(
        Request $request,
        DiseaseGroup $diseaseGroup
    ): View {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
        ]);
        $keyword = trim($filters['keyword'] ?? '');
        $status = $filters['status'] ?? null;

        $totalDiseases =
            DB::table('diseases')
                ->where(
                    'disease_group_id',
                    $diseaseGroup->id
                )
                ->count();

        $activeDiseases =
            DB::table('diseases')
                ->where(
                    'disease_group_id',
                    $diseaseGroup->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count();

        $inactiveDiseases =
            DB::table('diseases')
                ->where(
                    'disease_group_id',
                    $diseaseGroup->id
                )
                ->where(
                    'status',
                    'INACTIVE'
                )
                ->count();

        $diseases =
            DB::table('diseases')
                ->where(
                    'disease_group_id',
                    $diseaseGroup->id
                )
                ->select([
                    'id',
                    'disease_code',
                    'name',
                    'alternative_name',
                    'common_symptoms',
                    'status',
                ])
                ->when($keyword !== '', fn (Builder $query): Builder => $query->where(
                    fn (Builder $search): Builder => $search->where('disease_code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('alternative_name', 'like', "%{$keyword}%")
                ))
                ->when($status, fn (Builder $query): Builder => $query->where('status', $status))
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString();

        return view(
            'admin.disease-groups.show',
            compact(
                'diseaseGroup',
                'totalDiseases',
                'activeDiseases',
                'inactiveDiseases',
                'diseases'
            )
        );
    }

    public function edit(
        DiseaseGroup $diseaseGroup
    ) {
        return view(
            'admin.disease-groups.edit',
            compact('diseaseGroup')
        );
    }

    public function update(
        UpdateDiseaseGroupRequest $request,
        DiseaseGroup $diseaseGroup
    ) {
        $diseaseGroup->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'admin.disease-groups.show',
                $diseaseGroup
            )
            ->with(
                'success',
                'Cập nhật nhóm bệnh thành công.'
            );
    }

    public function destroy(
        DiseaseGroup $diseaseGroup
    ) {
        /*
        |--------------------------------------------------------------------------
        | Nhóm đã có bệnh
        |--------------------------------------------------------------------------
        */

        $hasDiseases =
            DB::table('diseases')
                ->where(
                    'disease_group_id',
                    $diseaseGroup->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Đã được dùng -> INACTIVE
        |--------------------------------------------------------------------------
        */

        if ($hasDiseases) {

            $diseaseGroup->update([
                'status' => 'INACTIVE',
            ]);

            return redirect()
                ->route(
                    'admin.disease-groups.index'
                )
                ->with(
                    'success',
                    'Nhóm đang có bệnh nên không thể xóa. Đã ngừng hoạt động nhóm và giữ nguyên các bệnh trong nhóm.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chưa sử dụng -> hard delete
        |--------------------------------------------------------------------------
        */

        $diseaseGroup->delete();

        return redirect()
            ->route(
                'admin.disease-groups.index'
            )
            ->with(
                'success',
                'Xóa nhóm bệnh thành công.'
            );
    }
}
