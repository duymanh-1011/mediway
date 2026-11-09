@props([
    'title',
    'heading',
    'brandSubtitle',
    'backUrl',
    'backLabel',
    'printLabel' => 'In / Lưu PDF',
    'identifiers' => [],
    'facility' => [],
])
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} - Mediway</title>
    @vite(['resources/css/payment-receipt.css', 'resources/js/print-document.js'])
</head>
<body>
    <div {{ $attributes->class(['screen-wrap', 'print-document-wrap']) }}>
        <nav class="toolbar print-document-toolbar" aria-label="Thao tác tài liệu">
            <a href="{{ $backUrl }}"><span aria-hidden="true">←</span> {{ $backLabel }}</a>
            <div class="print-document-actions">
                {{ $actions ?? '' }}
                <button type="button" data-print-document>{{ $printLabel }}</button>
            </div>
        </nav>

        <main class="paper print-document-paper">
            <header class="document-head">
                <div class="brand-line">
                    <span class="brand-icon" aria-hidden="true">✦</span>
                    <div>
                        <p class="brand-name"><strong>Mediway</strong></p>
                        <p class="brand-sub">{{ $brandSubtitle }}</p>
                        @foreach ($facility as $line)
                            @if (filled($line))<p class="brand-sub">{{ $line }}</p>@endif
                        @endforeach
                    </div>
                </div>
                <dl class="document-meta">
                    @foreach ($identifiers as $label => $value)
                        <div><dt>{{ $label }}</dt><dd>{{ filled($value) ? $value : '—' }}</dd></div>
                    @endforeach
                </dl>
            </header>

            <div class="document-title">
                <h1>{{ $heading }}</h1>
                {{ $titleDetails ?? '' }}
            </div>

            {{ $slot }}
        </main>
    </div>
</body>
</html>
