<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Khu vực nhân viên') - Mediway</title>
    @vite(['resources/css/portal.css', 'resources/css/portal-consistency.css', 'resources/css/employee.css', 'resources/js/portal.js', 'resources/js/employee.js'])
</head>
<body class="portal-body employee-body">
@php
    $employeeType = $employee->detail->employee_type;
    $employeeTypeName = \App\Support\EmployeeAccess::TYPES[$employeeType];
    $navigation = [
        ['Tiếp đón', 'RECEPTIONIST', 'employee.reception.index', 'reception'],
        ['Lịch hẹn và tiếp nhận', 'RECEPTIONIST', 'employee.appointments.index', 'calendar'],
        ['Hàng đợi hành chính', 'RECEPTIONIST', 'employee.queue.index', 'queue'],
        ['Xét nghiệm', 'LAB_TECHNICIAN', 'employee.laboratory.index', 'lab'],
        ['Chẩn đoán hình ảnh', 'IMAGING_TECHNICIAN', 'employee.imaging.index', 'image'],
        ['Thu ngân và thanh toán', 'CASHIER', 'employee.payments.index', 'payment'],
    ];
@endphp
<header class="employee-header">
    <div class="employee-header__inner">
        <a class="portal-brand" href="{{ route('employee.home') }}" aria-label="Mediway - tổng quan nhân viên"><span aria-hidden="true">✦</span> Mediway</a>
        <div class="employee-header__right">
            <a class="employee-account" href="{{ route('employee.profile') }}" aria-label="Hồ sơ của {{ $employee->full_name }}">
                <span class="employee-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($employee->full_name), 0, 1)) }}</span>
                <span class="employee-account__text"><small>{{ $employeeTypeName }}</small><strong>{{ $employee->full_name }}</strong></span>
            </a>
            <form method="post" action="{{ route('employee.logout') }}">@csrf<button type="submit" class="portal-btn portal-btn--secondary employee-logout">Đăng xuất</button></form>
        </div>
    </div>
</header>
<div class="employee-shell">
    <aside class="employee-sidebar">
        <details class="employee-menu" open>
            <summary><span class="employee-menu__toggle-icon" aria-hidden="true">☰</span><span>Menu làm việc</span><span class="employee-menu__chevron" aria-hidden="true">⌄</span></summary>
            <nav aria-label="Điều hướng nhân viên">
                <p class="employee-nav__label">KHÔNG GIAN LÀM VIỆC</p>
                <a class="employee-nav__item {{ request()->routeIs('employee.home') ? 'is-active' : '' }}" href="{{ route('employee.home') }}" @if(request()->routeIs('employee.home')) aria-current="page" @endif>
                    <x-employee-icon name="home" /><span>Tổng quan</span>
                </a>
                <p class="employee-nav__label">CHỨC NĂNG</p>
                @foreach ($navigation as [$label, $type, $routeName, $icon])
                    @if ($employeeType === $type && \Illuminate\Support\Facades\Route::has($routeName))
                        <a class="employee-nav__item {{ request()->routeIs($routeName) ? 'is-active' : '' }}" href="{{ route($routeName) }}" @if(request()->routeIs($routeName)) aria-current="page" @endif><x-employee-icon :name="$icon" /><span>{{ $label }}</span></a>
                    @else
                        <div class="employee-nav__item employee-nav__item--disabled" aria-label="{{ $label }}: {{ $employeeType === $type ? 'Chưa tích hợp' : 'Không có quyền' }}"><x-employee-icon :name="$icon" /><span>{{ $label }}<small>{{ $employeeType === $type ? 'Chưa tích hợp' : 'Không có quyền' }}</small></span><x-employee-icon name="lock" /></div>
                    @endif
                @endforeach
                <p class="employee-nav__label">TÀI KHOẢN</p>
                <a class="employee-nav__item {{ request()->routeIs('employee.profile*') ? 'is-active' : '' }}" href="{{ route('employee.profile') }}" @if(request()->routeIs('employee.profile*')) aria-current="page" @endif><x-employee-icon name="user" /><span>Hồ sơ cá nhân</span></a>
            </nav>
        </details>
        <div class="employee-sidebar__help"><x-employee-icon name="help" /><div><strong>Cần hỗ trợ?</strong><p>Thông tin và hướng dẫn sử dụng Mediway.</p><a href="{{ route('public.support') }}">Xem hỗ trợ <span aria-hidden="true">→</span></a></div></div>
    </aside>
    <main class="employee-main" id="main-content">
        <nav class="employee-breadcrumb" aria-label="Đường dẫn"><ol><li><a href="{{ route('employee.home') }}">Tổng quan</a></li>@if(request()->routeIs('employee.profile*'))<li>@if(request()->routeIs('employee.profile.edit'))<a href="{{ route('employee.profile') }}">Hồ sơ cá nhân</a>@else<span aria-current="page">Hồ sơ cá nhân</span>@endif</li>@endif @if(request()->routeIs('employee.profile.edit'))<li><span aria-current="page">Chỉnh sửa</span></li>@endif</ol></nav>
        @if(session('success'))<div class="portal-alert portal-alert--success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="portal-alert portal-alert--error" role="alert"><strong>Vui lòng kiểm tra thông tin.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
<footer class="employee-footer"><div>© {{ now()->year }} Mediway <span>·</span> Chăm sóc tận tâm, kết nối thuận tiện</div></footer>
</body>
</html>
