<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Không có quyền truy cập - Mediway</title>

    @vite([
        'resources/css/portal.css',
        'resources/css/portal-consistency.css',
        'resources/css/employee.css'
    ])
</head>

<body class="portal-body employee-body">

    <main class="employee-main portal-shell">

        <div class="employee-card">

            <h1>Không có quyền truy cập</h1>

            <p>{{ $message }}</p>

            <a href="{{ route('patient.login') }}" class="portal-btn portal-btn--secondary">
                Quay lại đăng nhập
            </a>

        </div>

    </main>

</body>

</html>
