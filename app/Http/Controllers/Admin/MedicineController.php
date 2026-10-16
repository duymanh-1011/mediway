<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMedicineRequest;
use App\Http\Requests\Admin\UpdateMedicineRequest;
use App\Models\Medicine;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->get('keyword')
        );

        $status =
            $request->get('status');

        $dosageForm =
            trim(
                (string) $request->get('dosage_form')
            );

        $query =
            Medicine::query();

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($keyword !== '') {

            $query->where(
                function ($q) use ($keyword) {

                    $q->where(
                        'medicine_code',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'name',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'active_ingredient',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'manufacturer',
                        'like',
                        "%{$keyword}%"
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Dạng bào chế
        |--------------------------------------------------------------------------
        */

        if ($dosageForm !== '') {

            $query->where(
                'dosage_form',
                $dosageForm
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

        $medicines =
            $query
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filter dạng bào chế
        |--------------------------------------------------------------------------
        */

        $dosageForms =
            Medicine::query()
                ->whereNotNull('dosage_form')
                ->where('dosage_form', '!=', '')
                ->distinct()
                ->orderBy('dosage_form')
                ->pluck('dosage_form');

        /*
        |--------------------------------------------------------------------------
        | Thống kê
        |--------------------------------------------------------------------------
        */

        $totalMedicines =
            Medicine::count();

        $activeMedicines =
            Medicine::where(
                'status',
                'ACTIVE'
            )->count();

        $inactiveMedicines =
            Medicine::where(
                'status',
                'INACTIVE'
            )->count();

        return view(
            'admin.medicines.index',
            compact(
                'medicines',
                'keyword',
                'status',
                'dosageForm',
                'dosageForms',
                'totalMedicines',
                'activeMedicines',
                'inactiveMedicines'
            )
        );
    }

    public function create()
    {
        return view(
            'admin.medicines.create'
        );
    }

    public function store(
        StoreMedicineRequest $request
    ) {
        $medicine =
            Medicine::create(
                $request->validated()
            );

        return redirect()
            ->route(
                'admin.medicines.show',
                $medicine
            )
            ->with(
                'success',
                'Thêm thuốc thành công.'
            );
    }

    public function show(
        Medicine $medicine
    ) {
        /*
        |--------------------------------------------------------------------------
        | Đã được kê bao nhiêu lần
        |--------------------------------------------------------------------------
        */

        $prescriptionItemCount =
            DB::table('prescription_items')
                ->where(
                    'medicine_id',
                    $medicine->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Tổng số lượng đã được kê
        |--------------------------------------------------------------------------
        */

        $totalQuantityPrescribed =
            DB::table('prescription_items')
                ->where(
                    'medicine_id',
                    $medicine->id
                )
                ->sum('quantity');

        /*
        |--------------------------------------------------------------------------
        | Chi tiết kê thuốc gần đây
        |
        | Chỉ sử dụng các cột prescription_items đã xác nhận schema.
        |--------------------------------------------------------------------------
        */

        $recentPrescriptionItems =
            DB::table('prescription_items')
                ->where(
                    'medicine_id',
                    $medicine->id
                )
                ->select([
                    'id',
                    'prescription_id',
                    'quantity',
                    'usage_unit',
                    'dose_per_use',
                    'times_per_day',
                    'duration_days',
                    'administration_time',
                    'usage_method',
                    'usage_instructions',
                    'notes',
                    'created_at',
                ])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

        return view(
            'admin.medicines.show',
            compact(
                'medicine',
                'prescriptionItemCount',
                'totalQuantityPrescribed',
                'recentPrescriptionItems'
            )
        );
    }

    public function edit(
        Medicine $medicine
    ) {
        return view(
            'admin.medicines.edit',
            compact('medicine')
        );
    }

    public function update(
        UpdateMedicineRequest $request,
        Medicine $medicine
    ) {
        $medicine->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'admin.medicines.show',
                $medicine
            )
            ->with(
                'success',
                'Cập nhật thuốc thành công.'
            );
    }

    public function destroy(
        Medicine $medicine
    ) {
        /*
        |--------------------------------------------------------------------------
        | Thuốc đã xuất hiện trong đơn thuốc?
        |--------------------------------------------------------------------------
        */

        $hasPrescriptionItems =
            DB::table('prescription_items')
                ->where(
                    'medicine_id',
                    $medicine->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Đã được kê -> INACTIVE
        |--------------------------------------------------------------------------
        */

        if ($hasPrescriptionItems) {

            $medicine->update([
                'status' => 'INACTIVE',
            ]);

            return redirect()
                ->route(
                    'admin.medicines.index'
                )
                ->with(
                    'success',
                    'Thuốc đã được sử dụng trong đơn thuốc nên không thể xóa. Hệ thống đã chuyển thuốc sang trạng thái INACTIVE.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Chưa được kê -> hard delete
        |--------------------------------------------------------------------------
        */

        $medicine->delete();

        return redirect()
            ->route(
                'admin.medicines.index'
            )
            ->with(
                'success',
                'Xóa thuốc thành công.'
            );
    }
}