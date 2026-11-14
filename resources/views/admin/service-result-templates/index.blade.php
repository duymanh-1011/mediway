@extends('admin.layouts.app')
@section('title', 'Mẫu phiếu kết quả - Mediway')
@section('admin-page', 'service-result-templates-index')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a><span aria-hidden="true">/</span><span>Dịch vụ</span><span aria-hidden="true">/</span><strong aria-current="page">Mẫu phiếu kết quả</strong>
@endsection
@section('content')
    <div class="page-header"><div><h1 class="page-title">Mẫu phiếu kết quả</h1><p class="page-description">Tạo và quản lý cấu trúc phiếu dùng chung. Gán mẫu cho dịch vụ để nhân viên ghi kết quả theo đúng biểu mẫu.</p></div><a class="admin-btn admin-btn-primary" href="{{ route('admin.service-result-templates.create') }}">Thêm mẫu phiếu</a></div>
    <div class="card">
        <h2 class="card-title">Danh sách mẫu</h2>
        <div class="table-responsive"><table class="admin-table"><thead><tr><th scope="col">STT</th><th scope="col">Tên mẫu</th><th scope="col">Trường dữ liệu</th><th scope="col">Dịch vụ đang dùng</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead><tbody>
            @forelse ($templates as $template)
                <tr><td class="admin-row-number">{{ $templates->firstItem() + $loop->index }}</td><td><strong>{{ $template->name }}</strong>@if ($template->description)<br><small>{{ $template->description }}</small>@endif</td><td>{{ count($template->fields ?? []) }}</td><td>{{ $template->medical_services_count }}</td><td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $template->is_active ? 'ACTIVE' : 'INACTIVE'])</td><td><a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.edit', $template) }}">Sửa</a></td></tr>
            @empty
                <tr><td colspan="6" class="text-center">Chưa có mẫu phiếu. Hãy tạo mẫu để gán cho dịch vụ.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrapper">{{ $templates->links() }}</div>
    </div>
@endsection
