<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @yield('title', 'Khu vực bác sĩ') - Mediway
    </title>

    @vite([
        'resources/css/portal.css',
        'resources/css/portal-consistency.css',
        'resources/css/employee.css',
        'resources/js/portal.js',
        'resources/js/employee.js'
    ])

    @stack('styles')
</head>

<body class="portal-body employee-body">

    @php
        $pageTitle = trim($__env->yieldContent('title'));
    @endphp

    @include('doctor.partials.header')

    <div class="employee-shell">

        @include('doctor.partials.sidebar')

        <main class="employee-main" id="main-content">

            <nav class="employee-breadcrumb" aria-label="Đường dẫn">

                <ol>
                    @if(request()->routeIs('doctor.home'))

                        <li>
                            <span aria-current="page">
                                Tổng quan
                            </span>
                        </li>

                    @elseif(request()->routeIs('doctor.profile', 'doctor.examination.*'))

                        <li>
                            <a href="{{ route('doctor.home') }}">
                                Tổng quan
                            </a>
                        </li>

                        <li>
                            <span aria-current="page">
                                {{ $pageTitle }}
                            </span>
                        </li>

                    @endif
                </ol>
            </nav>

            @if(session('success'))
                <div class="portal-alert portal-alert--success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="portal-alert portal-alert--error" role="alert">

                    <strong>
                        Vui lòng kiểm tra thông tin.
                    </strong>

                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>
            @endif

            @yield('content')

        </main>
    </div>

    <footer class="employee-footer">
        <div>
            © {{ now()->year }} Mediway
            <span>·</span>
            Chăm sóc tận tâm, kết nối thuận tiện
        </div>
    </footer>

    @stack('scripts')

</body>

</html>