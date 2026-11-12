<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Khu vực bác sĩ') - Mediway</title>
    @vite(['resources/css/portal.css', 'resources/css/portal-consistency.css', 'resources/css/employee.css', 'resources/js/portal.js', 'resources/js/employee.js'])
</head>
<body class="portal-body employee-body">
<header class="employee-header"><div class="employee-header__inner">
    <a class="portal-brand" href="{{ route('doctor.home') }}"><span aria-hidden="true">✦</span> Mediway</a>
    <div class="employee-header__right">
        <div class="employee-account"><span class="employee-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($doctor->full_name), 0, 1)) }}</span><span class="employee-account__text"><small>Bác sĩ</small><strong>{{ $doctor->full_name }}</strong></span></div>
        <form method="post" action="{{ route('doctor.logout') }}">@csrf<button type="submit" class="portal-btn portal-btn--secondary employee-logout">Đăng xuất</button></form>
    </div>
</div></header>
<div class="employee-shell">
    <aside class="employee-sidebar">
        <details class="employee-menu" open><summary><span class="employee-menu__toggle-icon" aria-hidden="true">☰</span><span>Menu làm việc</span><span class="employee-menu__chevron" aria-hidden="true">⌄</span></summary>
            <nav aria-label="Điều hướng bác sĩ"><p class="employee-nav__label">KHÔNG GIAN LÀM VIỆC</p>
                <a class="employee-nav__item is-active" href="{{ route('doctor.home') }}"><x-employee-icon name="home" /><span>Lượt khám của tôi</span></a>
            </nav>
        </details>
        <div class="employee-sidebar__help"><x-employee-icon name="help" /><div><strong>Cần hỗ trợ?</strong><p>Thông tin và hướng dẫn sử dụng Mediway.</p><a href="{{ route('public.support') }}">Xem hỗ trợ →</a></div></div>
    </aside>
    <main class="employee-main" id="main-content">
        <nav class="employee-breadcrumb" aria-label="Đường dẫn"><ol><li><a href="{{ route('doctor.home') }}">Lượt khám của tôi</a></li>@hasSection('breadcrumb')<li>@yield('breadcrumb')</li>@endif</ol></nav>
        @yield('content')
    </main>
</div>
<footer class="employee-footer"><div>© {{ now()->year }} Mediway <span>·</span> Chăm sóc tận tâm, kết nối thuận tiện</div></footer>
</body>
</html>
