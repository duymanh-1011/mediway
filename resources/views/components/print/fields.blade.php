@props(['values', 'wide' => [], 'divider' => null, 'fallback' => '—'])

<dl {{ $attributes->class(['print-facts']) }}>
    @foreach ($values as $label => $value)
        <div @class(['print-fact-wide' => in_array($label, $wide, true), 'print-fact-divider' => $label === $divider])>
            <dt><span>{{ $label }}</span><span aria-hidden="true">:</span></dt>
            <dd>{{ filled($value) ? $value : $fallback }}</dd>
        </div>
    @endforeach
</dl>
