@props(['name', 'position', 'code', 'codeLabel', 'profileTitle', 'profileUrl', 'facts', 'work', 'welcomeIcon' => null])

<section class="workspace-greeting" aria-labelledby="workspace-greeting-name">
    <span class="workspace-greeting__icon">
        <x-employee-icon :name="$welcomeIcon ?? $work['icon']" />
    </span>
    <div class="workspace-greeting__body">
        <p class="workspace-greeting__salutation">Xin chào,</p>
        <h1 id="workspace-greeting-name">{{ $name }}</h1>
        <div class="workspace-greeting__meta">
            <span class="workspace-greeting__position">{{ $position }}</span>
            <span class="workspace-greeting__code">{{ $codeLabel }} <strong>{{ $code }}</strong></span>
        </div>
    </div>
</section>

<section class="employee-section" aria-labelledby="workspace-overview-title">
    <div class="employee-section__heading"><h2 id="workspace-overview-title">Tổng quan của bạn</h2></div>
    <div class="employee-grid">
        <a class="employee-card employee-overview-card workspace-profile-card" href="{{ $profileUrl }}">
            <div class="employee-card__heading">
                <span class="employee-card__icon"><x-employee-icon name="user" /></span>
                <h3>{{ $profileTitle }}</h3>
            </div>
            <dl class="employee-facts">
                @foreach ($facts as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
            </dl>
        </a>
        <a class="employee-card employee-overview-card workspace-work-card" href="{{ $work['url'] }}">
            <div class="employee-card__heading">
                <span class="employee-card__icon"><x-employee-icon :name="isset($work['heading']) ? $work['icon'] : 'briefcase'" /></span>
                <h3>{{ $work['heading'] ?? 'Công việc của bạn' }}</h3>
            </div>
            <div class="workspace-work-card__content">
                @isset($work['title'])
                    <h4>{{ $work['title'] }}</h4>
                @endisset
                <p>{{ $work['description'] }}</p>
            </div>
        </a>
    </div>
</section>
