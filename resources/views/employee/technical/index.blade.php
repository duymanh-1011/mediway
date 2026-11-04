@extends('employee.layout')
@section('title', $title)
@section('content')
<section class="tech-page">
    <header class="tech-header"><p class="portal-eyebrow">DỊCH VỤ ĐƯỢC PHÂN CÔNG</p><h1>{{ $title }}</h1><p>Theo dõi chỉ định và cập nhật kết quả kỹ thuật.</p></header>
    {{-- Bộ lọc sử dụng ngày chỉ định và trạng thái dịch vụ hiện tại. --}}
    <form class="tech-filter" method="get"><label>Ngày chỉ định <input type="date" name="date" value="{{ $filters['date'] ?? '' }}"></label><label>Trạng thái <select name="status"><option value="">Tất cả</option>@foreach (['PENDING' => 'Chờ thực hiện', 'PROCESSING' => 'Đang thực hiện', 'COMPLETED' => 'Đã hoàn tất', 'CANCELLED' => 'Đã hủy'] as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label><div class="tech-filter__actions"><button type="submit" class="portal-btn portal-btn--primary">Lọc danh sách</button>{{-- Trở về danh sách không có tham số lọc hoặc trang cũ. --}}<a class="portal-btn portal-btn--secondary tech-filter__reset" href="{{ route($routePrefix.'.index') }}">Xóa lọc</a></div></form>
    <div class="tech-list">@forelse ($items as $item)<a class="tech-card" href="{{ route($routePrefix.'.show', $item->id) }}"><div><span class="tech-label">{{ $item->serviceOrder?->service_order_code ?? 'Chỉ định' }}</span><h2>{{ $item->medicalService?->name ?? 'Dịch vụ' }}</h2><p>Bệnh nhân: {{ $item->serviceOrder?->medicalVisit?->patient?->full_name ?? 'Chưa cập nhật' }}</p><p>Ngày chỉ định: {{ $item->serviceOrder?->ordered_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</p></div><span class="tech-status">{{ $item->serviceStatus?->description ?? 'Chưa cập nhật' }}</span></a>@empty<div class="tech-empty">Không có chỉ định phù hợp với bộ lọc hoặc phạm vi công việc của bạn.</div>@endforelse</div>
    {{ $items->links() }}
</section>
@endsection
