<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateQueueStatusRequest;
use App\Models\QueueStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueStatusController extends Controller
{
    public function index(Request $request): View
    {
        $query = QueueStatus::query();

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

        $queueStatuses = $query
            ->orderBy('process_order')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.queue-statuses.index',
            compact('queueStatuses')
        );
    }

    public function show(
        QueueStatus $queueStatus
    ): View {
        $queueStatus->loadCount(
            'queues'
        );

        return view(
            'admin.queue-statuses.show',
            compact('queueStatus')
        );
    }

    public function edit(
        QueueStatus $queueStatus
    ): View {
        return view(
            'admin.queue-statuses.edit',
            compact('queueStatus')
        );
    }

    public function update(
        UpdateQueueStatusRequest $request,
        QueueStatus $queueStatus
    ): RedirectResponse {
        $data = $request->validated();

        $queueStatus->update([
            'description' => $data['description'] ?? null,

            'process_order' => $data['process_order'],

            'allows_transition' => $data['allows_transition'],

            'is_active' => $data['is_active'],
        ]);

        return redirect()
            ->route(
                'admin.queue-statuses.show',
                $queueStatus
            )
            ->with(
                'success',
                'Cập nhật trạng thái hàng đợi thành công.'
            );
    }
}
