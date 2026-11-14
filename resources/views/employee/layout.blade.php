<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Khu vực nhân viên') - Mediway</title>
    @vite(['resources/css/portal.css', 'resources/css/portal-consistency.css', 'resources/css/employee.css', 'resources/js/portal.js', 'resources/js/employee.js'])
    @stack('styles')
</head>
<body class="portal-body employee-body">
@php
    $workspaceMode = session('employee_workspace_mode');
    $isCashier = $workspaceMode === 'cashier';
    $workspaceLabel = $isCashier ? 'Nhân viên thu ngân' : 'Nhân viên tiếp đón';
    $homeRoute = 'employee.home';
    $navigation = $isCashier
        ? [['Thu ngân và thanh toán', 'employee.payments.index', 'employee.payments.index', 'payment']]
        : [['Tiếp đón bệnh nhân', 'employee.reception.index', 'employee.reception.index', 'reception']];
    $sectionPatterns = ['employee.reception.index' => ['employee.reception.*', 'employee.walk-in.*', 'employee.appointments.*'], 'employee.payments.index' => 'employee.payments.*'];
    $activeSection = collect($navigation)->first(fn ($item) => request()->routeIs($item[2], ...(array) ($sectionPatterns[$item[2]] ?? [])));
    $pageTitle = trim($__env->yieldContent('title'));
@endphp
<header class="employee-header">
    <div class="employee-header__inner">
        <a class="portal-brand" href="{{ route($homeRoute) }}" aria-label="Mediway - tổng quan {{ mb_strtolower($workspaceLabel) }}"><span aria-hidden="true">✦</span> Mediway</a>
        <div class="employee-header__right">
            <a class="employee-account" href="{{ route('employee.profile') }}" aria-label="Tài khoản của {{ $employee->full_name }}">
                <span class="employee-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($employee->full_name), 0, 1)) }}</span>
                <span class="employee-account__text"><small>{{ $workspaceLabel }}</small><strong>{{ $employee->full_name }}</strong></span>
            </a>
            <x-workspace-switch :action="route('employee.workspace.select')"
                :workspace="$isCashier ? 'reception' : 'cashier'"
                :label="$isCashier ? 'Nhân viên tiếp đón' : 'Nhân viên thu ngân'" />
            <form method="post" action="{{ route('employee.logout') }}">@csrf<button type="submit" class="portal-btn portal-btn--secondary employee-logout">Đăng xuất</button></form>
        </div>
    </div>
</header>
<div class="employee-shell">
    <aside class="employee-sidebar">
        <details class="employee-menu" open>
            <summary><span class="employee-menu__toggle-icon" aria-hidden="true">☰</span><span>Menu làm việc</span><span class="employee-menu__chevron" aria-hidden="true">⌄</span></summary>
            <nav aria-label="Điều hướng {{ mb_strtolower($workspaceLabel) }}">
                <p class="employee-nav__label">KHÔNG GIAN LÀM VIỆC</p>
                <a class="employee-nav__item {{ request()->routeIs($homeRoute) ? 'is-active' : '' }}" href="{{ route($homeRoute) }}" @if(request()->routeIs($homeRoute)) aria-current="page" @endif>
                    <x-employee-icon name="home" /><span>Tổng quan</span>
                </a>
                <p class="employee-nav__label">CHỨC NĂNG</p>
                @foreach ($navigation as [$label, $href, $routeName, $icon])
                    <a class="employee-nav__item {{ ($activeSection[2] ?? null) === $routeName ? 'is-active' : '' }}" href="{{ $href === '#' ? '#' : route($href) }}" @if($href === '#') aria-disabled="true" @elseif(request()->routeIs($routeName)) aria-current="page" @elseif(($activeSection[2] ?? null) === $routeName) aria-current="location" @endif><x-employee-icon :name="$icon" /><span>{{ $label }}</span></a>
                @endforeach
                    <p class="employee-nav__label">TÀI KHOẢN</p>
                    <a class="employee-nav__item {{ request()->routeIs('employee.profile*') ? 'is-active' : '' }}" href="{{ route('employee.profile') }}" @if(request()->routeIs('employee.profile*')) aria-current="page" @endif><x-employee-icon name="user" /><span>Hồ sơ cá nhân</span></a>
            </nav>
        </details>
        <x-workspace-guidance
            :context="$workspaceLabel"
            :stages="$isCashier ? [
                'Trước khi thu tiền' => [
                    'Đối chiếu đúng bệnh nhân, khoản thu và số tiền.',
                    'Kiểm tra phương thức thanh toán trước khi xác nhận.',
                ],
                'Sau khi ghi nhận thanh toán' => [
                    'Kiểm tra giao dịch thành công rồi gửi biên lai cho bệnh nhân.',
                ],
            ] : [
                'Trước khi tiếp nhận' => [
                    'Đối chiếu hồ sơ bệnh nhân và kiểm tra đã có lịch hẹn hay chưa.',
                ],
                'Sau khi tiếp nhận' => [
                    'Thông báo phòng khám và hướng dẫn thanh toán phí khám tại quầy nếu còn khoản phải thu.',
                ],
            ]"
        />
    </aside>
    <main class="employee-main" id="main-content">
        <nav class="employee-breadcrumb" aria-label="Đường dẫn">
            <ol>
                @if(request()->routeIs($homeRoute))
                    <li><span aria-current="page">Tổng quan</span></li>
                @else
                    <li><a href="{{ route($homeRoute) }}">Tổng quan</a></li>
                @endif
                @if(request()->routeIs('employee.profile.edit'))
                    <li><a href="{{ route('employee.profile') }}">Hồ sơ cá nhân</a></li>
                    <li><span aria-current="page">Chỉnh sửa</span></li>
                @elseif(request()->routeIs('employee.profile'))
                    <li><span aria-current="page">Hồ sơ cá nhân</span></li>
                @elseif($activeSection)
                    @if(request()->routeIs($activeSection[2]))
                        <li><span aria-current="page">{{ $activeSection[0] }}</span></li>
                    @else
                        <li><a href="{{ route($activeSection[2]) }}">{{ $activeSection[0] }}</a></li>
                        <li><span aria-current="page">{{ $pageTitle === $activeSection[0] ? 'Chi tiết' : $pageTitle }}</span></li>
                    @endif
                @endif
            </ol>
        </nav>
        @if(session('success'))<div class="portal-alert portal-alert--success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="portal-alert portal-alert--error" role="alert"><strong>Vui lòng kiểm tra thông tin.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
<footer class="employee-footer"><div>© {{ now()->year }} Mediway <span>·</span> Chăm sóc tận tâm, kết nối thuận tiện</div></footer>
</body>
</html>
