@extends('admin.layouts.app')
@section('title', 'Mẫu phiếu kết quả - Mediway')
@section('admin-page', 'service-result-templates-index')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a><span aria-hidden="true">/</span><span>Dịch vụ</span><span aria-hidden="true">/</span><strong aria-current="page">Mẫu phiếu kết quả</strong>
@endsection
@section('content')
    <div class="page-header"><div><h1 class="page-title">Mẫu phiếu kết quả</h1><p class="page-description">Tạo các phiên bản mẫu, đặt thời gian hiệu lực và gán thủ công từng dịch vụ. Phiếu đã lưu giữ nguyên phiên bản đã dùng.</p></div><a class="admin-btn admin-btn-primary" href="{{ route('admin.service-result-templates.create') }}">Thêm mẫu phiếu</a></div>
    <div class="stats-grid">
        <div class="mini-card"><div class="mini-card-label">Tổng mẫu phiếu</div><div class="mini-card-value">{{ $totalTemplates }}</div></div>
        <div class="mini-card"><div class="mini-card-label">Đang hoạt động</div><div class="mini-card-value success">{{ $activeTemplates }}</div></div>
        <div class="mini-card"><div class="mini-card-label">Ngừng hoạt động</div><div class="mini-card-value danger">{{ $inactiveTemplates }}</div></div>
    </div>
    @component('admin.layouts.partials.filter-panel', ['title' => 'Bộ lọc mẫu phiếu', 'description' => 'Lọc theo tên, mô tả và trạng thái hoạt động.'])
        <form method="GET" action="{{ route('admin.service-result-templates.index') }}" class="filter-form admin-filter-form" data-filter-fields="2">
            <div class="filter-item admin-filter-field"><label class="filter-label" for="template-keyword">Tìm kiếm</label><input class="form-control" id="template-keyword" type="search" name="keyword" value="{{ $keyword }}" placeholder="Tên hoặc mô tả mẫu phiếu..."></div>
            <div class="filter-item admin-filter-field"><label class="filter-label" for="template-status">Trạng thái</label><select class="form-control" id="template-status" name="status"><option value="">Tất cả trạng thái</option><option value="active" @selected($status === 'active')>Đang hoạt động</option><option value="inactive" @selected($status === 'inactive')>Ngừng hoạt động</option></select></div>
            <div class="filter-actions admin-filter-actions"><button class="admin-btn admin-btn-primary" type="submit">Lọc</button><a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.index') }}">Xóa bộ lọc</a></div>
        </form>
    @endcomponent
    <div class="card">
        <h2 class="card-title">Danh sách mẫu</h2>
        <div class="table-responsive"><table class="admin-table"><thead><tr><th scope="col">STT</th><th scope="col">Tên mẫu</th><th scope="col">Thời gian hiệu lực</th><th scope="col">Trường dữ liệu</th><th scope="col">Dịch vụ được gán</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead><tbody>
            @forelse ($templates as $template)
                <tr><td class="admin-row-number">{{ $templates->firstItem() + $loop->index }}</td><td><strong>{{ $template->name }}</strong>@if ($template->description)<br><small>{{ $template->description }}</small>@endif</td><td>{{ $template->effective_from?->format('d/m/Y') ?? '—' }} – {{ $template->effective_until?->format('d/m/Y') ?? 'Không thời hạn' }}</td><td>{{ count($template->fields ?? []) }}</td><td>{{ $template->medical_services_count }}</td><td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $template->is_active ? 'ACTIVE' : 'INACTIVE'])</td><td><div class="admin-actions admin-table-actions"><a class="admin-btn admin-btn-detail" href="{{ route('admin.service-result-templates.show', $template) }}">Chi tiết</a><a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.edit', $template) }}">Sửa</a><form method="POST" action="{{ route('admin.service-result-templates.status', $template) }}" data-confirm="{{ $template->is_active ? 'Ngừng hoạt động mẫu phiếu “'.$template->name.'”? Các phiếu kết quả đã lập vẫn được giữ nguyên.' : 'Kích hoạt lại mẫu phiếu “'.$template->name.'”?' }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $template->is_active ? 0 : 1 }}"><button class="admin-btn {{ $template->is_active ? 'admin-btn-warning' : 'admin-btn-success' }}" type="submit">{{ $template->is_active ? 'Ngừng hoạt động' : 'Kích hoạt lại' }}</button></form></div></td></tr>
            @empty
                <tr><td colspan="7" class="text-center">Chưa có mẫu phiếu. Hãy tạo mẫu để gán cho dịch vụ.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrapper">{{ $templates->links() }}</div>
    </div>
@endsection
