@props(['type' => 'neutral'])
<span {{ $attributes->merge(['class' => 'portal-badge portal-badge--'.$type]) }}>{{ $slot }}</span>
