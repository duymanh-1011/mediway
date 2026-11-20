@extends('admin.layouts.app')
@section('title', 'Chi tiết mẫu phiếu - Mediway')
@section('admin-page', 'service-result-templates-show')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a><span aria-hidden="true">/</span><span>Dịch vụ</span><span aria-hidden="true">/</span><a href="{{ route('admin.service-result-templates.index') }}">Mẫu phiếu kết quả</a><span aria-hidden="true">/</span><strong aria-current="page">{{ $serviceResultTemplate->name }}</strong>
@endsection
@section('content')
    <div class="page-header">
        <div><h1 class="page-title">{{ $serviceResultTemplate->name }}</h1><p class="page-description">Chi tiết cấu trúc phiếu và các dịch vụ đang sử dụng mẫu này.</p></div>
        <div class="header-actions">
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.index') }}">← Danh sách mẫu</a>
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.edit', $serviceResultTemplate) }}">Sửa mẫu</a>
            <form method="POST" action="{{ route('admin.service-result-templates.status', $serviceResultTemplate) }}" data-confirm="{{ $serviceResultTemplate->is_active ? 'Ngừng hoạt động mẫu phiếu “'.$serviceResultTemplate->name.'”? Các phiếu kết quả đã lập vẫn được giữ nguyên.' : 'Kích hoạt lại mẫu phiếu “'.$serviceResultTemplate->name.'”?' }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $serviceResultTemplate->is_active ? 0 : 1 }}"><button class="admin-btn {{ $serviceResultTemplate->is_active ? 'admin-btn-warning' : 'admin-btn-success' }}" type="submit">{{ $serviceResultTemplate->is_active ? 'Ngừng hoạt động' : 'Kích hoạt lại' }}</button></form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span>Trạng thái</span><strong>@include('admin.layouts.partials.status-badge', ['badgeCode' => $serviceResultTemplate->is_active ? 'ACTIVE' : 'INACTIVE'])</strong></div>
        <div class="stat-card"><span>Bắt đầu hiệu lực</span><strong>{{ $serviceResultTemplate->effective_from?->format('d/m/Y') ?? '—' }}</strong></div>
        <div class="stat-card"><span>Kết thúc hiệu lực</span><strong>{{ $serviceResultTemplate->effective_until?->format('d/m/Y') ?? 'Không thời hạn' }}</strong></div>
        <div class="stat-card"><span>Dịch vụ được gán</span><strong>{{ $serviceResultTemplate->medicalServices->count() }}</strong></div>
    </div>

    @if ($serviceResultTemplate->description)
        <section class="card"><h2 class="card-title">Mô tả</h2><p>{{ $serviceResultTemplate->description }}</p></section>
    @endif

    <section class="card">
        <h2 class="card-title">Cấu trúc phiếu <small>({{ count($serviceResultTemplate->fields ?? []) }} trường)</small></h2>
        <div class="table-responsive"><table class="admin-table"><thead><tr><th scope="col">STT</th><th scope="col">Tên trường</th><th scope="col">Loại dữ liệu</th><th scope="col">Đơn vị</th><th scope="col">Giá trị tham chiếu</th><th scope="col">Bắt buộc</th></tr></thead><tbody>
            @foreach ($serviceResultTemplate->fields ?? [] as $field)
                <tr><td class="admin-row-number">{{ $loop->iteration }}</td><td><strong>{{ $field['label'] ?? '—' }}</strong></td><td>{{ ['text' => 'Văn bản', 'textarea' => 'Văn bản dài', 'number' => 'Số', 'measurements' => 'Bảng chỉ số'][$field['type'] ?? ''] ?? '—' }}</td><td>{{ $field['unit'] ?? '—' }}</td><td>{{ $field['reference'] ?? '—' }}</td><td>{{ !empty($field['required']) ? 'Có' : 'Không' }}</td></tr>
            @endforeach
        </tbody></table></div>
    </section>

    <section class="card">
        <h2 class="card-title">Dịch vụ sử dụng mẫu</h2>
        @if ($serviceResultTemplate->medicalServices->isNotEmpty())
            <div class="table-responsive"><table class="admin-table"><thead><tr><th scope="col">Mã dịch vụ</th><th scope="col">Tên dịch vụ</th><th scope="col">Trạng thái</th></tr></thead><tbody>
                @foreach ($serviceResultTemplate->medicalServices as $service)
                    <tr><td>{{ $service->service_code }}</td><td>{{ $service->name }}</td><td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $service->status])</td></tr>
                @endforeach
            </tbody></table></div>
        @else
            <p class="text-muted">Chưa có dịch vụ nào được gán vào mẫu phiếu này.</p>
        @endif
    </section>
@endsection
