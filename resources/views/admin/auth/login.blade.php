<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng nhập quản trị - Mediway</title>

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;

            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            --danger-bg: #fef2f2;
            --success-bg: #ecfdf5;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;

            font-family:
                "Times New Roman",
                Times,
                serif;

            background:
                radial-gradient(
                    circle at top left,
                    rgba(59, 130, 246, 0.12),
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    #eff6ff 0%,
                    #f8fafc 45%,
                    #eef2ff 100%
                );

            color: var(--text);
        }

        button,
        input {
            font-family:
                "Times New Roman",
                Times,
                serif;
        }

        .login-page {
            min-height: 100vh;

            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
        }

        /*
        |--------------------------------------------------------------------------
        | LEFT SIDE
        |--------------------------------------------------------------------------
        */

        .login-intro {
            position: relative;

            display: flex;
            flex-direction: column;
            justify-content: center;

            padding: 64px;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    #0f172a 0%,
                    #172554 55%,
                    #1e3a8a 100%
                );

            color: #ffffff;
        }

        .login-intro::before {
            content: "";

            position: absolute;

            width: 420px;
            height: 420px;

            top: -120px;
            right: -120px;

            border-radius: 50%;

            background:
                rgba(59, 130, 246, 0.18);
        }

        .login-intro::after {
            content: "";

            position: absolute;

            width: 300px;
            height: 300px;

            bottom: -110px;
            left: -90px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, 0.06);
        }

        .intro-content {
            position: relative;
            z-index: 2;

            max-width: 560px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;

            padding: 8px 12px;

            border:
                1px solid rgba(255, 255, 255, 0.15);

            border-radius: 999px;

            background:
                rgba(255, 255, 255, 0.08);

            color: #dbeafe;

            font-size: 14px;
            font-weight: 700;

            margin-bottom: 24px;
        }

        .brand-dot {
            width: 9px;
            height: 9px;

            border-radius: 50%;

            background: #60a5fa;

            box-shadow:
                0 0 0 6px rgba(96, 165, 250, 0.12);
        }

        .intro-title {
            margin: 0;

            font-size: 52px;
            line-height: 1.08;

            font-weight: 700;

            letter-spacing: -0.5px;
        }

        .intro-title span {
            color: #60a5fa;
        }

        .intro-description {
            margin-top: 22px;

            max-width: 520px;

            color: #cbd5e1;

            font-size: 17px;
            line-height: 1.7;
        }

        .intro-features {
            margin-top: 34px;

            display: grid;
            gap: 15px;
        }

        .intro-feature {
            display: flex;
            align-items: center;
            gap: 12px;

            color: #e2e8f0;

            font-size: 15px;
        }

        .feature-icon {
            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 9px;

            background:
                rgba(255, 255, 255, 0.09);

            font-size: 16px;
        }

        /*
        |--------------------------------------------------------------------------
        | RIGHT SIDE
        |--------------------------------------------------------------------------
        */

        .login-form-area {
            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;

            background:
                rgba(255, 255, 255, 0.96);

            border:
                1px solid rgba(226, 232, 240, 0.95);

            border-radius: 20px;

            padding: 38px;

            box-shadow:
                0 24px 70px rgba(15, 23, 42, 0.12);
        }

        .mobile-brand {
            display: none;
        }

        .login-heading {
            margin: 0;

            font-size: 31px;
            font-weight: 700;

            letter-spacing: -0.2px;
        }

        .login-subheading {
            margin:
                9px
                0
                27px;

            color: var(--muted);

            font-size: 15px;
            line-height: 1.55;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .alert {
            margin-bottom: 18px;

            padding: 12px 14px;

            border-radius: 10px;

            font-size: 14px;
            line-height: 1.5;
        }

        .alert-error {
            background: var(--danger-bg);

            border:
                1px solid #fecaca;

            color: #991b1b;
        }

        .alert-success {
            background: var(--success-bg);

            border:
                1px solid #a7f3d0;

            color: #047857;
        }

        .alert ul {
            margin: 0;
            padding-left: 18px;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-group {
            margin-bottom: 19px;
        }

        .form-label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 15px;
            font-weight: 700;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;

            padding:
                12px
                14px;

            border:
                1px solid var(--border);

            border-radius: 10px;

            background: #ffffff;

            color: var(--text);

            font-size: 15px;

            outline: none;

            transition:
                border-color 0.18s ease,
                box-shadow 0.18s ease;
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .form-input:focus {
            border-color: #60a5fa;

            box-shadow:
                0 0 0 4px
                rgba(59, 130, 246, 0.1);
        }

        .password-input {
            padding-right: 48px;
        }

        .toggle-password {
            position: absolute;

            top: 50%;
            right: 10px;

            transform: translateY(-50%);

            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: none;

            border-radius: 8px;

            background: transparent;

            color: #64748b;

            cursor: pointer;
        }

        .toggle-password:hover {
            background: #f1f5f9;
        }

        .login-button {
            width: 100%;

            margin-top: 4px;

            padding:
                13px
                16px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--primary-dark)
                );

            color: #ffffff;

            font-family:
                "Times New Roman",
                Times,
                serif;

            font-weight: 700;
            font-size: 16px;

            cursor: pointer;

            box-shadow:
                0 10px 24px
                rgba(37, 99, 235, 0.2);

            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }

        .login-button:hover {
            transform: translateY(-1px);

            box-shadow:
                0 14px 30px
                rgba(37, 99, 235, 0.28);
        }

        .login-footer {
            margin-top: 24px;

            padding-top: 20px;

            border-top:
                1px solid #f1f5f9;

            text-align: center;

            color: #94a3b8;

            font-size: 13px;
            line-height: 1.55;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 960px) {

            .login-page {
                grid-template-columns: 1fr;
            }

            .login-intro {
                display: none;
            }

            .login-form-area {
                min-height: 100vh;

                padding: 24px;
            }

            .mobile-brand {
                display: block;

                margin-bottom: 22px;

                color: var(--primary);

                font-size: 26px;
                font-weight: 700;
            }
        }

        @media (max-width: 520px) {

            .login-form-area {
                padding: 16px;
            }

            .login-card {
                padding: 28px 22px;

                border-radius: 16px;
            }

            .login-heading {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <section class="login-intro">

        <div class="intro-content">

            <div class="brand-badge">

                <span class="brand-dot"></span>

                Mediway Administration

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
                                {{ $error }}
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

            <form
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
                Mediway Hospital Management System
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