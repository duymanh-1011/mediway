<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mediway - chăm sóc sức khỏe thuận tiện, an toàn và gần gũi.">
    <title>@yield('title', 'Mediway')</title>
    @vite(['resources/css/portal.css', 'resources/js/portal.js'])
    @stack('portal_page_styles')
</head>

<body class="portal-body">
    @include('portal.layouts.header')
    <main id="main-content" class="portal-main">@include('portal.layouts.flash')@yield('content')</main>
    @include('portal.layouts.footer')
</body>

</html>
