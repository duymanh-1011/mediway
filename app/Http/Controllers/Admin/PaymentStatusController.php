<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentStatusRequest;
use App\Models\PaymentStatus;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    /**
     * Danh sách trạng thái thanh toán.
     */
    public function index(Request $request)
    {
        $query = PaymentStatus::query();

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
        | Lọc theo trạng thái sử dụng
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
        | Không cho Admin chỉnh sửa process_order
        | tại màn hình Sửa.
        |
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

    /**
     * Chi tiết trạng thái thanh toán.
     */
    public function show(int $id)
    {
        $status = PaymentStatus::findOrFail($id);

        return view(
            'admin.payment-statuses.show',
            compact('status')
        );
    }

    /**
     * Form sửa trạng thái thanh toán.
     */
    public function edit(int $id)
    {
        $status = PaymentStatus::findOrFail($id);

        return view(
            'admin.payment-statuses.edit',
            compact('status')
        );
    }

    /**
     * Cập nhật trạng thái thanh toán.
     */
    public function update(
        UpdatePaymentStatusRequest $request,
        int $id
    ) {
        $status = PaymentStatus::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Dữ liệu đã được validation trong FormRequest
        |--------------------------------------------------------------------------
        |
        | Không nhận:
        | - name
        | - process_order
        |
        | Vì vậy không thể sửa hai trường này thông qua
        | request update.
        |
        */
        $validated = $request->validated();

        $status->update([
            'description' =>
            $validated['description'] ?? null,

            'allows_service_execution' =>
            $validated['allows_service_execution'],

            'is_active' =>
            $validated['is_active'],
        ]);

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
