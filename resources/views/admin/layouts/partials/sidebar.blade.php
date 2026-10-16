
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <h1 class="sidebar-brand-title">Mediway</h1>
        <div class="sidebar-brand-subtitle">Administration System</div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">
            <div class="sidebar-section-title">Tổng quan</div>

            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="sidebar-link-dot"></span>
                Dashboard
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Hệ thống</div>

            <a href="{{ route('admin.users.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <span class="sidebar-link-dot"></span>
                Tài khoản
            </a>

            <a href="{{ route('admin.roles.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <span class="sidebar-link-dot"></span>
                Vai trò
            </a>

            <a href="{{ route('admin.administrators.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.administrators.*') ? 'active' : '' }}">
                <span class="sidebar-link-dot"></span>
                Quản trị viên
            </a>

            <a href="#" class="sidebar-link">
                <span class="sidebar-link-dot"></span>
                Nhật ký hoạt động
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Nhân sự & bệnh nhân</div>

            <details class="sidebar-submenu"
                {{ request()->routeIs('admin.patients.*', 'admin.personal-health-records.*') ? 'open' : '' }}>

                <summary
                    class="sidebar-link sidebar-submenu-toggle {{ request()->routeIs('admin.patients.*', 'admin.personal-health-records.*') ? 'active' : '' }}">
                    <span class="sidebar-link-dot"></span>
                    <span class="sidebar-submenu-label">Bệnh nhân</span>
                    <span class="sidebar-submenu-chevron">⌄</span>
                </summary>

                <div class="sidebar-submenu-items">
                    <a href="{{ route('admin.patients.index') }}"
                       class="sidebar-link sidebar-submenu-item {{ request()->routeIs('admin.patients.*') ? 'active' : '' }}">
                        <span class="sidebar-link-dot"></span>
                        Danh sách bệnh nhân
                    </a>

                    <a href="{{ route('admin.personal-health-records.index') }}"
                       class="sidebar-link sidebar-submenu-item {{ request()->routeIs('admin.personal-health-records.*') ? 'active' : '' }}">
                        <span class="sidebar-link-dot"></span>
                        Lịch sử sức khỏe
                    </a>
                </div>
            </details>

            <a href="{{ route('admin.doctors.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.doctors.*') ? 'active' : '' }}">
                <span class="sidebar-link-dot"></span>
                Bác sĩ
            </a>

            <a href="{{ route('admin.employees.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                <span class="sidebar-link-dot"></span>
                Nhân viên
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Danh mục</div>

            @foreach([
                'Chuyên khoa', 'Phòng khám', 'Nhóm dịch vụ',
                'Dịch vụ y tế', 'Nhóm bệnh', 'Danh mục bệnh', 'Thuốc'
            ] as $item)
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-dot"></span>
                    {{ $item }}
                </a>
            @endforeach
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Lịch & đặt khám</div>

            @foreach(['Lịch làm việc bác sĩ', 'Lịch hẹn'] as $item)
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-dot"></span>
                    {{ $item }}
                </a>
            @endforeach
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Vận hành khám</div>

            @foreach([
                'Lượt khám', 'Hàng đợi phòng khám',
                'Nội dung khám', 'Chẩn đoán', 'Kết luận'
            ] as $item)
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-dot"></span>
                    {{ $item }}
                </a>
            @endforeach
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Dịch vụ</div>

            @foreach(['Chỉ định dịch vụ', 'Kết quả dịch vụ'] as $item)
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-dot"></span>
                    {{ $item }}
                </a>
            @endforeach
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Đơn thuốc</div>

            <a href="#" class="sidebar-link">
                <span class="sidebar-link-dot"></span>
                Đơn thuốc
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Thanh toán</div>

            @foreach(['Khoản phải thanh toán', 'Giao dịch thanh toán'] as $item)
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-dot"></span>
                    {{ $item }}
                </a>
            @endforeach
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Cấu hình trạng thái</div>

            @foreach([
                'Trạng thái lịch hẹn', 'Trạng thái lượt khám',
                'Trạng thái hàng đợi', 'Trạng thái dịch vụ',
                'Trạng thái thanh toán'
            ] as $item)
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-dot"></span>
                    {{ $item }}
                </a>
            @endforeach
        </div>
    </nav>
</aside>

<style>
.sidebar-submenu{width:100%;margin:0;padding:0}
.sidebar-submenu-toggle{display:flex;align-items:center;width:100%;cursor:pointer;list-style:none;user-select:none}
.sidebar-submenu-toggle::-webkit-details-marker{display:none}
.sidebar-submenu-toggle::marker{content:''}
.sidebar-submenu-label{flex:1}
.sidebar-submenu-chevron{font-size:20px;line-height:1;margin-left:auto;transition:transform .2s ease;opacity:.7}
.sidebar-submenu[open] .sidebar-submenu-chevron{transform:rotate(180deg)}
.sidebar-submenu-items{display:flex;flex-direction:column;gap:3px;margin:4px 0 8px 18px;padding-left:11px;border-left:1px solid rgba(148,163,184,.25)}
.sidebar-submenu-item{font-size:12px;padding-left:12px;min-height:35px}
.sidebar-submenu-item .sidebar-link-dot{width:5px;height:5px;flex-shrink:0}
</style>
