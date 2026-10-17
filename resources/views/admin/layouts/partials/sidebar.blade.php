<aside class="admin-sidebar">

    <div class="sidebar-brand">

        <h1 class="sidebar-brand-title">
            Mediway
        </h1>

        <div class="sidebar-brand-subtitle">
            Hệ thống quản lý bệnh viện
        </div>

    </div>


    <nav class="sidebar-nav">

        {{-- Tổng quan --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Tổng quan
            </div>

            <a
                href="{{ route('admin.dashboard') }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.dashboard'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Bảng điều khiển
            </a>

        </div>


        {{-- Hệ thống --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Hệ thống
            </div>

            <a
                href="{{ route('admin.users.index') }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.users.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Tài khoản
            </a>

            <a
                href="{{ route('admin.roles.index') }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.roles.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Vai trò
            </a>

            <a
                href="{{ route(
                    'admin.administrators.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.administrators.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Quản trị viên
            </a>

            <a
                href="{{ route(
                    'admin.audit-logs.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.audit-logs.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Nhật ký hoạt động
            </a>

        </div>


        {{-- Nhân sự & bệnh nhân --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Nhân sự & bệnh nhân
            </div>

            <details
                class="sidebar-submenu"
                {{ request()->routeIs(
                    'admin.patients.*',
                    'admin.personal-health-records.*'
                ) ? 'open' : '' }}
            >

                <summary
                    class="
                        sidebar-link
                        sidebar-submenu-toggle
                        {{ request()->routeIs(
                            'admin.patients.*',
                            'admin.personal-health-records.*'
                        ) ? 'active' : '' }}
                    "
                >

                    <span class="sidebar-link-dot"></span>

                    <span class="sidebar-submenu-label">
                        Bệnh nhân
                    </span>

                    <span class="sidebar-submenu-chevron">
                        ⌄
                    </span>

                </summary>


                <div class="sidebar-submenu-items">

                    <a
                        href="{{ route(
                            'admin.patients.index'
                        ) }}"
                        class="
                            sidebar-link
                            sidebar-submenu-item
                            {{ request()->routeIs(
                                'admin.patients.*'
                            ) ? 'active' : '' }}
                        "
                    >
                        <span class="sidebar-link-dot"></span>

                        Danh sách bệnh nhân
                    </a>

                    <a
                        href="{{ route(
                            'admin.personal-health-records.index'
                        ) }}"
                        class="
                            sidebar-link
                            sidebar-submenu-item
                            {{ request()->routeIs(
                                'admin.personal-health-records.*'
                            ) ? 'active' : '' }}
                        "
                    >
                        <span class="sidebar-link-dot"></span>

                        Hồ sơ sức khỏe
                    </a>

                </div>

            </details>


            <a
                href="{{ route('admin.doctors.index') }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.doctors.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Bác sĩ
            </a>

            <a
                href="{{ route('admin.employees.index') }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.employees.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Nhân viên
            </a>

        </div>


        {{-- Danh mục --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Danh mục
            </div>

            <a
                href="{{ route(
                    'admin.specialties.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.specialties.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Chuyên khoa
            </a>

            <a
                href="{{ route(
                    'admin.examination-rooms.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.examination-rooms.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Phòng khám
            </a>

            <a
                href="{{ route(
                    'admin.service-groups.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.service-groups.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Nhóm dịch vụ
            </a>

            <a
                href="{{ route(
                    'admin.medical-services.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.medical-services.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Dịch vụ y tế
            </a>

            <a
                href="{{ route(
                    'admin.disease-groups.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.disease-groups.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Nhóm bệnh
            </a>

            <a
                href="{{ route(
                    'admin.diseases.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.diseases.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Danh mục bệnh
            </a>

            <a
                href="{{ route(
                    'admin.medicines.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.medicines.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Thuốc
            </a>

        </div>


        {{-- Lịch và đặt khám --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Lịch và đặt khám
            </div>

            <a
                href="{{ route(
                    'admin.doctor-schedules.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.doctor-schedules.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Lịch làm việc bác sĩ
            </a>

            <a
                href="{{ route(
                    'admin.appointments.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.appointments.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Lịch hẹn
            </a>

        </div>


        {{-- Vận hành khám --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Vận hành khám
            </div>

            <a
                href="{{ route(
                    'admin.visits.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.visits.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Lượt khám
            </a>

            <a
                href="{{ route(
                    'admin.queues.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.queues.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Hàng đợi phòng khám
            </a>

            <a
                href="{{ route(
                    'admin.clinical-notes.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.clinical-notes.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Nội dung khám
            </a>

            <a
                href="{{ route(
                    'admin.diagnoses.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.diagnoses.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Chẩn đoán
            </a>

            <a
                href="{{ route(
                    'admin.conclusions.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.conclusions.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Kết luận
            </a>

        </div>


        {{-- Dịch vụ --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Dịch vụ
            </div>

            <a
                href="{{ route(
                    'admin.service-orders.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.service-orders.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Chỉ định dịch vụ
            </a>

            <a
                href="{{ route(
                    'admin.service-results.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.service-results.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Kết quả dịch vụ
            </a>

        </div>


        {{-- Đơn thuốc --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Đơn thuốc
            </div>

            <a
                href="{{ route(
                    'admin.prescriptions.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.prescriptions.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Quản lý đơn thuốc
            </a>

        </div>


        {{-- Thanh toán --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Thanh toán
            </div>

            <a
                href="{{ route(
                    'admin.payables.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.payables.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Khoản phải thanh toán
            </a>

            <a
                href="{{ route(
                    'admin.payment-transactions.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.payment-transactions.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Giao dịch thanh toán
            </a>

        </div>


        {{-- Cấu hình trạng thái --}}
        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Cấu hình trạng thái
            </div>

            <a
                href="{{ route(
                    'admin.appointment-statuses.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.appointment-statuses.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái lịch hẹn
            </a>

            <a
                href="{{ route(
                    'admin.visit-statuses.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.visit-statuses.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái lượt khám
            </a>

            <a
                href="{{ route(
                    'admin.queue-statuses.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.queue-statuses.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái hàng đợi
            </a>

            <a
                href="{{ route(
                    'admin.service-statuses.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.service-statuses.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái dịch vụ
            </a>

            <a
                href="{{ route(
                    'admin.payment-statuses.index'
                ) }}"
                class="sidebar-link
                    {{ request()->routeIs(
                        'admin.payment-statuses.*'
                    ) ? 'active' : '' }}"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái thanh toán
            </a>

        </div>

    </nav>

</aside>