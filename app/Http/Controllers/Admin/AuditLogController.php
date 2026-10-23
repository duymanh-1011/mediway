<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditValuePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('audit_logs as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.user_id')
            ->select(
                'al.*',
                'u.business_code as user_business_code'
            );

        // Lọc theo người thực hiện
        if ($request->filled('user')) {
            $query->where(
                'u.business_code',
                $request->user
            );
        }

        // Lọc theo hành động
        if ($request->filled('action')) {
            $query->where(
                'al.action',
                $request->action
            );
        }

        // Lọc theo loại đối tượng
        if ($request->filled('entity')) {
            $query->where(
                'al.entity_type',
                $request->entity
            );
        }

        // Lọc từ ngày
        if ($request->filled('date_from')) {
            $query->whereDate(
                'al.performed_at',
                '>=',
                $request->date_from
            );
        }

        // Lọc đến ngày
        if ($request->filled('date_to')) {
            $query->whereDate(
                'al.performed_at',
                '<=',
                $request->date_to
            );
        }

        $logs = $query
            ->orderByDesc('al.performed_at')
            ->paginate(15)
            ->withQueryString();

        $users = DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('user_id')
                    ->from('audit_logs')
                    ->whereNotNull('user_id');
            })
            ->orderBy('business_code')
            ->get();

        $actions = DB::table('audit_logs')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $entities = DB::table('audit_logs')
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type');

        return view(
            'admin.audit-logs.index',
            compact(
                'logs',
                'users',
                'actions',
                'entities'
            )
        );
    }

    public function show(int $id)
    {
        $log = DB::table('audit_logs as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.user_id')
            ->select(
                'al.*',
                'u.business_code as user_business_code'
            )
            ->where('al.id', $id)
            ->first();

        abort_if(! $log, 404);

        $presenter = app(AuditValuePresenter::class);
        $oldRows = $presenter->rows($log->old_values);
        $newRows = $presenter->rows($log->new_values);

        return view(
            'admin.audit-logs.show',
            compact('log', 'oldRows', 'newRows')
        );
    }
}
