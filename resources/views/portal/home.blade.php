@extends('portal.layouts.app')
@section('title', 'Tài khoản bệnh nhân - Mediway')
@section('content')
<div class="patient-home">
    <section class="patient-welcome">
        <div class="portal-shell patient-welcome__inner">
            <div class="patient-welcome__copy">
                <p class="portal-eyebrow">CỔNG THÔNG TIN BỆNH NHÂN</p>
                <h1>Xin chào, {{ $patient->full_name }}</h1>
                <p>Chăm sóc sức khỏe của bạn, thuận tiện hơn mỗi ngày.<br>Mã bệnh nhân: {{ $patient->patient_code }}</p>
                <a class="patient-welcome__link" href="#dich-vu">Khám phá dịch vụ <span aria-hidden="true">↓</span></a>
            </div>
            <div class="patient-welcome__art" aria-hidden="true">
                <div class="patient-welcome__art-ring"></div>
                <div class="patient-welcome__art-card"><svg viewBox="0 0 48 48" fill="none"><rect x="5" y="5" width="38" height="38" rx="13" fill="currentColor"/><path d="M24 15v18M15 24h18" stroke="white" stroke-width="4" stroke-linecap="round"/></svg></div>
                <span class="patient-welcome__art-dot patient-welcome__art-dot--one"></span><span class="patient-welcome__art-dot patient-welcome__art-dot--two"></span>
            </div>
        </div>
    </section>

    <section class="patient-services" id="dich-vu" aria-labelledby="patient-services-title">
        <div class="portal-shell">
            <div class="patient-services__heading">
                <div><p class="portal-eyebrow">DỊCH VỤ CỦA BẠN</p><h2 id="patient-services-title">Chăm sóc sức khỏe, theo cách của bạn</h2><p>Chọn dịch vụ bạn cần để bắt đầu.</p></div>
            </div>
            <div class="patient-services__grid">
                @foreach ([
                    ['Lịch khám', 'Xem lịch hẹn của bạn.', '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-9 3v5m-2.5-2.5h5"/>'],
                    ['Hồ sơ cá nhân', 'Quản lý thông tin cá nhân của bạn.', '<rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="9" r="2.5"/><path d="M7.5 17c.6-2.1 2.2-3.2 4.5-3.2s3.9 1.1 4.5 3.2"/>'],
                    ['Hồ sơ sức khỏe', 'Theo dõi thông tin sức khỏe theo thời gian.', '<path d="M20.8 5.8a5.3 5.3 0 0 0-7.5 0L12 7.1l-1.3-1.3a5.3 5.3 0 0 0-7.5 7.5L12 22l8.8-8.7a5.3 5.3 0 0 0 0-7.5Z"/><path d="M6 12h3l1.5-2.5 2.5 5 1.5-2.5H18"/>'],
                    ['Kết quả khám bệnh', 'Xem lại kết quả sau mỗi lần thăm khám.', '<path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h4M9 12h6m-6 4h6"/>'],
                    ['Đơn thuốc', 'Tra cứu thông tin đơn thuốc của bạn.', '<path d="M8 4h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4Z"/><path d="M12 8v8m-4-4h8"/>'],
                    ['Thanh toán', 'Theo dõi các khoản thanh toán khám chữa bệnh.', '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 9h18M7 15h4"/>'],
                ] as [$title, $description, $icon])
                    @php($destination = match ($title) { 'Lịch khám' => route('patient.appointments'), 'Hồ sơ cá nhân' => route('patient.profile.show'), default => null })
                    @if ($destination)<a class="patient-service-card" href="{{ $destination }}">@else<article class="patient-service-card">@endif
                        <span class="patient-service-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></span>
                        <span class="patient-service-card__body"><h3>{{ $title }}</h3><p>{{ $description }}</p>
                            @if ($title === 'Hồ sơ cá nhân' && (! $patient->date_of_birth || ! $patient->gender))<p>Vui lòng hoàn thiện thông tin hồ sơ.</p>@endif
                            @if ($title === 'Thanh toán')<p>Thông tin khoản cần thanh toán đang được cập nhật.</p>@endif
                            @if (! $destination)<span class="patient-service-card__status">Chức năng chưa khả dụng</span>@endif
                        </span>
                        @if ($destination)<span class="patient-service-card__arrow" aria-hidden="true">→</span></a>@else</article>@endif
                @endforeach
            </div>
            <section class="patient-notifications" aria-labelledby="patient-notifications-title">
                <div class="patient-notifications__heading">
                    <span class="patient-notifications__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-2 7-2 9h16c0-2-2-2-2-9M10 20h4"/></svg></span>
                    <div><h2 id="patient-notifications-title">Thông báo</h2><p>Chưa có thông báo để hiển thị.</p></div>
                    <a class="patient-notifications__link" href="{{ route('patient.notifications') }}">Xem thông báo <span aria-hidden="true">→</span></a>
                </div>
            </section>
            <div class="patient-help"><div><strong>Cần hỗ trợ sử dụng?</strong><p>Tìm hướng dẫn và thông tin hỗ trợ của Mediway.</p></div><a class="portal-text-link" href="{{ route('public.support') }}">Xem hỗ trợ <span aria-hidden="true">→</span></a></div>
        </div>
    </section>
</div>
@endsection
