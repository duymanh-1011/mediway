<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVisitStatusRequest;
use App\Models\VisitStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitStatusController extends Controller
{
    public function index(Request $request): View
    {
        $query = VisitStatus::query();

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái hoạt động
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('is_active')
            &&
            in_array(
                $request->input('is_active'),
                ['0', '1'],
                true
            )
        ) {
            $query->where(
                'is_active',
                (bool) $request->integer('is_active')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sắp xếp
        |--------------------------------------------------------------------------
        */

        $visitStatuses = $query
            ->orderBy('process_order')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.visit-statuses.index',
            compact('visitStatuses')
        );
    }

    public function show(
        VisitStatus $visitStatus
    ): View {
        $visitStatus->loadCount(
            'medicalVisits'
        );

        return view(
            'admin.visit-statuses.show',
            compact('visitStatus')
        );
    }

    public function edit(
        VisitStatus $visitStatus
    ): View {
        return view(
            'admin.visit-statuses.edit',
            compact('visitStatus')
        );
    }

    public function update(
        UpdateVisitStatusRequest $request,
        VisitStatus $visitStatus
    ): RedirectResponse {
        $data = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Không cho đổi name
        |--------------------------------------------------------------------------
        |
        | name là khóa nghiệp vụ dùng xuyên hệ thống.
        | Admin chỉ được cấu hình mô tả, thứ tự,
        | khả năng chuyển trạng thái và active.
        |
        */

        $visitStatus->update([
            'description' => $data['description'] ?? null,

            'process_order' => $data['process_order'],

            'allows_transition' => $data['allows_transition'],

            'is_active' => $data['is_active'],
        ]);

        return redirect()
            ->route(
                'admin.visit-statuses.show',
                $visitStatus
            )
            ->with(
                'success',
                'Cập nhật trạng thái lượt khám thành công.'
            );
    }
}
