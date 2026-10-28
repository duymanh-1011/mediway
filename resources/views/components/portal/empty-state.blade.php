@props(['title' => 'Chưa có dữ liệu', 'description' => null])
<section {{ $attributes->merge(['class' => 'portal-empty']) }}>
    <span class="portal-empty__icon" aria-hidden="true">○</span>
    <h2>{{ $title }}</h2>
    @if ($description)<p>{{ $description }}</p>@endif
    {{ $slot }}
</section>
