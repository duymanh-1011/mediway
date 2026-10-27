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
                        <a href="{{ route('patient.home') }}">Tài khoản</a>
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
                    <x-portal.button variant="ghost" :href="route('patient.home')">Tài khoản</x-portal.button>
                @elseif (auth()->user()->canAccessAdmin())
                    <x-portal.button variant="ghost" :href="route('admin.dashboard')">Quản trị</x-portal.button>
                @endif
            @else
                <a class="portal-link" href="{{ route('patient.login') }}">Đăng nhập</a>
                <x-portal.button variant="primary" :href="route('patient.register')">Đăng ký</x-portal.button>
            @endauth
        </div>
    </div>
</header>
