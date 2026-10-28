@extends('portal.layouts.app')
@section('title', 'Tài khoản bệnh nhân - Mediway')
@section('content')
<section class="patient-welcome">
    <div class="portal-shell patient-welcome__inner">
        <div>
            <p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p>
            <h1>Xin chào, {{ $patient->full_name }}</h1>
            <p>Chăm sóc sức khỏe của bạn, thuận tiện hơn mỗi ngày.<br>Mã bệnh nhân: {{ $patient->patient_code }}</p>
        </div>
        <div class="patient-welcome__mark" aria-hidden="true"><span>✚</span></div>
    </div>
</section>
<section class="patient-services">
    <div class="portal-shell">
        <div class="patient-services__heading">
            <div><p class="portal-eyebrow">DỊCH VỤ CỦA BẠN</p><h2>Mọi thứ bạn cần, ở một nơi</h2><p>Tiện ích chăm sóc sức khỏe dành cho bạn.</p></div>
        </div>
        <div class="patient-services__grid">
            @foreach ([
                ['✚', 'Lịch khám', 'Xem lịch hẹn và lượt khám của bạn.'],
                ['▤', 'Hồ sơ cá nhân', 'Quản lý thông tin cá nhân của bạn.'],
                ['♡', 'Hồ sơ sức khỏe', 'Theo dõi thông tin sức khỏe theo thời gian.'],
                ['✳', 'Kết quả khám bệnh', 'Xem lại kết quả sau mỗi lần thăm khám.'],
                ['▧', 'Đơn thuốc', 'Tra cứu thông tin đơn thuốc của bạn.'],
                ['▣', 'Thanh toán', 'Theo dõi các khoản thanh toán khám chữa bệnh.'],
            ] as [$icon, $title, $description])
                @if (in_array($title, ['Lịch khám', 'Hồ sơ cá nhân'], true))
                <a class="patient-service-card" href="{{ $title === 'Lịch khám' ? route('patient.appointments') : route('patient.profile.show') }}">
                    <span class="patient-service-card__icon" aria-hidden="true">{{ $icon }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $description }}</p>
                    @if ($title === 'Hồ sơ cá nhân' && (! $patient->date_of_birth || ! $patient->gender))
                        <p>Vui lòng hoàn thiện thông tin hồ sơ.</p>
                    @endif
                    <span class="patient-service-card__status">{{ $title === 'Lịch khám' ? 'Xem lịch khám' : 'Xem hồ sơ' }} →</span>
                </a>
                @else
                <article class="patient-service-card">
                    <span class="patient-service-card__icon" aria-hidden="true">{{ $icon }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $description }}</p>
                    @if ($title === 'Thanh toán')
                        <p>Thông tin khoản cần thanh toán đang được cập nhật.</p>
                    @endif
                    <span class="patient-service-card__status"><span aria-hidden="true"></span>Chức năng chưa khả dụng</span>
                </article>
                @endif
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
@endsection
