@extends('admin.layouts.app')
@section('admin-page', 'service-groups-show')
@section('title', 'Chi tiết nhóm dịch vụ - Mediway')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ $serviceGroup->name }}</strong>
@endsection
@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')
    <div class="admin-page-header">
        <div>
            <h1>{{ $serviceGroup->name }}</h1>
            <p class="form-help">Mã nhóm: @include('admin.layouts.partials.record-code', ['code' => $serviceGroup->service_group_code])</p>
        </div>
        <div class="admin-actions">
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-groups.index') }}">← Quay lại</a>
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-groups.edit', $serviceGroup) }}">Sửa</a>
            @if ($serviceGroup->status === 'ACTIVE')
                <a class="admin-btn admin-btn-primary" href="{{ route('admin.medical-services.create', ['group_id' => $serviceGroup->id]) }}">Thêm dịch vụ</a>
                <a class="admin-btn admin-btn-primary" href="#assign-services">Gán dịch vụ</a>
            @endif
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-label">Tổng dịch vụ</div><div class="stat-value">{{ $totalServices }}</div></div>
        <div class="stat-card"><div class="stat-label">Đang hoạt động</div><div class="stat-value success">{{ $activeServices }}</div></div>
        <div class="stat-card"><div class="stat-label">Ngừng hoạt động</div><div class="stat-value danger">{{ $inactiveServices }}</div></div>
    </div>
    <section class="admin-card">
        <h2>Thông tin nhóm</h2>
        <dl class="detail-grid">
            <div><dt>Trạng thái</dt><dd>@include('admin.layouts.partials.status-badge', ['badgeCode' => $serviceGroup->status])</dd></div>
            <div><dt>Thứ tự hiển thị</dt><dd>{{ $serviceGroup->display_order }}</dd></div>
            <div><dt>Mô tả</dt><dd>{{ $serviceGroup->description ?: 'Chưa có mô tả' }}</dd></div>
            <div><dt>Ghi chú</dt><dd>{{ $serviceGroup->notes ?: '—' }}</dd></div>
            <div><dt>Ngày tạo</dt><dd>{{ $serviceGroup->created_at?->format('d/m/Y H:i') }}</dd></div>
            <div><dt>Cập nhật lần cuối</dt><dd>{{ $serviceGroup->updated_at?->format('d/m/Y H:i') }}</dd></div>
        </dl>
    </section>
    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc dịch vụ trong nhóm',
        'description' => 'Lọc theo từ khóa và trạng thái.',
    ])
        <form data-filter-fields="2" method="GET" class="admin-filter admin-filter-form" action="{{ route('admin.service-groups.show', $serviceGroup) }}">
            <div class="admin-form-group admin-filter-field"><label for="keyword">Từ khóa</label><input class="admin-input" id="keyword" name="keyword" value="{{ request('keyword') }}" placeholder="Mã hoặc tên dịch vụ">
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="admin-form-group admin-filter-field"><label for="status">Trạng thái</label><select class="admin-select" id="status" name="status"><option value="">Tất cả</option><option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Đang hoạt động</option><option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Ngừng hoạt động</option></select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="admin-actions admin-filter-layout"></div>

@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.service-groups.show', $serviceGroup), 'searchOnly' => false])
        </form>
    @endcomponent

    <section class="admin-card">
        <h2>Dịch vụ thuộc nhóm</h2>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead><tr><th scope="col" class="admin-row-number">STT</th>
                <th>Mã dịch vụ</th><th>Tên dịch vụ</th><th>Giá</th><th>Đơn vị</th><th>Thời lượng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($medicalServices as $service)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $medicalServices, 'index' => $loop->index])
                            <td>@include('admin.layouts.partials.record-code', ['code' => $service->service_code])</td><td>{{ $service->name }}</td><td>{{ number_format($service->price, 0, ',', '.') }} đ</td><td>{{ $service->unit ?: '—' }}</td><td>{{ $service->estimated_duration_minutes ? $service->estimated_duration_minutes.' phút' : '—' }}</td>
                            <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $service->status])</td>
                            <td><div class="admin-actions admin-table-actions"><div class="admin-actions"><a class="admin-btn admin-btn-detail" href="{{ route('admin.medical-services.show', $service) }}">Chi tiết</a><a class="admin-btn admin-btn-secondary" href="{{ route('admin.medical-services.edit', $service) }}">Sửa</a></div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">Không có dịch vụ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $medicalServices->links() }}</div>
    </section>
    @if ($serviceGroup->status === 'ACTIVE')
        <section class="admin-card" id="assign-services">
            <h2>Gán dịch vụ hiện có</h2>
            <p class="form-help">Chọn nhiều dịch vụ bằng phím Ctrl hoặc Shift. Việc gán không tạo bản ghi dịch vụ mới và không bỏ các nhóm đã gán trước đó.</p>
            @if ($availableServices->isNotEmpty())
                <form method="POST" action="{{ route('admin.service-groups.assign', $serviceGroup) }}" class="admin-form" novalidate>
                    @csrf
                    <div class="admin-form-group">
                        <label for="medical_service_ids">Dịch vụ đang hoạt động chưa thuộc nhóm</label>
                        <select id="medical_service_ids" name="medical_service_ids[]" multiple size="6" class="admin-select">
                            @foreach ($availableServices as $service)
                                <option value="{{ $service->id }}" @selected(in_array($service->id, old('medical_service_ids', [])))>{{ $service->service_code }} — {{ $service->name }}</option>
                            @endforeach
                        </select>
                        @error('medical_service_ids')<div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>@enderror
                        @error('medical_service_ids.*')<div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>@enderror
                    </div>
                    <button class="admin-btn admin-btn-primary">Gán dịch vụ</button>
                </form>
            @else
                <p class="empty-state">Tất cả dịch vụ đang hoạt động đã thuộc nhóm này.</p>
            @endif
        </section>
    @endif
@endsection
