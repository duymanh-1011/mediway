@extends('portal.layouts.app')
@section('title', 'Thông báo - Mediway')
@section('content')
<section class="notification-page">
    <div class="portal-shell">
        <a class="notification-page__back" href="{{ route('patient.home') }}">← Tài khoản bệnh nhân</a>
        <div class="notification-page__heading">
            <p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p>
            <h1>Thông báo</h1>
            <p>Theo dõi thông tin liên quan đến hành trình chăm sóc sức khỏe của bạn.</p>
        </div>
        <div class="notification-page__panel">
            <nav class="notification-page__filters" aria-label="Danh mục thông báo">
                @foreach ($categories as $key => $label)
                    <a href="{{ route('patient.notifications', ['danh-muc' => $key]) }}" @class(['notification-page__filter', 'is-active' => $category === $key]) @if ($category === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="notification-page__content" aria-label="Danh sách thông báo"></div>
        </div>
    </div>
</section>
@endsection
