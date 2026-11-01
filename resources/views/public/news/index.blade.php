@extends('portal.layouts.app')
@section('title', 'Tin tức và hướng dẫn - Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell"><p class="portal-eyebrow">TIN TỨC & HƯỚNG DẪN</p><h1>Thông tin hữu ích cho hành trình khám</h1><p class="portal-lead">Tìm hiểu cách chuẩn bị trước khi khám và sử dụng các tiện ích trong tài khoản Mediway.</p></div></section>
<section class="public-guide"><div class="portal-shell"><div class="public-guide__intro"><p class="portal-eyebrow">DÀNH CHO BẠN</p><h2>Hướng dẫn và thông tin</h2><p>Những bước cơ bản giúp bạn chủ động hơn khi sử dụng cổng bệnh nhân.</p></div><div class="public-guide__grid public-guide__grid--3">
@foreach ($news as $article)<article class="public-guide__card"><x-portal.badge type="info">{{ $article['category'] }}</x-portal.badge><h3><a class="portal-text-link" href="{{ route('public.news.show', $article['slug']) }}">{{ $article['title'] }}</a></h3><p>{{ $article['excerpt'] }}</p><a class="portal-text-link" href="{{ route('public.news.show', $article['slug']) }}">Xem hướng dẫn →</a></article>@endforeach
</div></div></section>
@endsection
