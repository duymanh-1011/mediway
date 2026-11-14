<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chọn chức năng bác sĩ - Mediway</title>
    @vite(['resources/css/portal.css', 'resources/css/portal-consistency.css', 'resources/css/employee.css', 'resources/js/portal.js', 'resources/js/employee.js'])
</head>
<body class="portal-body employee-body workspace-select-page">
    <header class="employee-header">
        <div class="employee-header__inner">
            <a class="portal-brand" href="{{ route('public.home') }}" aria-label="Mediway"><span aria-hidden="true">✦</span> Mediway</a>
            <div class="employee-header__right">
                <div class="employee-account" aria-label="Tài khoản bác sĩ {{ $employee->full_name }}">
                    <span class="employee-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($employee->full_name), 0, 1)) }}</span>
                    <span class="employee-account__text"><small>Bác sĩ</small><strong>{{ $employee->full_name }}</strong></span>
                </div>
                <form method="post" action="{{ route('doctor.logout') }}">@csrf<button type="submit" class="portal-btn portal-btn--secondary employee-logout">Đăng xuất</button></form>
            </div>
        </div>
    </header>

    <main class="workspace-select" id="main-content">
        <div class="workspace-select__intro">
            <span class="workspace-select__eyebrow">KHÔNG GIAN LÀM VIỆC</span>
            <h1>Xin chào, {{ $employee->full_name }}</h1>
            <p>Vui lòng chọn chức năng cho phiên làm việc này.</p>
        </div>
        <div class="workspace-select__grid" aria-label="Chọn chức năng bác sĩ">
            <form id="doctor-examination-select" method="post" action="{{ route('doctor.workspace.select') }}" hidden>
                @csrf<input type="hidden" name="workspace" value="examination">
            </form>
            <a class="workspace-choice workspace-choice--examination" href="#" data-select-workspace="doctor-examination-select">
                <span class="workspace-choice__icon"><x-employee-icon name="user" /></span>
                <span class="workspace-choice__title">Bác sĩ khám bệnh</span>
                <span class="workspace-choice__description">Không gian khám bệnh sẽ được kết nối khi hoàn thiện.</span>
                <span class="workspace-choice__action">Chọn chức năng <span aria-hidden="true">→</span></span>
                <span class="workspace-choice__glow" aria-hidden="true"></span>
            </a>
            <form method="post" action="{{ route('doctor.workspace.select') }}">
                @csrf<input type="hidden" name="workspace" value="services">
                <button type="submit" class="workspace-choice workspace-choice--services">
                    <span class="workspace-choice__icon"><x-employee-icon name="services" /></span>
                    <span class="workspace-choice__title">Bác sĩ thực hiện dịch vụ</span>
                    <span class="workspace-choice__description">Tiếp nhận chỉ định, nhập kỹ thuật và kết luận dịch vụ.</span>
                    <span class="workspace-choice__action">Vào chức năng <span aria-hidden="true">→</span></span>
                    <span class="workspace-choice__glow" aria-hidden="true"></span>
                </button>
            </form>
        </div>
        @if(session('success'))<div class="portal-alert portal-alert--success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="portal-alert portal-alert--error" role="alert">{{ $errors->first() }}</div>@endif
        <p class="workspace-select__note"><span aria-hidden="true">🔒</span> Chức năng đã chọn sẽ được khóa đến khi bạn đăng xuất.</p>
    </main>
    <script>
        document.querySelectorAll('[data-select-workspace]').forEach((card) => {
            card.addEventListener('click', (event) => {
                event.preventDefault();
                document.getElementById(card.dataset.selectWorkspace)?.requestSubmit();
            });
        });
    </script>
</body>
</html>
