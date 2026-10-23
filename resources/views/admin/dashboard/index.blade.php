@extends('admin.layouts.app')
@section('admin-page', 'dashboard-index')
@section('title', 'Bảng điều khiển - Mediway')
@section('breadcrumb')
    <strong aria-current="page">Bảng điều khiển</strong>
@endsection

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>Bảng điều khiển</h1>
            <p class="page-description">Hoạt động ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} và đối chiếu 7 ngày gần nhất.</p>
        </div>
        <div class="admin-actions">
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.dashboard', ['date' => \Carbon\Carbon::parse($selectedDate)->subDay()->toDateString()]) }}">Ngày trước</a>
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.dashboard', ['date' => \Carbon\Carbon::parse($selectedDate)->addDay()->toDateString()]) }}">Ngày sau</a>
        </div>
    </div>

    @component('admin.layouts.partials.filter-panel', ['title' => 'Ngày thống kê', 'description' => 'Chọn ngày để xem lịch hẹn, lượt khám, dịch vụ và tiền thu được xác nhận trong ngày.'])
        <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-filter-form">
            <div class="admin-filter-grid">
                <div>
                    <label for="date">Ngày thống kê</label>
                    <input type="date" name="date" id="date" value="{{ $selectedDate }}">
                    @error('date')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
            @include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.dashboard')])
        </form>
    @endcomponent

    <h2 class="section-title">Quy mô hồ sơ toàn hệ thống</h2>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-label">Bệnh nhân</div><div class="stat-value">{{ number_format($totalPatients) }}</div></div>
        <div class="stat-card"><div class="stat-label">Bác sĩ</div><div class="stat-value">{{ number_format($totalDoctors) }}</div></div>
        <div class="stat-card"><div class="stat-label">Nhân viên</div><div class="stat-value">{{ number_format($totalEmployees) }}</div></div>
    </div>
    <h2 class="section-title">Hoạt động ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</h2>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-label">Lịch hẹn trong ngày</div><div class="stat-value">{{ number_format($dailyAppointments) }}</div></div>
        <div class="stat-card"><div class="stat-label">Lượt khám đã tiếp nhận</div><div class="stat-value">{{ number_format($dailyVisits) }}</div></div>
        <div class="stat-card"><div class="stat-label">Dịch vụ hoàn tất trong ngày</div><div class="stat-value">{{ number_format($dailyCompletedServices) }}</div></div>
        <div class="stat-card"><div class="stat-label">Tiền thu đã xác nhận</div><div class="stat-value">{{ number_format($dailyRevenue, 0, ',', '.') }} ₫</div><div class="stat-label">Giao dịch thành công: {{ number_format($dailySuccessfulPayments) }}</div></div>
    </div>

    <div class="admin-card">
        <h2 class="section-title">Diễn biến 7 ngày</h2>
        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead><tr><th scope="col" class="admin-row-number">STT</th><th>Ngày</th><th>Lịch hẹn</th><th>Lượt tiếp nhận</th><th>Dịch vụ được chỉ định</th><th>Dịch vụ hoàn tất</th><th>Tiền thu xác nhận</th></tr></thead>
                <tbody>
                    @foreach ($dailyRows as $day)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $dailyRows, 'index' => $loop->index])
                            <td><a href="{{ route('admin.dashboard', ['date' => $day['date']]) }}">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</a></td>
                            <td>{{ number_format($day['appointments']) }}</td><td>{{ number_format($day['visits']) }}</td>
                            <td>{{ number_format($day['services']) }}</td><td>{{ number_format($day['completed_services']) }}</td>
                            <td>{{ number_format($day['revenue'], 0, ',', '.') }} ₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-label">Dịch vụ được chỉ định trong ngày</div><div class="stat-value">{{ number_format($dailyServices) }}</div></div>
        <div class="stat-card"><div class="stat-label">Khoản thu trong ngày còn chờ thanh toán</div><div class="stat-value">{{ number_format($dailyUnpaidCount) }}</div><p class="form-help">{{ number_format($dailyUnpaidAmount, 0, ',', '.') }} ₫</p></div>
    </div>

    <h2 class="section-title">Trạng thái hiện tại</h2>
    <div class="admin-report-grid">
        @foreach ([
            ['title' => 'Lịch hẹn', 'stats' => $appointmentStats, 'codes' => ['BOOKED', 'CHECKED_IN', 'IN_EXAMINATION', 'COMPLETED', 'CANCELLED'], 'context' => null],
            ['title' => 'Lượt khám', 'stats' => $visitStats, 'codes' => ['WAITING', 'IN_EXAMINATION', 'WAITING_FOR_SERVICE', 'RETURNING', 'COMPLETED'], 'context' => null],
            ['title' => 'Dịch vụ được chỉ định', 'stats' => $serviceStats, 'codes' => ['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'], 'context' => 'service'],
            ['title' => 'Hàng đợi', 'stats' => $queueStats, 'codes' => ['WAITING', 'CALLED', 'IN_SERVICE', 'COMPLETED', 'SKIPPED'], 'context' => null],
        ] as $group)
            <section class="admin-card">
                <h2 class="section-title">{{ $group['title'] }}</h2>
                @foreach ($group['codes'] as $code)
                    <div class="admin-stat-breakdown">
                        @include('admin.layouts.partials.status-badge', ['badgeCode' => $code, 'badgeContext' => $group['context']])
                        <strong>{{ number_format($group['stats'][$code] ?? 0) }}</strong>
                        <progress value="{{ $group['stats'][$code] ?? 0 }}" max="{{ max(1, $group['stats']->sum()) }}" aria-label="{{ \App\Models\AdminDisplay::label($code, $group['context']) }}"></progress>
                    </div>
                @endforeach
            </section>
        @endforeach
    </div>

    <section class="admin-card">
        <h2 class="section-title">Hoạt động trong ngày</h2>

        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead><tr><th scope="col" class="admin-row-number">STT</th><th>Người thao tác</th><th>Vai trò</th><th>Hành động</th><th>Đối tượng</th><th>Mã đối tượng</th><th>Mô tả</th><th>Thời gian</th></tr></thead>
                <tbody>
                    @forelse ($recentAuditLogs as $log)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $recentAuditLogs, 'index' => $loop->index])
                            <td>{{ $log->business_code ?? 'Hệ thống' }}</td>
                            <td>{{ \App\Models\AdminDisplay::label($log->role_name_snapshot) }}</td>
                            <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $log->action])</td>
                            <td>{{ \App\Models\AdminDisplay::label($log->entity_type) }}</td>
                            <td>{{ $log->entity_business_code ?? '—' }}</td><td>{{ \App\Models\AdminDisplay::text($log->description) }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->performed_at)->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Chưa có hoạt động trong ngày đã chọn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

@endsection
