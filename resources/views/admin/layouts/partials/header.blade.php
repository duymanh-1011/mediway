<header class="admin-header">

    <div class="header-left-title">
        Hệ thống quản trị Mediway
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
                {{ auth()->user()->role->name }}
            </div>

        </div>

        <form
            method="POST"
            action="{{ route('admin.logout') }}"
        >
            @csrf

            <button
                type="submit"
                class="logout-button"
            >
                Đăng xuất
            </button>

        </form>

    </div>

</header>