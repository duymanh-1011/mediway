@extends('employee.layout')
@section('title', $title)
@section('content')
<section class="tech-page">
    <header class="tech-header">
        <div>
            <p class="portal-eyebrow">KỸ THUẬT VIÊN DỊCH VỤ Y TẾ</p>
            <h1>{{ $title }}</h1>
            <p>Tra cứu chỉ định, nhận dịch vụ đủ điều kiện và ghi kết quả cho từng bệnh nhân.</p>
        </div>
        <span class="tech-header__icon"><x-employee-icon name="services" /></span>
    </header>

    @if ($shortcutGroups)
        <p><a class="portal-btn portal-btn--secondary" href="{{ route('employee.services.index') }}">Xem tất cả nhóm dịch vụ</a></p>
    @endif

    <form class="tech-panel tech-filter" method="get" action="{{ route($routePrefix.'.index') }}" aria-label="Lọc chỉ định dịch vụ">
        <label class="tech-filter__search">Tìm chỉ định
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Tên dịch vụ, mã phiếu hoặc bệnh nhân">
        </label>
        <label>Người thực hiện
            <select name="assignee">
                <option value="">Tất cả công việc hiển thị</option>
                <option value="mine" @selected(($filters['assignee'] ?? '') === 'mine')>Việc của tôi</option>
                <option value="unassigned" @selected(($filters['assignee'] ?? '') === 'unassigned')>Chưa có người thực hiện</option>
            </select>
        </label>
        @unless ($shortcutGroups)
            <label>Nhóm dịch vụ
                <select name="group">
                    <option value="">Tất cả nhóm</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->service_group_code }}" @selected(($filters['group'] ?? '') === $group->service_group_code)>{{ $group->name }}</option>
                    @endforeach
                </select>
            </label>
        @endunless
        <label>Trạng thái
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                @foreach (\App\Models\ServiceOrderItem::TECHNICAL_STAGES as $stage => $label)
                    <option value="{{ $stage }}" @selected(($filters['status'] ?? '') === $stage)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Ngày chỉ định
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}">
        </label>
        <div class="tech-actions">
            <button type="submit" class="portal-btn portal-btn--primary">Lọc danh sách</button>
            <a class="portal-btn portal-btn--secondary" href="{{ route($routePrefix.'.index') }}">Xóa bộ lọc</a>
        </div>
    </form>

    <section aria-labelledby="technical-list-title">
        <div class="tech-list-heading">
            <h2 id="technical-list-title">Danh sách chỉ định</h2>
            <span>{{ $items->total() }} chỉ định</span>
        </div>
        <div class="tech-list">
            @forelse ($items as $item)
                @php
                    $stage = $item->technicalStage();
                    $order = $item->serviceOrder;
                    $patient = $order?->medicalVisit?->patient;
                    $service = $item->medicalService;
                @endphp
                <a class="tech-card" href="{{ route($routePrefix.'.show', ['item' => $item->id, ...$listQuery]) }}">
                    <div class="tech-card__body">
                        <p class="tech-label">{{ $service?->serviceGroups->pluck('name')->join(' · ') ?: 'Chưa phân nhóm' }}</p>
                        <h3>{{ $service?->name ?? 'Dịch vụ y tế' }}</h3>
                        <p>{{ $patient?->full_name ?? 'Chưa cập nhật bệnh nhân' }} @if ($patient?->patient_code) <span>· {{ $patient->patient_code }}</span>@endif</p>
                        <p>{{ $order?->service_order_code ?? 'Chưa có mã phiếu' }} @if ($order?->ordered_at) <span>· {{ $order->ordered_at->format('d/m/Y H:i') }}</span>@endif</p>
                    </div>
                    <div class="tech-card__meta">
                        <span class="tech-status" data-stage="{{ $stage }}">{{ \App\Models\ServiceOrderItem::TECHNICAL_STAGES[$stage] ?? 'Chưa cập nhật' }}</span>
                        <span>{{ $item->performed_by_employee_id ? 'Việc của tôi' : 'Chưa có người thực hiện' }}</span>
                        <span class="tech-card__link">Xem chỉ định <x-employee-icon name="arrow" /></span>
                    </div>
                </a>
            @empty
                <div class="tech-empty">
                    <h3>Không tìm thấy chỉ định</h3>
                    <p>Thử đổi từ khóa hoặc bộ lọc để xem công việc khác.</p>
                </div>
            @endforelse
        </div>
        @if ($items->hasPages())
            <div class="tech-pagination">{{ $items->links() }}</div>
        @endif
    </section>
</section>
@endsection
