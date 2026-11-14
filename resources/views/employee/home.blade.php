<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chọn chức năng - Mediway</title>
    @vite(['resources/css/portal.css', 'resources/css/portal-consistency.css', 'resources/css/employee.css', 'resources/js/portal.js', 'resources/js/employee.js'])
</head>
<body class="portal-body employee-body workspace-select-page">
    <header class="employee-header">
        <div class="employee-header__inner">
            <a class="portal-brand" href="{{ route('public.home') }}" aria-label="Mediway"><span aria-hidden="true">✦</span> Mediway</a>
            <div class="employee-header__right">
                <div class="employee-account" aria-label="Tài khoản nhân viên {{ $employee->full_name }}">
                    <span class="employee-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($employee->full_name), 0, 1)) }}</span>
                    <span class="employee-account__text"><small>Nhân viên</small><strong>{{ $employee->full_name }}</strong></span>
                </div>
                <form method="post" action="{{ route('employee.logout') }}">@csrf<button type="submit" class="portal-btn portal-btn--secondary employee-logout">Đăng xuất</button></form>
            </div>
        </div>
    </header>

    <main class="workspace-select" id="main-content">
        <div class="workspace-select__intro">
            <span class="workspace-select__eyebrow">KHÔNG GIAN LÀM VIỆC</span>
            <h1>Xin chào, {{ $employee->full_name }}</h1>
            <p>Vui lòng chọn chức năng cho phiên làm việc này.</p>
        </div>
        <div class="workspace-select__grid" aria-label="Chọn chức năng nhân viên">
            <form method="post" action="{{ route('employee.workspace.select') }}">
                @csrf<input type="hidden" name="workspace" value="reception">
                <button type="submit" class="workspace-choice workspace-choice--reception">
                    <span class="workspace-choice__icon"><x-employee-icon name="reception" /></span>
                    <span class="workspace-choice__title">Nhân viên tiếp đón</span>
                    <span class="workspace-choice__description">Tiếp nhận lịch hẹn, khám trực tiếp và theo dõi bệnh nhân.</span>
                    <span class="workspace-choice__action">Vào chức năng <span aria-hidden="true">→</span></span>
                    <span class="workspace-choice__glow" aria-hidden="true"></span>
                </button>
            </form>
            <form method="post" action="{{ route('employee.workspace.select') }}">
                @csrf<input type="hidden" name="workspace" value="cashier">
                <button type="submit" class="workspace-choice workspace-choice--cashier">
                    <span class="workspace-choice__icon"><x-employee-icon name="payment" /></span>
                    <span class="workspace-choice__title">Nhân viên thu ngân</span>
                    <span class="workspace-choice__description">Tra cứu khoản phải thu, ghi nhận thanh toán và in biên lai.</span>
                    <span class="workspace-choice__action">Vào chức năng <span aria-hidden="true">→</span></span>
                    <span class="workspace-choice__glow" aria-hidden="true"></span>
                </button>
            </form>
        </div>
        @if(session('success'))<div class="portal-alert portal-alert--success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="portal-alert portal-alert--error" role="alert">{{ $errors->first() }}</div>@endif
        <p class="workspace-select__note"><span aria-hidden="true">🔒</span> Chức năng đã chọn sẽ được khóa đến khi bạn đăng xuất.</p>
    </main>
</body>
</html>
