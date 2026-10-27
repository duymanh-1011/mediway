@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@if ($href)
    <a {{ $attributes->merge(['class' => 'portal-btn portal-btn--'.$variant, 'href' => $href]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'portal-btn portal-btn--'.$variant]) }}>{{ $slot }}</button>
@endif
