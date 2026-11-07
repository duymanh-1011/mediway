@extends('portal.layouts.app')
@section('title', 'Thông báo - Mediway')
@section('content')
<section class="notification-page">
    <div class="portal-shell">
        <a class="notification-page__back portal-back" href="{{ route('patient.home') }}">← Quay lại tài khoản</a>
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
            <div class="notification-page__content" aria-label="Danh sách thông báo">
                @forelse ($items as $item)
                    <form method="POST" action="{{ route('patient.notifications.open') }}">
                        @csrf
                        <input type="hidden" name="notification_key" value="{{ $item['key'] }}">
                    <button class="notification-item" type="submit">
                        <span class="notification-item__body"><span class="notification-item__category">{{ $item['category'] }}</span><strong>{{ $item['title'] }}</strong><span>{{ $item['description'] }}</span></span>
                        @if ($item['time'])<time datetime="{{ $item['time']->toIso8601String() }}">{{ $item['time']->format('H:i d/m/Y') }}</time>@endif
                    </button>
                    </form>
                @empty
                    <x-portal.empty-state title="Chưa có thông báo" />
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
