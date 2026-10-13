<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceStatus;
use Illuminate\Http\Request;

class ServiceStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceStatus::query();

        /*
         * Tìm kiếm theo tên hoặc mô tả.
         */
        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }

        /*
         * Lọc trạng thái sử dụng.
         */
        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->is_active === '1'
            );
        }

        /*
         * process_order chỉ dùng để sắp xếp.
         * Không cho Admin sửa tại màn hình chỉnh sửa.
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


    public function show(int $id)
    {
        $status = ServiceStatus::findOrFail($id);

        return view(
            'admin.service-statuses.show',
            compact('status')
        );
    }


    public function edit(int $id)
    {
        $status = ServiceStatus::findOrFail($id);

        return view(
            'admin.service-statuses.edit',
            compact('status')
        );
    }


    public function update(Request $request, int $id)
    {
        $status = ServiceStatus::findOrFail($id);

        /*
         * Không nhận:
         * - name
         * - process_order
         *
         * Vì vậy dù gửi request thủ công cũng không
         * cập nhật được hai trường này.
         */
        $validated = $request->validate([
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'allows_transition' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ], [
            'description.max' =>
            'Mô tả không được vượt quá 255 ký tự.',

            'allows_transition.required' =>
            'Vui lòng chọn quyền chuyển trạng thái.',

            'allows_transition.boolean' =>
            'Quyền chuyển trạng thái không hợp lệ.',

            'is_active.required' =>
            'Vui lòng chọn trạng thái sử dụng.',

            'is_active.boolean' =>
            'Trạng thái sử dụng không hợp lệ.',
        ]);

        $status->update($validated);

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
