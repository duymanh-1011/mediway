@extends('portal.auth.layout')
@section('title', 'Xác minh OTP - Mediway')
@section('auth-content')
<p class="portal-eyebrow">XÁC MINH OTP</p><h1>Nhập mã xác minh</h1><p>Mã có hiệu lực 10 phút và bị khóa sau 5 lần nhập sai.</p><form method="post" action="{{ route('patient.password.verify.store') }}" class="portal-form" novalidate>@csrf<label for="otp">Mã OTP</label><input id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required>@error('otp')<p class="portal-field-error">{{ $message }}</p>@enderror<x-portal.button type="submit" class="portal-btn--block">Xác minh mã</x-portal.button></form>
@endsection
