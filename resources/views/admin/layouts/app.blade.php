<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>@yield('title', 'Mediway Admin')</title>

    @vite('resources/css/app.css')

    <style>
        :root {
            --sidebar-width: 270px;

            --primary: #2563eb;
            --primary-dark: #1d4ed8;

            --sidebar-bg: #0f172a;
            --sidebar-bg-hover: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-muted: #64748b;

            --background: #f4f7fb;
            --card: #ffffff;

            --text: #0f172a;
            --muted: #64748b;

            --border: #e2e8f0;

            --success: #059669;
            --danger: #dc2626;

            --shadow-sm:
                0 1px 2px rgba(15, 23, 42, 0.04);

            --shadow-md:
                0 6px 20px rgba(15, 23, 42, 0.07);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;

            font-family:
                "Times New Roman",
                Times,
                serif;

            background: var(--background);
            color: var(--text);
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        a {
            color: inherit;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .admin-sidebar {
            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: var(--sidebar-width);

            background: var(--sidebar-bg);
            color: #fff;

            overflow-y: auto;
            overflow-x: hidden;

            border-right: 1px solid rgba(255, 255, 255, 0.05);

            z-index: 100;
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 999px;
        }

        .sidebar-brand {
            padding: 22px 18px 18px;

            border-bottom:
                1px solid rgba(255, 255, 255, 0.06);
        }

        .sidebar-brand-title {
            margin: 0;

            font-size: 22px;
            font-weight: 800;

            letter-spacing: -0.4px;
        }

        .sidebar-brand-subtitle {
            margin-top: 4px;

            font-size: 12px;
            color: #94a3b8;
        }

        .sidebar-nav {
            padding: 14px 12px 28px;
        }

        .sidebar-section {
            margin-bottom: 18px;
        }

        .sidebar-section-title {
            padding: 7px 10px;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 10px 12px;
            margin-bottom: 3px;

            border-radius: 8px;

            color: var(--sidebar-text);

            text-decoration: none;

            font-size: 13px;
            font-weight: 500;

            transition:
                background-color 0.18s ease,
                color 0.18s ease,
                transform 0.18s ease;
        }

        .sidebar-link:hover {
            background: var(--sidebar-bg-hover);
            color: #fff;

            transform: translateX(2px);
        }

        .sidebar-link.active {
            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #fff;

            box-shadow:
                0 6px 18px rgba(37, 99, 235, 0.22);
        }

        .sidebar-link-dot {
            width: 7px;
            height: 7px;

            flex-shrink: 0;

            border-radius: 999px;

            background: currentColor;

            opacity: 0.6;
        }

        .sidebar-link.active .sidebar-link-dot {
            opacity: 1;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .admin-main {
            width:
                calc(
                    100% - var(--sidebar-width)
                );

            margin-left: var(--sidebar-width);

            min-height: 100vh;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .admin-header {
            position: sticky;

            top: 0;

            height: 68px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 24px;

            background:
                rgba(255, 255, 255, 0.96);

            backdrop-filter: blur(10px);

            border-bottom:
                1px solid var(--border);

            box-shadow: var(--shadow-sm);

            z-index: 50;
        }

        .header-left-title {
            font-size: 15px;
            font-weight: 700;

            color: #334155;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-avatar {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #60a5fa
                );

            color: #fff;

            font-weight: 700;
            font-size: 14px;
        }

        .header-user-info {
            text-align: right;
        }

        .header-user-name {
            font-size: 13px;
            font-weight: 700;

            color: #0f172a;
        }

        .header-user-role {
            margin-top: 2px;

            font-size: 11px;

            color: #94a3b8;
        }

        .logout-button {
            padding: 8px 13px;

            border: 1px solid var(--border);
            border-radius: 8px;

            background: #fff;

            color: #475569;

            cursor: pointer;

            font-size: 12px;
            font-weight: 600;

            transition:
                background-color 0.18s ease,
                border-color 0.18s ease,
                color 0.18s ease;
        }

        .logout-button:hover {
            background: #fef2f2;
            border-color: #fecaca;

            color: var(--danger);
        }

        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .admin-content {
            padding: 24px;
        }

        .breadcrumb {
            margin-bottom: 12px;

            font-size: 12px;

            color: #94a3b8;
        }

        .page-title {
            margin:
                0
                0
                4px;

            font-size: 26px;
            font-weight: 800;

            letter-spacing: -0.5px;

            color: #0f172a;
        }

        .page-description {
            margin:
                0
                0
                22px;

            font-size: 13px;

            color: #64748b;
        }

        /*
        |--------------------------------------------------------------------------
        | CARD
        |--------------------------------------------------------------------------
        */

        .card {
            background: var(--card);

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 20px;

            box-shadow: var(--shadow-sm);
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .alert {
            margin-bottom: 16px;

            padding: 12px 14px;

            border-radius: 9px;

            font-size: 13px;
        }

        .alert-success {
            background: #ecfdf5;

            border: 1px solid #a7f3d0;

            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;

            border: 1px solid #fecaca;

            color: #991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | COMMON BUTTONS
        |--------------------------------------------------------------------------
        */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 6px;

            padding: 9px 14px;

            border: 1px solid transparent;
            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;

            transition:
                background-color 0.18s ease,
                border-color 0.18s ease,
                color 0.18s ease,
                transform 0.18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #fff;
            border-color: var(--border);

            color: #475569;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .btn-danger {
            background: #fff;
            border-color: #fecaca;

            color: var(--danger);
        }

        .btn-danger:hover {
            background: #fef2f2;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-wrapper {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;

            border-collapse: collapse;

            font-size: 13px;
        }

        .admin-table th {
            padding: 12px;

            background: #f8fafc;

            border-bottom:
                1px solid var(--border);

            color: #64748b;

            text-align: left;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.35px;
        }

        .admin-table td {
            padding: 13px 12px;

            border-bottom:
                1px solid #f1f5f9;

            color: #334155;
        }

        .admin-table tbody tr:hover {
            background: #f8fafc;
        }

        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        .badge {
            display: inline-flex;
            align-items: center;

            padding: 4px 8px;

            border-radius: 999px;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 11px;
            font-weight: 700;
        }

        .badge-success {
            background: #ecfdf5;
            color: #047857;
        }

        .badge-warning {
            background: #fffbeb;
            color: #b45309;
        }

        .badge-danger {
            background: #fef2f2;
            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {
            :root {
                --sidebar-width: 230px;
            }

            .admin-content {
                padding: 18px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sidebar =
            document.querySelector('.admin-sidebar')
            || document.querySelector('aside');

        if (!sidebar) {
            return;
        }

        const savedScrollPosition =
            localStorage.getItem('sidebar_scroll_pos');

        if (savedScrollPosition) {
            sidebar.scrollTop = savedScrollPosition;
        }

        sidebar.addEventListener('scroll', function () {
            localStorage.setItem(
                'sidebar_scroll_pos',
                sidebar.scrollTop
            );
        });
    });
</script>

<div class="admin-wrapper">

    @include(
        'admin.layouts.partials.sidebar'
    )

    <div class="admin-main">

        @include(
            'admin.layouts.partials.header'
        )

        <main class="admin-content">

            @yield('breadcrumb')

            @include(
                'admin.layouts.partials.flash'
            )

            @yield('content')

        </main>

    </div>

</div>

@stack('scripts')

</body>

</html>
