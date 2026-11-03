@extends('portal.layouts.app')
@section('title', $article['title'].' - Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell public-article__content"><a class="appointment-back" href="{{ route('public.news') }}">← Quay lại tin tức</a><p class="portal-eyebrow">{{ $article['category'] }}</p><h1>{{ $article['title'] }}</h1><p class="portal-lead">{{ $article['excerpt'] }}</p></div></section>
<article class="public-guide"><div class="portal-shell public-article__content">
    <figure class="public-article__figure"><img src="{{ asset($article['detail_image'] ?? $article['image']) }}" alt="{{ $article['detail_image_alt'] ?? $article['image_alt'] }}"><figcaption>Hình minh họa cho hướng dẫn dành cho người bệnh.</figcaption></figure>
    @foreach ($article['body'] as $section)<section class="public-article__section"><h2>{{ $section['title'] }}</h2><p>{{ $section['text'] }}</p></section>@endforeach
    <div class="public-guide__note public-article__note">Nội dung mang tính hướng dẫn chung. Với các câu hỏi về sức khỏe hoặc chỉ định điều trị, hãy trao đổi trực tiếp với bác sĩ hoặc cơ sở khám.</div>
    <div class="public-guide__actions"><x-portal.button :href="route('public.care-process')">Xem quy trình khám</x-portal.button><x-portal.button variant="secondary" :href="route('public.support')">Cần hỗ trợ?</x-portal.button></div>
</div></article>
@endsection
