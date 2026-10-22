@extends('admin.layouts.app')
@section('admin-page', 'dashboard-index')

@section('title', 'Bảng điều khiển - Mediway')

@section('breadcrumb')
    <strong aria-current="page">Bảng điều khiển</strong>
@endsection

@section('content')

    <div class="mb-6">
        <h1 class="page-title">
            Bảng điều khiển
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
                Lượt khám đã tiếp nhận trong ngày
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
                Đang chờ / Đã gọi / Đang phục vụ
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
                Chờ xử lý / Đang thực hiện
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
                Tổng các giao dịch có kết quả Thành công
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
                    <span>Đã đặt lịch</span>
                    <strong>{{ $todayAppointmentStats['BOOKED'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Đã tiếp nhận</span>
                    <strong>{{ $todayAppointmentStats['CHECKED_IN'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Đang khám</span>
                    <strong>{{ $todayAppointmentStats['IN_EXAMINATION'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Đã hoàn thành</span>
                    <strong>{{ $todayAppointmentStats['COMPLETED'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Đã hủy</span>
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
                    <span>Đang chờ</span>
                    <strong>{{ $todayVisitStats['WAITING'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Đang khám</span>
                    <strong>{{ $todayVisitStats['IN_EXAMINATION'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Chờ thực hiện dịch vụ</span>
                    <strong>{{ $todayVisitStats['WAITING_FOR_SERVICE'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Quay lại bác sĩ</span>
                    <strong>{{ $todayVisitStats['RETURNING'] ?? 0 }}</strong>
                </div>

                <div class="status-item">
                    <span>Đã hoàn thành</span>
                    <strong>{{ $todayVisitStats['COMPLETED'] ?? 0 }}</strong>
                </div>

            </div>

        </div>

    </div>

    <div class="card mt-6">

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

        <div class="admin-table-scroll">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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

                        @include('admin.layouts.partials.row-number', ['rows' => $recentAuditLogs, 'index' => $loop->index])
                        <td>
                            {{ $log->business_code ?? 'Hệ thống' }}
                        </td>

                        <td>
                            {{ \App\Models\AdminDisplay::label($log->role_name_snapshot ?? '-') }}
                        </td>

                        <td>
                            <span class="badge">
                                {{ \App\Models\AdminDisplay::label($log->action) }}
                            </span>
                        </td>

                        <td>
                            {{ \App\Models\AdminDisplay::label($log->entity_type) }}
                        </td>

                        <td>
                            {{ $log->entity_business_code
                                ?? $log->entity_id
                                ?? '-' }}
                        </td>

                        <td>
                            {{ \App\Models\AdminDisplay::text($log->description ?? '-') }}
                        </td>

                        <td>
                            {{ $log->performed_at }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="8"
                            class="py-10 text-center text-slate-500"
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
