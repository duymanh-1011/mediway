<section class="admin-card admin-filter-panel" aria-label="{{ $title }}">
    <header class="admin-filter-panel-header">
        <h2>{{ $title }}</h2>
        <p>{{ $description }}</p>
    </header>

    {{ $slot }}
</section>
