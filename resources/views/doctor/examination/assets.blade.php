{{-- D01 asset sources are bundled into document-local URLs until Vite has dedicated entries. --}}
@push('styles')
    <link rel="stylesheet" href="data:text/css;charset=utf-8;base64,{{ base64_encode(file_get_contents(resource_path('css/doctor-examination.css'))) }}">
@endpush

@push('scripts')
    <script defer src="data:text/javascript;charset=utf-8;base64,{{ base64_encode(file_get_contents(resource_path('js/doctor-examination.js'))) }}"></script>
@endpush
