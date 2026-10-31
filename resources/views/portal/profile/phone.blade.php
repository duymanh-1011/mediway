@extends('portal.layouts.app')
@section('title', 'Đổi số điện thoại - Mediway')
@section('content')
<link rel="stylesheet" href="{{ asset('css/patient-profile.css') }}">
<section class="profile-page"><div class="portal-shell profile-edit-shell">
    <a class="profile-back" href="{{ route('patient.profile.show') }}">← Quay lại hồ sơ cá nhân</a>
    <header class="profile-heading"><p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p><h1>Đổi số điện thoại</h1><p>Bảo vệ thông tin đăng nhập của bạn.</p></header>
    <section class="profile-card"><div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">✦</span><div><h2>Số điện thoại đăng nhập</h2><p>Số đang dùng để đăng nhập tài khoản</p></div></div>
        <dl class="profile-details"><div><dt>Số điện thoại hiện tại</dt><dd>{{ $user->phone }}</dd></div></dl>
        <p class="profile-card__note">Chức năng đổi số điện thoại qua mã SMS đang được triển khai và chưa khả dụng. Vui lòng <a href="{{ route('public.support') }}">liên hệ hỗ trợ</a> khi cần thay đổi.</p>
    </section>
</div></section>
@endsection
