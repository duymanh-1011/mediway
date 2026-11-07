@extends('employee.layout')
@section('title', 'Thực hiện dịch vụ')
@section('content')
@include('employee.technical._heading', [
    'eyebrow' => 'KHU VỰC NHÂN VIÊN',
    'heading' => 'Thực hiện dịch vụ',
    'description' => 'Mở nhóm dịch vụ, chọn dịch vụ và xem các chỉ định cần xử lý.',
])

<section class="employee-section" aria-labelledby="technical-groups-title">
    <div class="employee-section__heading">
        <div>
            <p class="portal-eyebrow">KHÔNG GIAN LÀM VIỆC</p>
            <h2 id="technical-groups-title">Nhóm dịch vụ</h2>
            <p>Chọn nhóm để xem các dịch vụ và số chỉ định hiện tại.</p>
        </div>
        @if ($shortcutGroups)
            <a class="portal-btn portal-btn--secondary" href="{{ route('employee.services.index') }}">Xem tất cả nhóm</a>
        @endif
    </div>

    <div class="tech-catalogue">
        @forelse ($groups as $group)
            <details class="employee-card tech-group" id="nhom-{{ rawurlencode($group['code']) }}" @if ($openGroup === $group['code']) open @endif>
                <summary class="tech-group__summary">
                    <span class="employee-card__icon"><x-employee-icon name="services" /></span>
                    <span>{{ $group['name'] }}</span>
                    <span class="tech-group__chevron" aria-hidden="true"></span>
                </summary>
                <ul class="tech-group__services">
                    @forelse ($group['services'] as $service)
                        <li>
                            <a class="tech-service-link" href="{{ route('employee.services.orders', ['group' => $group['code'], 'service' => $service->service_code]) }}">
                                <span class="tech-service-link__name">{{ $service->name }}</span>
                                <span class="tech-service-link__count">{{ $service->current_order_count }} chỉ định hiện tại</span>
                                <x-employee-icon name="arrow" />
                            </a>
                        </li>
                    @empty
                        <li class="tech-group__empty">Nhóm này chưa có dịch vụ.</li>
                    @endforelse
                </ul>
            </details>
        @empty
            <div class="employee-card">Chưa có nhóm dịch vụ.</div>
        @endforelse
    </div>
</section>
@endsection
