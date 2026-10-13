<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng nhập quản trị - Mediway</title>
    @vite('resources/css/app.css')
</head>

<body class="admin-login">
    <main class="login-card">
        <p class="login-brand">Mediway · Quản trị y tế</p>
        <h1 class="login-title">Đăng nhập</h1>
        <p class="login-description">Truy cập hệ thống quản trị vận hành khám chữa bệnh.</p>

        @if ($errors->any())
        <div class="login-alert" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        @if (session('success'))
        <p class="login-alert login-alert-success" role="status">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf

            <div class="login-field">
                <label for="phone">Số điện thoại</label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="{{ old('phone') }}"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="login-field">
                <label for="password">Mật khẩu</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-submit">Đăng nhập</button>
        </form>
    </main>
</body>
</html>