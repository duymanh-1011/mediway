<section class="employee-welcome">
    <div class="employee-welcome__copy">
        <p class="portal-eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $description }}</p>
        @isset($stageLabel)
            <span class="tech-status tech-heading-status" data-stage="{{ $stage }}">{{ $stageLabel }}</span>
        @endisset
    </div>
    <div class="employee-welcome__art" aria-hidden="true">
        <div class="employee-welcome__ring"></div>
        <span><x-employee-icon name="services" /></span><i></i>
    </div>
</section>
