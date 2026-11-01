@extends('portal.layouts.app')
@section('title', $article['title'].' - Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell public-article__content"><a class="appointment-back" href="{{ route('public.news') }}">← Tin tức và hướng dẫn</a><p class="portal-eyebrow">{{ $article['category'] }}</p><h1>{{ $article['title'] }}</h1><p class="portal-lead">{{ $article['excerpt'] }}</p></div></section>
<article class="public-guide"><div class="portal-shell public-article__content">
    @foreach ($article['body'] as $section)<section class="public-article__section"><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p></section>@endforeach
    <div class="public-guide__actions"><x-portal.button :href="route('public.care-process')">Xem quy trình khám</x-portal.button><x-portal.button variant="secondary" :href="route('public.support')">Cần hỗ trợ?</x-portal.button></div>
</div></article>
@endsection
