@extends('admin.layouts.app')
@section('title', 'Chi tiết mẫu phiếu - Mediway')
@section('admin-page', 'service-result-templates-show')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span><span>Dịch vụ</span>
    <span aria-hidden="true">/</span><a href="{{ route('admin.service-result-templates.index') }}">Mẫu phiếu kết quả</a>
    <span aria-hidden="true">/</span><strong aria-current="page">{{ $serviceResultTemplate->name }}</strong>
@endsection

@section('content')
    <div class="page-header template-detail-header">
        <div class="template-detail-title">
            <span class="template-detail-eyebrow">MẪU PHIẾU KẾT QUẢ</span>
            <h1 class="page-title">{{ $serviceResultTemplate->name }}</h1>
            <p class="page-description">Xem cấu trúc phiếu, thời gian áp dụng và các dịch vụ đang sử dụng.</p>
        </div>
        <div class="header-actions template-detail-actions">
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.index') }}">← Danh sách mẫu</a>
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.edit', $serviceResultTemplate) }}">Sửa mẫu</a>
            <form method="POST" action="{{ route('admin.service-result-templates.status', $serviceResultTemplate) }}" data-confirm="{{ $serviceResultTemplate->is_active ? 'Ngừng hoạt động mẫu phiếu “'.$serviceResultTemplate->name.'”? Các phiếu kết quả đã lập vẫn được giữ nguyên.' : 'Kích hoạt lại mẫu phiếu “'.$serviceResultTemplate->name.'”?' }}">
                @csrf @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $serviceResultTemplate->is_active ? 0 : 1 }}">
                <button class="admin-btn {{ $serviceResultTemplate->is_active ? 'admin-btn-warning' : 'admin-btn-success' }}" type="submit">{{ $serviceResultTemplate->is_active ? 'Ngừng hoạt động' : 'Kích hoạt lại' }}</button>
            </form>
        </div>
    </div>

    <div class="stats-grid template-summary">
        <div class="stat-card"><div class="stat-label">Trạng thái</div><div class="stat-value template-status-value">@include('admin.layouts.partials.status-badge', ['badgeCode' => $serviceResultTemplate->is_active ? 'ACTIVE' : 'INACTIVE'])</div></div>
        <div class="stat-card"><div class="stat-label">Bắt đầu hiệu lực</div><div class="stat-value">{{ $serviceResultTemplate->effective_from?->format('d/m/Y') ?? '—' }}</div></div>
        <div class="stat-card"><div class="stat-label">Kết thúc hiệu lực</div><div class="stat-value">{{ $serviceResultTemplate->effective_until?->format('d/m/Y') ?? 'Không thời hạn' }}</div></div>
        <div class="stat-card"><div class="stat-label">Dịch vụ được gán</div><div class="stat-value">{{ $serviceResultTemplate->medicalServices->count() }}</div></div>
    </div>

    @if ($serviceResultTemplate->description)
        <section class="card template-detail-card">
            <div class="template-section-heading"><div><h2 class="card-title">Mô tả mẫu phiếu</h2><p>Thông tin về mục đích và phạm vi sử dụng.</p></div></div>
            <div class="template-description">{{ $serviceResultTemplate->description }}</div>
        </section>
    @endif

    <section class="card template-detail-card">
        <div class="template-section-heading">
            <div><h2 class="card-title">Cấu trúc phiếu</h2><p>Các trường sẽ được hiển thị khi nhân viên nhập kết quả.</p></div>
            <span class="template-count">{{ count($serviceResultTemplate->fields ?? []) }} trường dữ liệu</span>
        </div>
        <div class="table-responsive"><table class="admin-table template-fields-table">
            <thead><tr><th scope="col">STT</th><th scope="col">Tên trường</th><th scope="col">Loại dữ liệu</th><th scope="col">Đơn vị</th><th scope="col">Giá trị tham chiếu</th><th scope="col">Bắt buộc</th></tr></thead>
            <tbody>
                @forelse ($serviceResultTemplate->fields ?? [] as $field)
                    <tr>
                        <td class="admin-row-number">{{ $loop->iteration }}</td>
                        <td><strong>{{ $field['label'] ?? '—' }}</strong></td>
                        <td>{{ ['text' => 'Văn bản', 'textarea' => 'Văn bản dài', 'number' => 'Số', 'measurements' => 'Bảng chỉ số'][$field['type'] ?? ''] ?? '—' }}</td>
                        <td>{{ $field['unit'] ?? '—' }}</td>
                        <td>{{ $field['reference'] ?? '—' }}</td>
                        <td><span class="template-required {{ !empty($field['required']) ? 'is-required' : '' }}">{{ !empty($field['required']) ? 'Bắt buộc' : 'Không bắt buộc' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Mẫu phiếu chưa có trường dữ liệu.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="card template-detail-card">
        <div class="template-section-heading">
            <div><h2 class="card-title">Dịch vụ sử dụng mẫu</h2><p>Các dịch vụ được gán vào mẫu phiếu này.</p></div>
            <span class="template-count">{{ $serviceResultTemplate->medicalServices->count() }} dịch vụ</span>
        </div>
        @if ($serviceResultTemplate->medicalServices->isNotEmpty())
            <div class="table-responsive"><table class="admin-table">
                <thead><tr><th scope="col">Mã dịch vụ</th><th scope="col">Tên dịch vụ</th><th scope="col">Trạng thái</th></tr></thead>
                <tbody>@foreach ($serviceResultTemplate->medicalServices as $service)<tr><td><strong>{{ $service->service_code }}</strong></td><td>{{ $service->name }}</td><td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $service->status])</td></tr>@endforeach</tbody>
            </table></div>
        @else
            <div class="template-empty-state"><strong>Chưa có dịch vụ được gán</strong><span>Chỉnh sửa mẫu để chọn dịch vụ sẽ sử dụng cấu trúc phiếu này.</span><a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.edit', $serviceResultTemplate) }}">Chỉnh sửa mẫu</a></div>
        @endif
    </section>
@endsection
