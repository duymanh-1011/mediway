@extends(request()->routeIs('doctor.*') ? 'doctor.layout' : 'employee.layout')
@section('title', $service->name)
@section('content')
<div class="tech-page">
    <a class="portal-btn portal-btn--secondary tech-back" href="{{ $catalogueUrl }}">← Quay lại nhóm dịch vụ</a>

    @include('employee.technical._heading', [
        'eyebrow' => $groupName,
        'heading' => $service->name,
        'description' => 'Xem các phiếu chỉ định và tiến độ thực hiện của dịch vụ này.',
    ])

    <form class="employee-card workspace-filter" method="post" action="{{ $filterUrl }}" autocomplete="off" aria-label="Lọc chỉ định dịch vụ">
        @csrf
        <label class="workspace-filter__field workspace-filter__field--search">Tìm chỉ định
            <input type="search" name="q" value="{{ old('q', $filters['q']) }}" maxlength="100" placeholder="Mã phiếu, tên, mã hoặc SĐT bệnh nhân">
            @error('q')<span class="field-error">{{ $message }}</span>@enderror
        </label>
        <label class="workspace-filter__field">Trạng thái
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                @foreach (\App\Models\ServiceOrderItem::TECHNICAL_STAGES as $stage => $label)
                    <option value="{{ $stage }}" @selected(($filters['status'] ?? '') === $stage)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="workspace-filter__field">Ngày chỉ định
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}">
        </label>
        <x-workspace-filter-actions :reset-url="$ordersUrl" />
    </form>

    <section aria-labelledby="technical-list-title">
        <div class="employee-section__heading">
            <div><h2 id="technical-list-title">Danh sách phiếu chỉ định</h2></div>
            <span class="tech-service-link__count">{{ $items->total() }} phiếu</span>
        </div>
        <div class="tech-list">
            @forelse ($items as $item)
                @php
                    $displayStatus = \App\Support\TechnicalServiceStatus::for($item);
                    $order = $item->serviceOrder;
                    $patient = $order?->medicalVisit?->patient;
                @endphp
                <a class="employee-card tech-card" href="{{ route($routePrefix.'.show', ['item' => $item->id, ...$listQuery]) }}">
                    <div class="tech-card__body">
                        <p class="tech-label">Phiếu chỉ định</p>
                        <h3>{{ $order?->service_order_code ?? 'Chưa có mã phiếu' }}</h3>
                        <p>{{ $patient?->full_name ?? 'Chưa cập nhật bệnh nhân' }} @if ($patient?->patient_code) <span>· {{ $patient->patient_code }}</span>@endif</p>
                        @if ($order?->ordered_at)<p>{{ $order->ordered_at->format('d/m/Y H:i') }}</p>@endif
                    </div>
                    <div class="tech-card__meta">
                        <span class="tech-status tech-status--{{ $displayStatus['tone'] }}">{{ $displayStatus['label'] }}</span>
                        <span>{{ $item->performed_by_doctor_id ? 'Việc của tôi' : ($item->performed_by_employee_id ? 'Đang phân công cũ' : 'Chưa có người thực hiện') }}</span>
                        <span class="employee-card__link">Xem chỉ định <x-employee-icon name="arrow" /></span>
                    </div>
                </a>
            @empty
                <div class="employee-card tech-empty">
                    <h3>Không tìm thấy phiếu chỉ định</h3>
                    <p>Danh sách sẽ hiển thị khi có chỉ định phù hợp với dịch vụ và bộ lọc.</p>
                </div>
            @endforelse
        </div>
        @if ($items->hasPages())
            <div class="tech-pagination">{{ $items->links() }}</div>
        @endif
    </section>
</div>
@endsection
