@extends('portal.layouts.app')
@section('title', 'Tài khoản bệnh nhân - Mediway')
@section('content')
<section class="patient-welcome">
    <div class="portal-shell patient-welcome__inner">
        <div>
            <p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p>
            <h1>Xin chào, {{ auth()->user()->patient->full_name }}</h1>
            <p>Chăm sóc sức khỏe của bạn, thuận tiện hơn mỗi ngày.</p>
        </div>
        <div class="patient-welcome__mark" aria-hidden="true"><span>✚</span></div>
    </div>
</section>
<section class="patient-services">
    <div class="portal-shell">
        <div class="patient-services__heading">
            <div><p class="portal-eyebrow">DỊCH VỤ CỦA BẠN</p><h2>Mọi thứ bạn cần, ở một nơi</h2><p>Tiện ích chăm sóc sức khỏe dành cho bạn.</p></div>
        </div>
        {{-- Các thẻ dẫn đến chức năng bệnh nhân; tài liệu nằm trong kết quả khám. --}}
        <div class="patient-services__grid">
            @foreach ([
                ['✚', 'Đặt lịch khám', 'Lựa chọn thời gian khám phù hợp với bạn.'],
                ['▤', 'Hồ sơ cá nhân', 'Quản lý thông tin cá nhân của bạn.'],
                ['♡', 'Hồ sơ sức khỏe', 'Theo dõi thông tin sức khỏe theo thời gian.'],
                ['✳', 'Kết quả khám bệnh', 'Xem kết quả và tài liệu trong hồ sơ của bạn.'],
                ['▧', 'Đơn thuốc', 'Tra cứu thông tin đơn thuốc của bạn.'],
                ['▣', 'Thanh toán', 'Theo dõi các khoản thanh toán khám chữa bệnh.'],
            ] as [$icon, $title, $description])
                @php($url = match ($title) { 'Hồ sơ sức khỏe' => route('patient.health-history'), 'Kết quả khám bệnh' => route('patient.results'), 'Đơn thuốc' => route('patient.prescriptions'), default => '#' })
                <a class="patient-service-card" href="{{ $url }}">
                    <span class="patient-service-card__icon" aria-hidden="true">{{ $icon }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $description }}</p>
                    <span class="patient-service-card__action">Khám phá <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
        <section class="patient-notifications" aria-labelledby="patient-notifications-title">
            <div class="patient-notifications__heading">
                <span class="patient-notifications__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-2 7-2 9h16c0-2-2-2-2-9M10 20h4"/></svg></span>
                <div><h2 id="patient-notifications-title">Thông báo</h2><p>Theo dõi thông tin khám và chăm sóc sức khỏe của bạn.</p></div>
                <a class="patient-notifications__link" href="{{ route('patient.notifications') }}">Xem thông báo <span aria-hidden="true">→</span></a>
            </div>
        </section>
        <div class="patient-help"><div><strong>Cần hỗ trợ sử dụng?</strong><p>Tìm hướng dẫn và thông tin hỗ trợ của Mediway.</p></div><a class="portal-text-link" href="{{ route('public.support') }}">Xem hỗ trợ <span aria-hidden="true">→</span></a></div>
    </div>
</section>
@endsection
