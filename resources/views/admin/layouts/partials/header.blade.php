<header class="admin-header">

    <div class="admin-header-left">
        <button type="button" class="admin-sidebar-toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="true" aria-label="Đóng thanh điều hướng" title="Đóng thanh điều hướng">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="header-left-title">Hệ thống quản lý bệnh viện Mediway</div>
    </div>

    <div class="header-user">

        <div class="header-avatar">
            {{ strtoupper(
                mb_substr(
                    auth()->user()->administrator?->full_name
                        ?? auth()->user()->business_code,
                    0,
                    1
                )
            ) }}
        </div>

        <div class="header-user-info">

            <div class="header-user-name">
                {{ auth()->user()->administrator?->full_name
                    ?? auth()->user()->business_code }}
            </div>

            <div class="header-user-role">
                {{ \App\Models\AdminDisplay::label(auth()->user()->role?->name) }}
            </div>

        </div>

        <form
            method="POST"
            action="{{ route('admin.logout') }}"
        >
            @csrf

            <button
                type="submit"
                class="admin-btn admin-btn-secondary"
            >
                Đăng xuất
            </button>

        </form>

    </div>

</header>
