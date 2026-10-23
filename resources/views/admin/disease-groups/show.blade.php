@extends('admin.layouts.app')
@section('admin-page', 'disease-groups-show')
@section('title', 'Chi tiết nhóm bệnh - Mediway')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <a href="{{ route('admin.disease-groups.index') }}">Nhóm bệnh</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">@include('admin.layouts.partials.record-code', ['code' => $diseaseGroup->disease_group_code])</strong>
@endsection
@section('content')
    @include('admin.layouts.partials.disease-catalog-tabs')
    <div class="admin-page-header">
        <div>
            <h1>{{ $diseaseGroup->name }}</h1>
            <p class="page-description">Mã nhóm bệnh: @include('admin.layouts.partials.record-code', ['code' => $diseaseGroup->disease_group_code])</p>
        </div>
        <div class="admin-actions">
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.disease-groups.index') }}">← Quay lại</a>
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.disease-groups.edit', $diseaseGroup) }}">Sửa</a>
            @if ($diseaseGroup->status === 'ACTIVE')
                <a class="admin-btn admin-btn-primary" href="{{ route('admin.diseases.create', ['disease_group_id' => $diseaseGroup->id]) }}">Thêm bệnh</a>
            @endif
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-label">Tổng bệnh</div><div class="stat-value">{{ $totalDiseases }}</div></div>
        <div class="stat-card"><div class="stat-label">Đang hoạt động</div><div class="stat-value success">{{ $activeDiseases }}</div></div>
        <div class="stat-card"><div class="stat-label">Ngừng hoạt động</div><div class="stat-value danger">{{ $inactiveDiseases }}</div></div>
    </div>

    <section class="admin-card">
        <h2>Thông tin nhóm bệnh</h2>
        <dl class="detail-grid">
            <div><dt>Mã nhóm</dt><dd>@include('admin.layouts.partials.record-code', ['code' => $diseaseGroup->disease_group_code])</dd></div>
            <div><dt>Tên nhóm</dt><dd>{{ $diseaseGroup->name }}</dd></div>
            <div><dt>Trạng thái</dt><dd>@include('admin.layouts.partials.status-badge', ['badgeCode' => $diseaseGroup->status])</dd></div>
            <div><dt>Mô tả</dt><dd>{{ $diseaseGroup->description ?: '—' }}</dd></div>
            <div><dt>Ghi chú</dt><dd>{{ $diseaseGroup->notes ?: '—' }}</dd></div>
            <div><dt>Ngày tạo</dt><dd>{{ $diseaseGroup->created_at?->format('d/m/Y H:i') }}</dd></div>
        </dl>
    </section>

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc bệnh trong nhóm',
        'description' => 'Lọc theo mã, tên, tên thay thế và trạng thái bệnh trong nhóm hiện tại.',
    ])
        <form method="GET" action="{{ route('admin.disease-groups.show', $diseaseGroup) }}" class="admin-filter-form">
            <div class="admin-filter-field">
                <label for="group-disease-keyword">Từ khóa</label>
                <input id="group-disease-keyword" name="keyword" value="{{ request('keyword') }}" placeholder="Mã, tên hoặc tên thay thế của bệnh...">
                @error('keyword')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="admin-filter-field">
                <label for="group-disease-status">Trạng thái</label>
                <select id="group-disease-status" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Đang hoạt động</option>
                    <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Ngừng hoạt động</option>
                </select>
                @error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            @include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.disease-groups.show', $diseaseGroup)])
        </form>
    @endcomponent

    <section class="admin-card" id="group-diseases">
        <h2>Danh mục bệnh thuộc nhóm</h2>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead><tr><th scope="col" class="admin-row-number">STT</th>
                <th>Mã bệnh</th><th>Tên bệnh</th><th>Tên khác</th><th>Triệu chứng thường gặp</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($diseases as $disease)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $diseases, 'index' => $loop->index])
                            <td><strong>@include('admin.layouts.partials.record-code', ['code' => $disease->disease_code])</strong></td>
                            <td>{{ $disease->name }}</td>
                            <td>{{ $disease->alternative_name ?: '—' }}</td>
                            <td>{{ $disease->common_symptoms ?: '—' }}</td>
                            <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $disease->status])</td>
                            <td><div class="admin-actions admin-table-actions"><div class="admin-actions">
                                <a class="admin-btn admin-btn-detail" href="{{ route('admin.diseases.show', $disease->id) }}">Chi tiết</a>
                                <a class="admin-btn admin-btn-secondary" href="{{ route('admin.diseases.edit', $disease->id) }}">Sửa</a>
                            </div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Không có bệnh phù hợp trong nhóm.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $diseases->links() }}</div>
    </section>
@endsection
