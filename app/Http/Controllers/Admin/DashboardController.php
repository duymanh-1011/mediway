<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Ngày hiện tại
        |--------------------------------------------------------------------------
        */

        $today = now()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Tổng quan con người
        |--------------------------------------------------------------------------
        */

        $totalPatients = DB::table('patients')->count();

        $totalDoctors = DB::table('doctors')->count();

        $totalEmployees = DB::table('employees')->count();

        /*
        |--------------------------------------------------------------------------
        | Lịch hẹn hôm nay
        |--------------------------------------------------------------------------
        */

        $todayAppointments = DB::table('appointments')
            ->whereDate('appointment_date', $today)
            ->count();

        $todayAppointmentStats = DB::table('appointments')
            ->join(
                'appointment_statuses',
                'appointments.appointment_status_id',
                '=',
                'appointment_statuses.id'
            )
            ->whereDate('appointments.appointment_date', $today)
            ->select(
                'appointment_statuses.name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('appointment_statuses.name')
            ->pluck('total', 'appointment_statuses.name');

        /*
        |--------------------------------------------------------------------------
        | Lượt khám hôm nay
        |--------------------------------------------------------------------------
        */

        $todayVisits = DB::table('medical_visits')
            ->whereDate('checked_in_at', $today)
            ->count();

        $todayVisitStats = DB::table('medical_visits')
            ->join(
                'visit_statuses',
                'medical_visits.visit_status_id',
                '=',
                'visit_statuses.id'
            )
            ->whereDate('medical_visits.checked_in_at', $today)
            ->select(
                'visit_statuses.name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('visit_statuses.name')
            ->pluck('total', 'visit_statuses.name');

        /*
        |--------------------------------------------------------------------------
        | Hàng đợi đang hoạt động hôm nay
        |--------------------------------------------------------------------------
        |
        | Chỉ tính queue của ngày hiện tại.
        | Tránh lấy các queue demo ở ngày khác.
        |
        */

        $activeQueueCount = DB::table('examination_room_queues')
            ->join(
                'queue_statuses',
                'examination_room_queues.queue_status_id',
                '=',
                'queue_statuses.id'
            )
            ->whereDate(
                'examination_room_queues.queued_at',
                $today
            )
            ->whereIn('queue_statuses.name', [
                'WAITING',
                'CALLED',
                'IN_SERVICE',
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Dịch vụ đang chờ / đang thực hiện hôm nay
        |--------------------------------------------------------------------------
        |
        | Dùng service_orders.ordered_at làm ngày nghiệp vụ.
        | Không dùng created_at vì Seeder có thể tạo record ở ngày chạy seed.
        |
        */

        $activeServiceCount = DB::table('service_order_items')
            ->join(
                'service_statuses',
                'service_order_items.service_status_id',
                '=',
                'service_statuses.id'
            )
            ->join(
                'service_orders',
                'service_order_items.service_order_id',
                '=',
                'service_orders.id'
            )
            ->whereDate(
                'service_orders.ordered_at',
                $today
            )
            ->whereIn('service_statuses.name', [
                'PENDING',
                'PROCESSING',
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Khoản chưa thanh toán
        |--------------------------------------------------------------------------
        |
        | Đây là tổng công nợ hiện tại của toàn hệ thống,
        | không giới hạn theo ngày.
        |
        */

        $unpaidQuery = DB::table('payables')
            ->join(
                'payment_statuses',
                'payables.payment_status_id',
                '=',
                'payment_statuses.id'
            )
            ->where(
                'payment_statuses.name',
                'UNPAID'
            );

        $unpaidCount = (clone $unpaidQuery)
            ->count();

        $unpaidAmount = (clone $unpaidQuery)
            ->sum('payables.amount');

        /*
        |--------------------------------------------------------------------------
        | Tổng doanh thu đã thanh toán
        |--------------------------------------------------------------------------
        |
        | Tổng toàn hệ thống.
        | Chỉ lấy giao dịch thành công.
        |
        */

        $paidRevenue = DB::table('payment_transactions')
            ->where(
                'transaction_result',
                'SUCCESS'
            )
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Hoạt động gần đây
        |--------------------------------------------------------------------------
        */

        $recentAuditLogs = DB::table('audit_logs')
            ->leftJoin(
                'users',
                'audit_logs.user_id',
                '=',
                'users.id'
            )
            ->select([
                'audit_logs.id',
                'audit_logs.role_name_snapshot',
                'audit_logs.action',
                'audit_logs.entity_type',
                'audit_logs.entity_id',
                'audit_logs.entity_business_code',
                'audit_logs.description',
                'audit_logs.performed_at',
                'users.business_code',
            ])
            ->orderByDesc(
                'audit_logs.performed_at'
            )
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Trả dữ liệu sang View
        |--------------------------------------------------------------------------
        */

        return view('admin.dashboard.index', [
            'totalPatients' => $totalPatients,
            'totalDoctors' => $totalDoctors,
            'totalEmployees' => $totalEmployees,

            'todayAppointments' => $todayAppointments,
            'todayAppointmentStats' => $todayAppointmentStats,

            'todayVisits' => $todayVisits,
            'todayVisitStats' => $todayVisitStats,

            'activeQueueCount' => $activeQueueCount,
            'activeServiceCount' => $activeServiceCount,

            'unpaidCount' => $unpaidCount,
            'unpaidAmount' => $unpaidAmount,

            'paidRevenue' => $paidRevenue,

            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}