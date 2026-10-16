<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateServiceStatusRequest;
use App\Models\ServiceStatus;
use Illuminate\Http\Request;

class ServiceStatusController extends Controller
{
    /**
     * Danh sách trạng thái dịch vụ.
     */
    public function index(Request $request)
    {
        $query = ServiceStatus::query();

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm theo tên hoặc mô tả
        |--------------------------------------------------------------------------
        */
        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'name',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'description',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái sử dụng
        |--------------------------------------------------------------------------
        */
        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->is_active === '1'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | process_order chỉ dùng để sắp xếp
        |--------------------------------------------------------------------------
        |
        | Không cho Admin sửa process_order tại màn hình chỉnh sửa.
        |
        */
        $statuses = $query
            ->orderBy('process_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.service-statuses.index',
            compact('statuses')
        );
    }

    /**
     * Chi tiết trạng thái dịch vụ.
     */
    public function show(int $id)
    {
        $status = ServiceStatus::findOrFail($id);

        return view(
            'admin.service-statuses.show',
            compact('status')
        );
    }

    /**
     * Form sửa trạng thái dịch vụ.
     */
    public function edit(int $id)
    {
        $status = ServiceStatus::findOrFail($id);

        return view(
            'admin.service-statuses.edit',
            compact('status')
        );
    }

    /**
     * Cập nhật trạng thái dịch vụ.
     */
    public function update(
        UpdateServiceStatusRequest $request,
        int $id
    ) {
        $status = ServiceStatus::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu đã được validation trong FormRequest
        |--------------------------------------------------------------------------
        |
        | Không nhận:
        | - name
        | - process_order
        |
        | Vì vậy dù gửi request thủ công cũng không cập nhật
        | được hai trường này.
        |
        */
        $validated = $request->validated();

        $status->update([
            'description' =>
            $validated['description'] ?? null,

            'allows_transition' =>
            $validated['allows_transition'],

            'is_active' =>
            $validated['is_active'],
        ]);

        return redirect()
            ->route(
                'admin.service-statuses.show',
                $status->id
            )
            ->with(
                'success',
                'Cập nhật trạng thái dịch vụ thành công.'
            );
    }
}
