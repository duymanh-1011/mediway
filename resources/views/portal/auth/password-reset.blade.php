@extends('portal.auth.layout')
@section('title', 'Đặt lại mật khẩu - Mediway')
@section('auth-content')
<p class="portal-eyebrow">MẬT KHẨU MỚI</p><h1>Đặt lại mật khẩu</h1><p>Quyền đặt lại này chỉ dùng một lần trong phiên hiện tại.</p><form method="post" action="{{ route('patient.password.reset.store') }}" class="portal-form" novalidate>@csrf<label for="password">Mật khẩu mới</label><div class="portal-password"><input id="password" name="password" type="password" autocomplete="new-password" required><button type="button" data-password-toggle aria-controls="password">Hiện</button></div>@error('password')<p class="portal-field-error">{{ $message }}</p>@enderror<label for="password_confirmation">Xác nhận mật khẩu mới</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><x-portal.button type="submit" class="portal-btn--block">Lưu mật khẩu mới</x-portal.button></form>
@endsection
