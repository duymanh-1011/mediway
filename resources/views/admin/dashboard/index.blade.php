@extends('admin.layouts.app')

@section('title', 'Dashboard - Mediway Admin')

@section('breadcrumb')
    <div class="breadcrumb">
        Admin / Dashboard
    </div>
@endsection

@section('content')

    <div style="margin-bottom:24px;">
        <h1 class="page-title">
            Dashboard
        </h1>

        <p class="page-description">
            Tổng quan hoạt động hệ thống Mediway.
        </p>
    </div>

    <div class="dashboard-grid">

        <div class="stat-card">
            <div class="stat-title">
                Bệnh nhân
            </div>

            <div class="stat-value">
                {{ number_format($totalPatients) }}
            </div>

            <div class="stat-description">
                Tổng số bệnh nhân trong hệ thống
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                Bác sĩ
            </div>

            <div class="stat-value">
                {{ number_format($totalDoctors) }}
            </div>

            <div class="stat-description">
                Tổng số bác sĩ
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                Nhân viên
            </div>

            <div class="stat-value">
                {{ number_format($totalEmployees) }}
            </div>

            <div class="stat-description">
                Tổng số nhân viên
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                Lịch hẹn hôm nay
            </div>

            <div class="stat-value">
                {{ number_format($todayAppointments) }}
            </div>

            <div class="stat-description">
                Tổng lịch hẹn trong ngày
            </div>
        </div>

    </div>

    <div class="dashboard-grid">

        <div class="stat-card">
            <div class="stat-title">
                Lượt khám hôm nay
            </div>

            <div class="stat-value">
                {{ number_format($todayVisits) }}
            </div>

            <div class="stat-description">
                Lượt khám đã check-in trong ngày
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                Đang trong hàng đợi
            </div>

            <div class="stat-value">
                {{ number_format($activeQueueCount) }}
            </div>

            <div class="stat-description">
                WAITING / CALLED / IN_SERVICE
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                Dịch vụ đang xử lý
            </div>

            <div class="stat-value">
                {{ number_format($activeServiceCount) }}
            </div>

            <div class="stat-description">
                PENDING / PROCESSING
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                Khoản chưa thanh toán
            </div>

            <div class="stat-value">
                {{ number_format($unpaidCount) }}
            </div>

            <div class="stat-description">
                Tổng:
                {{ number_format($unpaidAmount, 0, ',', '.') }}
                VNĐ
            </div>
        </div>

    </div>

    <div class="dashboard-section">

        <div class="revenue-card">

            <div class="stat-title">
                Doanh thu đã thanh toán
            </div>

            <div class="revenue-value">
                {{ number_format($paidRevenue, 0, ',', '.') }}
                VNĐ
            </div>

            <div class="stat-description">
                Tổng các giao dịch có kết quả SUCCESS
            </div>

        </div>

    </div>

    <div class="dashboard-two-columns">

        <div class="card">

            <h3 class="section-title">
                Trạng thái lịch hẹn hôm nay
            </h3>

            <div class="status-list">

                <div class="status-item">
                    <span>BOOKED</span>
                    <strong>{{ $todayAppointmentStats['BOOKED'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>CHECKED_IN</span>
                    <strong>{{ $todayAppointmentStats['CHECKED_IN'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>IN_EXAMINATION</span>
                    <strong>{{ $todayAppointmentStats['IN_EXAMINATION'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>COMPLETED</span>
                    <strong>{{ $todayAppointmentStats['COMPLETED'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>CANCELLED</span>
                    <strong>{{ $todayAppointmentStats['CANCELLED'] ?? 0 }}</strong>
                </div>

            </div>

        </div>

        <div class="card">

            <h3 class="section-title">
                Trạng thái lượt khám hôm nay
            </h3>

            <div class="status-list">

                <div class="status-item">
                    <span>WAITING</span>
                    <strong>{{ $todayVisitStats['WAITING'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>IN_EXAMINATION</span>
                    <strong>{{ $todayVisitStats['IN_EXAMINATION'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>WAITING_FOR_SERVICE</span>
                    <strong>{{ $todayVisitStats['WAITING_FOR_SERVICE'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>RETURNING</span>
                    <strong>{{ $todayVisitStats['RETURNING'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>COMPLETED</span>
                    <strong>{{ $todayVisitStats['COMPLETED'] ?? 0 }}</strong>
                </div>

            </div>

        </div>

    </div>

    <div class="card" style="margin-top:24px;">

        <div class="section-header">

            <div>
                <h3 class="section-title">
                    Hoạt động gần đây
                </h3>

                <p class="section-description">
                    10 hoạt động mới nhất trong hệ thống.
                </p>
            </div>

        </div>

        <div style="overflow-x:auto;">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Người thao tác</th>
                        <th>Vai trò</th>
                        <th>Hành động</th>
                        <th>Đối tượng</th>
                        <th>Mã đối tượng</th>
                        <th>Mô tả</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>

                <tbody>

                @forelse ($recentAuditLogs as $log)

                    <tr>

                        <td>
                            {{ $log->business_code ?? 'Hệ thống' }}
                        </td>

                        <td>
                            {{ $log->role_name_snapshot ?? '-' }}
                        </td>

                        <td>
                            <span class="badge">
                                {{ $log->action }}
                            </span>
                        </td>

                        <td>
                            {{ $log->entity_type }}
                        </td>

                        <td>
                            {{ $log->entity_business_code
                                ?? $log->entity_id
                                ?? '-' }}
                        </td>

                        <td>
                            {{ $log->description ?? '-' }}
                        </td>

                        <td>
                            {{ $log->performed_at }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="7"
                            style="text-align:center; color:#6b7280;"
                        >
                            Chưa có nhật ký hoạt động.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection

@push('styles')

<style>

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 18px;
    }

    .stat-title {
        color: #6b7280;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 30px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 6px;
    }

    .stat-description {
        font-size: 13px;
        color: #9ca3af;
    }

    .dashboard-section {
        margin-top: 8px;
        margin-bottom: 24px;
    }

    .revenue-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 22px;
    }

    .revenue-value {
        font-size: 32px;
        font-weight: 700;
        color: #059669;
        margin: 8px 0;
    }

    .dashboard-two-columns {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .section-title {
        margin: 0 0 16px;
        font-size: 18px;
    }

    .section-description {
        margin: -8px 0 16px;
        color: #6b7280;
        font-size: 13px;
    }

    .status-list {
        display: flex;
        flex-direction: column;
    }

    .status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
    }

    .status-item:last-child {
        border-bottom: none;
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        white-space: nowrap;
    }

    .admin-table th {
        background: #f9fafb;
        color: #4b5563;
        font-size: 12px;
        text-transform: uppercase;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 600;
    }

    @media (max-width: 1100px) {

        .dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

    }

    @media (max-width: 700px) {

        .dashboard-grid,
        .dashboard-two-columns {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush