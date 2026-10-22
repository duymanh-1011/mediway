<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDiseaseRequest;
use App\Http\Requests\Admin\UpdateDiseaseRequest;
use App\Models\Disease;
use App\Models\DiseaseGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiseaseController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $diseaseGroupId =
            $request->get('disease_group_id');

        $status =
            $request->get('status');

        $query =
            Disease::query()
                ->with('diseaseGroup');

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'disease_code',
                        'like',
                        "%{$keyword}%"
                    )
                        ->orWhere(
                            'name',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'alternative_name',
                            'like',
                            "%{$keyword}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nhóm bệnh
        |--------------------------------------------------------------------------
        */

        if (! empty($diseaseGroupId)) {

            $query->where(
                'disease_group_id',
                $diseaseGroupId
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
        | Danh sách
        |--------------------------------------------------------------------------
        */

        $diseases =
            $query
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Nhóm bệnh dùng cho filter
        |--------------------------------------------------------------------------
        */

        $diseaseGroups =
            DiseaseGroup::query()
                ->orderBy('name')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Thống kê
        |--------------------------------------------------------------------------
        */

        $totalDiseases =
            Disease::count();

        $activeDiseases =
            Disease::where(
                'status',
                'ACTIVE'
            )->count();

        $inactiveDiseases =
            Disease::where(
                'status',
                'INACTIVE'
            )->count();

        return view(
            'admin.diseases.index',
            compact(
                'diseases',
                'diseaseGroups',
                'keyword',
                'diseaseGroupId',
                'status',
                'totalDiseases',
                'activeDiseases',
                'inactiveDiseases'
            )
        );
    }

    public function create(Request $request): View
    {
        $diseaseGroups =
            DiseaseGroup::query()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->orderBy('name')
                ->get();

        $selectedDiseaseGroup = $diseaseGroups->firstWhere('id', $request->integer('disease_group_id'));
        $backUrl = $selectedDiseaseGroup
            ? route('admin.disease-groups.show', $selectedDiseaseGroup)
            : route('admin.diseases.index');

        return view(
            'admin.diseases.create',
            compact('diseaseGroups', 'selectedDiseaseGroup', 'backUrl')
        );
    }

    public function store(
        StoreDiseaseRequest $request
    ) {
        $disease =
            Disease::create(
                $request->validated()
            );

        return redirect()
            ->route(
                'admin.diseases.show',
                $disease
            )
            ->with(
                'success',
                'Thêm bệnh thành công.'
            );
    }

    public function show(
        Disease $disease
    ) {
        $disease->load(
            'diseaseGroup'
        );

        /*
        |--------------------------------------------------------------------------
        | Số lần bệnh đã được dùng trong chẩn đoán
        |--------------------------------------------------------------------------
        */

        $diagnosisCount =
            DB::table('visit_diagnoses')
                ->where(
                    'disease_id',
                    $disease->id
                )
                ->count();

        return view(
            'admin.diseases.show',
            compact(
                'disease',
                'diagnosisCount'
            )
        );
    }

    public function edit(
        Disease $disease
    ) {
        $diseaseGroups =
            DiseaseGroup::query()
                ->orderBy('name')
                ->get();

        return view(
            'admin.diseases.edit',
            compact(
                'disease',
                'diseaseGroups'
            )
        );
    }

    public function update(
        UpdateDiseaseRequest $request,
        Disease $disease
    ) {
        $disease->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'admin.diseases.show',
                $disease
            )
            ->with(
                'success',
                'Cập nhật bệnh thành công.'
            );
    }

    public function destroy(
        Disease $disease
    ) {
        /*
        |--------------------------------------------------------------------------
        | Bệnh đã được dùng trong chẩn đoán?
        |--------------------------------------------------------------------------
        */

        $hasDiagnoses =
            DB::table('visit_diagnoses')
                ->where(
                    'disease_id',
                    $disease->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Đã sử dụng -> INACTIVE
        |--------------------------------------------------------------------------
        */

        if ($hasDiagnoses) {

            $disease->update([
                'status' => 'INACTIVE',
            ]);

            return redirect()
                ->route(
                    'admin.diseases.index'
                )
                ->with(
                    'success',
                    'Bệnh đã được sử dụng trong chẩn đoán nên không thể xóa. Hệ thống đã chuyển bệnh sang trạng thái INACTIVE.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chưa sử dụng -> hard delete
        |--------------------------------------------------------------------------
        */

        $disease->delete();

        return redirect()
            ->route(
                'admin.diseases.index'
            )
            ->with(
                'success',
                'Xóa bệnh thành công.'
            );
    }
}
