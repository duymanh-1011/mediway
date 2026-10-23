<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $selectedDate = $filters['date'] ?? now()->toDateString();
        $end = CarbonImmutable::parse($selectedDate);
        $start = $end->subDays(6);
        $from = $start->startOfDay();
        $until = $end->addDay()->startOfDay();

        $appointmentSeries = $this->dailyTotals(DB::table('appointments')->whereBetween('appointment_date', [$start->toDateString(), $selectedDate]), 'appointment_date');
        $visitSeries = $this->dailyTotals(DB::table('medical_visits')->where('checked_in_at', '>=', $from)->where('checked_in_at', '<', $until), 'checked_in_at');
        $serviceSeries = $this->dailyTotals(DB::table('service_order_items')->join('service_orders', 'service_orders.id', '=', 'service_order_items.service_order_id')
            ->where('service_orders.ordered_at', '>=', $from)->where('service_orders.ordered_at', '<', $until), 'service_orders.ordered_at');
        $completedSeries = $this->dailyTotals(DB::table('service_order_items')->join('service_statuses', 'service_statuses.id', '=', 'service_order_items.service_status_id')
            ->where('service_statuses.name', 'COMPLETED')->where('completed_at', '>=', $from)->where('completed_at', '<', $until), 'completed_at');
        $successfulPayments = DB::table('payment_transactions')->where('transaction_result', 'SUCCESS');
        $revenueSeries = $this->dailyTotals((clone $successfulPayments)->where('confirmed_at', '>=', $from)->where('confirmed_at', '<', $until), 'confirmed_at', 'SUM(amount)');

        $dailyRows = collect(range(0, 6))->map(function (int $offset) use ($start, $appointmentSeries, $visitSeries, $serviceSeries, $completedSeries, $revenueSeries): array {
            $day = $start->addDays($offset)->toDateString();

            return [
                'date' => $day, 'appointments' => (int) ($appointmentSeries[$day] ?? 0),
                'visits' => (int) ($visitSeries[$day] ?? 0), 'services' => (int) ($serviceSeries[$day] ?? 0),
                'completed_services' => (int) ($completedSeries[$day] ?? 0), 'revenue' => $revenueSeries[$day] ?? 0,
            ];
        });
        $appointmentStats = DB::table('appointments')->join('appointment_statuses', 'appointment_statuses.id', '=', 'appointments.appointment_status_id')
            ->whereDate('appointments.appointment_date', $selectedDate)->selectRaw('appointment_statuses.name, COUNT(*) as total')
            ->groupBy('appointment_statuses.name')->pluck('total', 'name');
        $visitStats = DB::table('medical_visits')->join('visit_statuses', 'visit_statuses.id', '=', 'medical_visits.visit_status_id')
            ->whereDate('medical_visits.checked_in_at', $selectedDate)->selectRaw('visit_statuses.name, COUNT(*) as total')
            ->groupBy('visit_statuses.name')->pluck('total', 'name');
        $serviceStats = DB::table('service_order_items')->join('service_statuses', 'service_statuses.id', '=', 'service_order_items.service_status_id')
            ->join('service_orders', 'service_orders.id', '=', 'service_order_items.service_order_id')
            ->whereDate('service_orders.ordered_at', $selectedDate)->selectRaw('service_statuses.name, COUNT(*) as total')
            ->groupBy('service_statuses.name')->pluck('total', 'name');
        $queueStats = DB::table('examination_room_queues')->join('queue_statuses', 'queue_statuses.id', '=', 'examination_room_queues.queue_status_id')
            ->whereDate('examination_room_queues.queued_at', $selectedDate)->selectRaw('queue_statuses.name, COUNT(*) as total')
            ->groupBy('queue_statuses.name')->pluck('total', 'name');
        $unpaid = DB::table('payables')->join('payment_statuses', 'payment_statuses.id', '=', 'payables.payment_status_id')
            ->whereDate('payables.generated_at', $selectedDate)->whereIn('payment_statuses.name', ['UNPAID', 'PENDING']);
        $recentAuditLogs = DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->whereDate('audit_logs.performed_at', $selectedDate)
            ->select(['audit_logs.*', 'users.business_code'])->orderByDesc('audit_logs.performed_at')->orderByDesc('audit_logs.id')->limit(10)->get();

        return view('admin.dashboard.index', [
            'selectedDate' => $selectedDate, 'dailyRows' => $dailyRows,
            'appointmentStats' => $appointmentStats, 'visitStats' => $visitStats, 'serviceStats' => $serviceStats, 'queueStats' => $queueStats,
            'dailyAppointments' => (int) ($appointmentSeries[$selectedDate] ?? 0), 'dailyVisits' => (int) ($visitSeries[$selectedDate] ?? 0),
            'dailyServices' => (int) ($serviceSeries[$selectedDate] ?? 0), 'dailyCompletedServices' => (int) ($completedSeries[$selectedDate] ?? 0),
            'dailyRevenue' => $revenueSeries[$selectedDate] ?? 0,
            'dailySuccessfulPayments' => (clone $successfulPayments)->whereDate('confirmed_at', $selectedDate)->count(),
            'dailyUnpaidCount' => (clone $unpaid)->count(), 'dailyUnpaidAmount' => (clone $unpaid)->sum('payables.amount'),
            'totalPatients' => DB::table('patients')->count(), 'totalDoctors' => DB::table('doctors')->count(), 'totalEmployees' => DB::table('employees')->count(),
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }

    /** Date columns and aggregates are internal constants, never request input. */
    private function dailyTotals(Builder $query, string $dateColumn, string $aggregate = 'COUNT(*)'): Collection
    {
        return $query->selectRaw('DATE('.$dateColumn.') as report_date, '.$aggregate.' as total')
            ->groupByRaw('DATE('.$dateColumn.')')->pluck('total', 'report_date');
    }
}
