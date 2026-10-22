<!DOCTYPE html>
<html lang="vi">

<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng nhập quản trị - Mediway</title>


</head>

<body class="admin-ui" data-admin-page="auth-login" data-admin-module="auth">

<div class="login-page">

    <section class="login-intro">

        <div class="intro-content">

            <div class="brand-badge">

                <span class="brand-dot"></span>

                Quản trị Mediway

            </div>

            <h1 class="intro-title">
                Quản trị hệ thống
                <span>Mediway</span>
            </h1>

            <p class="intro-description">
                Trung tâm quản lý vận hành bệnh viện:
                tài khoản, bệnh nhân, bác sĩ, lịch khám,
                dịch vụ, hồ sơ khám bệnh, thanh toán
                và hoạt động toàn hệ thống.
            </p>

            <div class="intro-features">

                <div class="intro-feature">

                    <div class="feature-icon">
                        ✓
                    </div>

                    Quản lý tập trung toàn bộ dữ liệu vận hành

                </div>

                <div class="intro-feature">

                    <div class="feature-icon">
                        ✓
                    </div>

                    Theo dõi lịch khám và quá trình khám bệnh

                </div>

                <div class="intro-feature">

                    <div class="feature-icon">
                        ✓
                    </div>

                    Kiểm soát dịch vụ, thanh toán và nhật ký hệ thống

                </div>

            </div>

        </div>

    </section>

    <main class="login-form-area">

        <div class="login-card">

            <div class="mobile-brand">
                Mediway
            </div>

            <h2 class="login-heading">
                Đăng nhập quản trị
            </h2>

            <p class="login-subheading">
                Vui lòng sử dụng tài khoản quản trị viên
                để truy cập hệ thống Mediway.
            </p>

            @if ($errors->any())

                <div class="alert alert-error">

                    <ul>

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ \App\Models\AdminDisplay::text($error) }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif

            @if (session('success'))

                <div class="alert alert-success">
                    {{ session('success') }}
                </div>

            @endif

            <form novalidate
                method="POST"
                action="{{ route('admin.login.submit') }}"
            >

                @csrf

                <div class="form-group">

                    <label
                        for="phone"
                        class="form-label"
                    >
                        Số điện thoại
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        class="form-input"
                        value="{{ old('phone') }}"
                        placeholder="Nhập số điện thoại"
                        autocomplete="username"
                        required
                        autofocus
                    >
@error('phone')<div class="field-error">{{ $message }}</div>@enderror

                </div>

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Mật khẩu
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input password-input"
                            placeholder="Nhập mật khẩu"
                            autocomplete="current-password"
                            required
                        >
@error('password')<div class="field-error">{{ $message }}</div>@enderror

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Hiển thị mật khẩu"
                        >
                            👁
                        </button>

                    </div>

                </div>

                <button
                    type="submit"
                    class="login-button"
                >
                    Đăng nhập
                </button>

            </form>

            <div class="login-footer">
                Hệ thống quản lý bệnh viện Mediway
                <br>
                Khu vực dành riêng cho quản trị viên
            </div>

        </div>

    </main>

</div>

<script>
    const togglePassword =
        document.getElementById('togglePassword');

    const passwordInput =
        document.getElementById('password');

    togglePassword.addEventListener(
        'click',
        function () {

            const isPassword =
                passwordInput.type === 'password';

            passwordInput.type =
                isPassword
                    ? 'text'
                    : 'password';

            togglePassword.textContent =
                isPassword
                    ? '🙈'
                    : '👁';

            togglePassword.setAttribute(
                'aria-label',
                isPassword
                    ? 'Ẩn mật khẩu'
                    : 'Hiển thị mật khẩu'
            );
        }
    );
</script>

</body>

</html>
