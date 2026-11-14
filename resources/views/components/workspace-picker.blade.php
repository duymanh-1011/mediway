@props(['account', 'role', 'area', 'selectUrl', 'logoutUrl', 'choices'])

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chọn chức năng {{ mb_strtolower($role) }} - Mediway</title>
    @vite(['resources/css/portal.css', 'resources/css/portal-consistency.css', 'resources/css/employee.css', 'resources/js/portal.js'])
</head>
<body class="portal-body employee-body workspace-select-page">
    <header class="employee-header">
        <div class="employee-header__inner">
            <a class="portal-brand" href="{{ route('public.home') }}" aria-label="Mediway - Trang chủ">
                <span aria-hidden="true">✦</span> Mediway
            </a>
            <div class="employee-header__right">
                <div class="employee-account">
                    <span class="employee-account__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($account), 0, 1)) }}</span>
                    <span class="employee-account__text"><small>{{ $role }}</small><strong>{{ $account }}</strong></span>
                </div>
                <form method="post" action="{{ $logoutUrl }}">
                    @csrf
                    <x-portal.button type="submit" variant="secondary" class="employee-logout">Đăng xuất</x-portal.button>
                </form>
            </div>
        </div>
    </header>

    <main class="workspace-select" id="main-content">
        <section class="workspace-select__intro" aria-labelledby="workspace-welcome">
            <div class="workspace-select__welcome">
                <p class="workspace-select__eyebrow">{{ $area }}</p>
                <h1 id="workspace-welcome">Xin chào, <span>{{ $account }}</span></h1>
            </div>
            <div class="workspace-select__hint">
                <span class="workspace-select__hint-icon"><x-employee-icon name="switch" /></span>
                <p>Bạn có thể chuyển chức năng ngay trên thanh đầu trang trong ca làm việc.</p>
            </div>
        </section>

        <section class="workspace-select__panel" aria-labelledby="workspace-choice-title">
            @if(session('success'))
                <p class="portal-alert portal-alert--success" role="status">{{ session('success') }}</p>
            @endif
            @if($errors->any())
                <p class="portal-alert portal-alert--error" role="alert">{{ $errors->first() }}</p>
            @endif
            <div class="workspace-select__heading">
                <h2 id="workspace-choice-title">Chọn chức năng làm việc</h2>
            </div>
            <div class="workspace-select__grid">
                @foreach ($choices as $choice)
                    <form method="post" action="{{ $selectUrl }}">
                        @csrf
                        <input type="hidden" name="workspace" value="{{ $choice['value'] }}">
                        <button type="submit" class="workspace-choice">
                            <span class="workspace-choice__icon"><x-employee-icon :name="$choice['icon']" /></span>
                            <span class="workspace-choice__copy">
                                <span class="workspace-choice__title">{{ $choice['title'] }}</span>
                                <span class="workspace-choice__description">{{ $choice['description'] }}</span>
                            </span>
                            <span class="workspace-choice__action" aria-hidden="true">Mở <x-employee-icon name="arrow" /></span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>
    </main>
    <footer class="employee-footer"><div>© {{ now()->year }} Mediway</div></footer>
</body>
</html>
