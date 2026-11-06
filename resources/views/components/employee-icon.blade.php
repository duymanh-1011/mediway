@props(['name'])
<svg class="employee-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('home') <path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/> @break
        @case('reception') <circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0M19 8v4m-2-2h4"/> @break
        @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-10 4h5"/> @break
        @case('queue') <path d="M4 6h11M4 12h11M4 18h7m7-4 3 3-3 3"/> @break
        @case('lab') <path d="M9 3h6m-5 0v6l-5.4 9.2A2 2 0 0 0 6.3 21h11.4a2 2 0 0 0 1.7-2.8L14 9V3M8 15h8"/> @break
        @case('image') <rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m4 18 5-5 3 3 3-4 5 6"/> @break
        @case('payment') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/> @break
        @case('user') <circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/> @break
        @case('lock') <rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v3"/> @break
        @case('help') <circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4 2c-1 .7-1.5 1.2-1.5 2.5m0 3h.01"/> @break
        @case('arrow') <path d="M5 12h14m-5-5 5 5-5 5"/> @break
        @case('shield') <path d="M12 3 4 6v5c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/> @break
        @case('briefcase') <rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18m-10 0v2h2v-2"/> @break
        @case('contact') <rect x="3" y="4" width="18" height="16" rx="2"/><path d="m4 6 8 6 8-6"/> @break
        @case('edit') <path d="m4 16-1 5 5-1L19 9l-4-4L4 16Zm9-9 4 4m-2-6 2-2 4 4-2 2"/> @break
    @endswitch
</svg>
