<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng nhập quản trị - Mediway</title>
</head>

<body>

    <h1>Mediway Admin</h1>

    <h2>Đăng nhập quản trị viên</h2>

    @if ($errors->any())
        <div>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <form
        method="POST"
        action="{{ route('admin.login.submit') }}"
    >
        @csrf

        <div>
            <label for="phone">
                Số điện thoại
            </label>

            <input
                type="text"
                id="phone"
                name="phone"
                value="{{ old('phone') }}"
                required
                autofocus
            >
        </div>

        <div>
            <label for="password">
                Mật khẩu
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <button type="submit">
            Đăng nhập
        </button>
    </form>

</body>
</html>