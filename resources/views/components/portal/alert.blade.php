@props(['type' => 'info'])
<div {{ $attributes->merge(['class' => 'portal-alert portal-alert--'.$type, 'role' => $type === 'error' ? 'alert' : 'status']) }}>{{ $slot }}</div>
