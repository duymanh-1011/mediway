@php
    $signedInEmployee = auth()->check() && auth()->user()->role?->name === 'Employee'
        ? app(\App\Support\EmployeeAccess::class)->employee(auth()->user())
        : null;
@endphp
<header class="portal-header">
    <div class="portal-shell portal-header__inner">
        <a class="portal-brand" href="{{ route('public.home') }}" aria-label="Mediway về trang chủ"><span aria-hidden="true">✦</span> Mediway</a>
        <button class="portal-menu-toggle" type="button" data-portal-menu aria-controls="portal-navigation" aria-expanded="false"><span class="sr-only">Mở menu điều hướng</span><span aria-hidden="true">☰</span>@auth @if (auth()->user()->canAccessPortal())<span class="portal-notification-count portal-notification-count--menu" data-notification-count hidden></span>@endif @endauth</button>
        <nav id="portal-navigation" class="portal-nav" aria-label="Điều hướng chính">
            <a href="{{ route('public.home') }}" @class(['is-active' => request()->routeIs('public.home')]) @if(request()->routeIs('public.home')) aria-current="page" @endif>Trang chủ</a>
            <a href="{{ route('public.about') }}" @class(['is-active' => request()->routeIs('public.about')]) @if(request()->routeIs('public.about')) aria-current="page" @endif>Về Mediway</a>
            <a href="{{ route('public.care-process') }}" @class(['is-active' => request()->routeIs('public.care-process')]) @if(request()->routeIs('public.care-process')) aria-current="page" @endif>Quy trình khám</a>
            <a href="{{ route('public.news') }}" @class(['is-active' => request()->routeIs('public.news*')]) @if(request()->routeIs('public.news*')) aria-current="{{ request()->routeIs('public.news') ? 'page' : 'location' }}" @endif>Tin tức</a>
            <a href="{{ route('public.support') }}" @class(['is-active' => request()->routeIs('public.support')]) @if(request()->routeIs('public.support')) aria-current="page" @endif>Hỗ trợ</a>
            <div class="portal-nav__account">
                @auth
                    @if (auth()->user()->canAccessPortal())
                        <a href="{{ route('patient.health-history') }}">Hồ sơ sức khỏe</a>
                        <a href="{{ route('patient.results') }}">Kết quả khám</a>
                        <a href="{{ route('patient.prescriptions') }}">Đơn thuốc</a>
                        <a href="{{ route('patient.notifications') }}">Thông báo <span data-notification-mobile-count></span></a>
                        <a href="{{ route('patient.home') }}">Bệnh nhân · {{ auth()->user()->patient->full_name }}</a>
                        <form method="post" action="{{ route('patient.logout') }}">@csrf<button class="portal-nav__logout" type="submit">Đăng xuất</button></form>
                    @elseif ($signedInEmployee)
                        <a href="{{ route(\App\Support\EmployeeAccess::homeRoute($signedInEmployee)) }}">Khu vực nhân viên</a>
                        <form method="post" action="{{ route('employee.logout') }}">@csrf<button class="portal-nav__logout" type="submit">Đăng xuất</button></form>
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
                    <a class="portal-notification-link" href="{{ route('patient.notifications') }}" aria-label="Thông báo" data-notification-link data-notification-account="{{ auth()->id() }}" data-notification-url="{{ route('patient.notifications') }}">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-2 7-2 9h16c0-2-2-2-2-9M10 20h4"/></svg>
                        <span class="portal-notification-count" data-notification-count hidden></span>
                    </a>
                    <a class="portal-account" href="{{ route('patient.home') }}" aria-label="Tài khoản của {{ auth()->user()->patient->full_name }}">
                        <span class="portal-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(auth()->user()->patient->full_name), 0, 1)) }}</span>
                        <span class="portal-account__name"><small>Bệnh nhân</small><strong>{{ auth()->user()->patient->full_name }}</strong></span>
                    </a>
                    <form class="portal-header__logout" method="post" action="{{ route('patient.logout') }}">@csrf<x-portal.button type="submit" variant="secondary">Đăng xuất</x-portal.button></form>
                @elseif ($signedInEmployee)
                    <x-portal.button variant="secondary" :href="route(\App\Support\EmployeeAccess::homeRoute($signedInEmployee))">Khu vực nhân viên</x-portal.button>
                    <form class="portal-header__logout" method="post" action="{{ route('employee.logout') }}">@csrf<x-portal.button type="submit" variant="secondary">Đăng xuất</x-portal.button></form>
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
@auth
    @if (auth()->user()->canAccessPortal())
        <div class="portal-shell" data-notification-refresh-status role="status" hidden>
            <p class="portal-connection-note">Chưa thể cập nhật thông báo. Hệ thống sẽ tự thử lại.</p>
        </div>
        <aside class="portal-toast" data-notification-toast hidden aria-label="Thông báo mới">
            <form method="post" action="{{ route('patient.notifications.open') }}">
                @csrf
                <input type="hidden" name="notification_key" data-toast-key>
                <button type="submit" class="portal-toast__open">
                    <span class="portal-toast__icon" aria-hidden="true">●</span>
                    <span><small data-toast-category></small><strong data-toast-title></strong><small data-toast-detail hidden></small><span class="portal-toast__hint">Xem chi tiết →</span></span>
                </button>
            </form>
            <button type="button" class="portal-toast__close" data-toast-close aria-label="Đóng thông báo">×</button>
        </aside>
        <p class="sr-only" data-toast-announcement aria-live="polite" aria-atomic="true"></p>
    @endif
@endauth
