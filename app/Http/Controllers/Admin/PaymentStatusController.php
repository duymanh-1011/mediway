<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentStatus;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentStatus::query();

        /*
         * Tìm kiếm theo tên hoặc mô tả.
         */
        if ($request->filled('q')) {
            $keyword = trim($request->q);

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere(
                        'description',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }

        /*
         * Lọc theo trạng thái sử dụng.
         */
        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->is_active === '1'
            );
        }

        /*
         * process_order chỉ dùng để sắp xếp.
         * Không cho Admin chỉnh sửa ở màn hình Sửa.
         */
        $statuses = $query
            ->orderBy('process_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.payment-statuses.index',
            compact('statuses')
        );
    }


    public function show(int $id)
    {
        $status = PaymentStatus::findOrFail($id);

        return view(
            'admin.payment-statuses.show',
            compact('status')
        );
    }


    public function edit(int $id)
    {
        $status = PaymentStatus::findOrFail($id);

        return view(
            'admin.payment-statuses.edit',
            compact('status')
        );
    }


    public function update(Request $request, int $id)
    {
        $status = PaymentStatus::findOrFail($id);

        /*
         * Không nhận:
         * - name
         * - process_order
         *
         * Vì vậy không thể sửa hai trường này
         * thông qua request update.
         */
        $validated = $request->validate([
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'allows_service_execution' => [
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

            'allows_service_execution.required' =>
            'Vui lòng chọn quyền thực hiện dịch vụ.',

            'allows_service_execution.boolean' =>
            'Quyền thực hiện dịch vụ không hợp lệ.',

            'is_active.required' =>
            'Vui lòng chọn trạng thái sử dụng.',

            'is_active.boolean' =>
            'Trạng thái sử dụng không hợp lệ.',
        ]);

        $status->update($validated);

        return redirect()
            ->route(
                'admin.payment-statuses.show',
                $status->id
            )
            ->with(
                'success',
                'Cập nhật trạng thái thanh toán thành công.'
            );
    }
}
