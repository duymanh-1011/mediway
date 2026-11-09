@extends('portal.auth.layout')
@section('title', 'Đổi mật khẩu lần đầu - Mediway')
@section('auth-content')
    <p class="portal-eyebrow">BẢO MẬT TÀI KHOẢN</p>
    <h1>Đổi mật khẩu lần đầu</h1>
    <p>Đặt mật khẩu riêng có ít nhất 8 ký tự để mở hồ sơ và sử dụng các dịch vụ Mediway.</p>
    <form method="post" action="{{ route('patient.password.initial.update') }}" class="portal-form">
        @csrf
        @method('PUT')
        <label for="password">Mật khẩu mới</label>
        <div class="portal-password">
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
            <button type="button" data-password-toggle aria-controls="password">Hiện</button>
        </div>
        @error('password')<p class="portal-field-error">{{ $message }}</p>@enderror
        <label for="password_confirmation">Xác nhận mật khẩu mới</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
        <x-portal.button type="submit" class="portal-btn--block">Lưu mật khẩu mới</x-portal.button>
    </form>
@endsection
