@props(['label', 'name' => null, 'date' => null, 'fallback' => 'Chưa ghi nhận'])

<section {{ $attributes->class(['print-document-signature']) }} aria-label="Khu vực chữ ký">
    @if ($date)
        <p class="print-sign-date">Ngày {{ $date->format('d') }} tháng {{ $date->format('m') }} năm {{ $date->format('Y') }}</p>
    @endif
    <h2>{{ $label }}</h2>
    <p class="print-sign-note">(Ký, ghi rõ họ tên)</p>
    <div class="print-sign-space" aria-hidden="true"></div>
    <p class="print-sign-name">{{ $name ?: $fallback }}</p>
</section>
