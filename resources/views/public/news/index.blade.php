@extends('portal.layouts.app')
@section('title', 'Tin tức sức khỏe - Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell"><p class="portal-eyebrow">TIN TỨC & KIẾN THỨC</p><h1>Góc sức khỏe Mediway</h1><p class="portal-lead">Những nội dung mẫu về chăm sóc sức khỏe dành cho bạn và gia đình.</p></div></section>
<section class="portal-section"><div class="portal-shell portal-grid portal-grid--3">@foreach ($news as $article)<article class="portal-card portal-news-card"><x-portal.badge type="info">{{ $article['category'] }}</x-portal.badge><p class="portal-date">{{ $article['published_at'] }} · Nội dung mẫu</p><h2><a href="{{ route('public.news.show', $article['slug']) }}">{{ $article['title'] }}</a></h2><p>{{ $article['excerpt'] }}</p><a class="portal-text-link" href="{{ route('public.news.show', $article['slug']) }}">Đọc bài viết →</a></article>@endforeach</div></section>
@endsection
