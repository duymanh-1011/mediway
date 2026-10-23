<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'Quản trị Mediway')
    </title>

    <script data-sidebar-prepaint>
        (() => {
            let saved = null;
            try { saved = localStorage.getItem('mediway_sidebar_state'); } catch { /* Storage is optional. */ }
            document.documentElement.dataset.sidebarState =
                !window.matchMedia('(max-width: 1024px)').matches && saved !== 'closed' ? 'open' : 'closed';
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="admin-ui" data-admin-page="@yield('admin-page')" data-admin-module="{{ request()->segment(2) }}" data-lookup-url="{{ route('admin.lookups') }}" data-lookup-fields='@json(\App\Models\AdminLookup::FIELDS)'>

    <div class="admin-wrapper">

        @include(
            'admin.layouts.partials.sidebar'
        )

        <button type="button" class="admin-sidebar-backdrop" data-sidebar-close aria-label="Đóng thanh điều hướng" tabindex="-1" hidden></button>

        <div class="admin-main">

            @include(
                'admin.layouts.partials.header'
            )

            <main class="admin-content">

                <nav class="admin-breadcrumb" aria-label="Đường dẫn">@yield('breadcrumb')</nav>

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
