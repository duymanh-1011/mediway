@extends('portal.layouts.app')
@section('title', $article['title'].' - Mediway')
@section('content')
<article class="portal-article portal-shell"><a class="portal-text-link" href="{{ route('public.news') }}">← Quay lại tin tức</a><x-portal.badge type="info">{{ $article['category'] }}</x-portal.badge><p class="portal-date">{{ $article['published_at'] }} · Nội dung mẫu</p><h1>{{ $article['title'] }}</h1><p class="portal-lead">{{ $article['excerpt'] }}</p><div class="portal-article__body"><p>{{ $article['body'] }}</p></div></article>
@endsection
