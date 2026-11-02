<header class="portal-header">
    <div class="portal-shell portal-header__inner">
        <a class="portal-brand" href="{{ route('public.home') }}" aria-label="Mediway về trang chủ"><span aria-hidden="true">✦</span> Mediway</a>
        <button class="portal-menu-toggle" type="button" data-portal-menu aria-controls="portal-navigation" aria-expanded="false"><span class="sr-only">Mở menu điều hướng</span><span aria-hidden="true">☰</span></button>
        <nav id="portal-navigation" class="portal-nav" aria-label="Điều hướng chính">
            <a href="{{ route('public.home') }}" @class(['is-active' => request()->routeIs('public.home')])>Trang chủ</a>
            <a href="{{ route('public.about') }}" @class(['is-active' => request()->routeIs('public.about')])>Về Mediway</a>
            <a href="{{ route('public.care-process') }}" @class(['is-active' => request()->routeIs('public.care-process')])>Quy trình khám</a>
            <a href="{{ route('public.news') }}" @class(['is-active' => request()->routeIs('public.news*')])>Tin tức</a>
            <a href="{{ route('public.support') }}" @class(['is-active' => request()->routeIs('public.support')])>Hỗ trợ</a>
            <div class="portal-nav__account">
                @auth
                    @if (auth()->user()->canAccessPortal())
                        <a href="{{ route('patient.health-history') }}">Hồ sơ sức khỏe</a>
                        <a href="{{ route('patient.results') }}">Kết quả khám</a>
                        <a href="{{ route('patient.prescriptions') }}">Đơn thuốc</a>
                        <a href="{{ route('patient.notifications') }}">Thông báo</a>
                        <a href="{{ route('patient.home') }}">Bệnh nhân · {{ auth()->user()->patient->full_name }}</a>
                        <form method="post" action="{{ route('patient.logout') }}">@csrf<button class="portal-nav__logout" type="submit">Đăng xuất</button></form>
                    @elseif (auth()->user()->canAccessAdmin())
                        <a href="{{ route('admin.dashboard') }}">Quản trị</a>
                    @endif
                @else
                    <a href="{{ route('patient.login') }}">Đăng nhập</a>
                    <a href="{{ route('patient.register') }}">Đăng ký</a>
                @endauth
            </div>
        </nav>
        <div class="portal-header__actions">
            @auth
                @if (auth()->user()->canAccessPortal())
                    <a class="portal-notification-link" href="{{ route('patient.notifications') }}" aria-label="Thông báo">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-2 7-2 9h16c0-2-2-2-2-9M10 20h4"/></svg>
                    </a>
                    <a class="portal-account" href="{{ route('patient.home') }}" aria-label="Tài khoản của {{ auth()->user()->patient->full_name }}">
                        <span class="portal-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(auth()->user()->patient->full_name), 0, 1)) }}</span>
                        <span class="portal-account__name"><small>Bệnh nhân</small><strong>{{ auth()->user()->patient->full_name }}</strong></span>
                    </a>
                    <form class="portal-header__logout" method="post" action="{{ route('patient.logout') }}">@csrf<x-portal.button type="submit" variant="secondary">Đăng xuất</x-portal.button></form>
                @elseif (auth()->user()->canAccessAdmin())
                    <x-portal.button variant="ghost" :href="route('admin.dashboard')">Quản trị</x-portal.button>
                @endif
            @else
                <x-portal.button variant="secondary" :href="route('patient.login')">Đăng nhập</x-portal.button>
                <x-portal.button variant="primary" :href="route('patient.register')">Đăng ký</x-portal.button>
            @endauth
        </div>
    </div>
</header>
