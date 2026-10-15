<aside class="admin-sidebar">

    <div class="sidebar-brand">

        <h1 class="sidebar-brand-title">
            Mediway
        </h1>

        <div class="sidebar-brand-subtitle">
            Administration System
        </div>

    </div>

    <nav class="sidebar-nav">

        {{-- =====================================================
            TỔNG QUAN
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Tổng quan
            </div>

            <a
                href="{{ route('admin.dashboard') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.dashboard')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Dashboard
            </a>

        </div>

        {{-- =====================================================
            HỆ THỐNG
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Hệ thống
            </div>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Tài khoản
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Vai trò
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Quản trị viên
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Nhật ký hoạt động
            </a>

        </div>

        {{-- =====================================================
            NHÂN SỰ & BỆNH NHÂN
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Nhân sự & bệnh nhân
            </div>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Bệnh nhân
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Bác sĩ
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Nhân viên
            </a>

        </div>

        {{-- =====================================================
            DANH MỤC
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Danh mục
            </div>

            <a
                href="{{ route('admin.specialties.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.specialties.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Chuyên khoa
            </a>

            <a
                href="{{ route('admin.examination-rooms.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.examination-rooms.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Phòng khám
            </a>

            <a
                href="{{ route('admin.service-groups.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.service-groups.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Nhóm dịch vụ
            </a>

            <a
                href="{{ route('admin.medical-services.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.medical-services.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Dịch vụ y tế
            </a>

            <a
                href="{{ route('admin.disease-groups.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.disease-groups.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Nhóm bệnh
            </a>

            <a
                href="{{ route('admin.diseases.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.diseases.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Danh mục bệnh
            </a>

            <a
                href="{{ route('admin.medicines.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.medicines.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Thuốc
            </a>

        </div>

        {{-- =====================================================
            LỊCH & ĐẶT KHÁM
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Lịch & đặt khám
            </div>

            <a
                href="{{ route('admin.doctor-schedules.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.doctor-schedules.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Lịch làm việc bác sĩ
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Lịch hẹn
            </a>

        </div>

        {{-- =====================================================
            VẬN HÀNH KHÁM
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Vận hành khám
            </div>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Lượt khám
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Hàng đợi phòng khám
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Nội dung khám
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Chẩn đoán
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Kết luận
            </a>

        </div>

        {{-- =====================================================
            DỊCH VỤ
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Dịch vụ
            </div>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Chỉ định dịch vụ
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Kết quả dịch vụ
            </a>

        </div>

        {{-- =====================================================
            ĐƠN THUỐC
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Đơn thuốc
            </div>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Đơn thuốc
            </a>

        </div>

        {{-- =====================================================
            THANH TOÁN
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Thanh toán
            </div>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Khoản phải thanh toán
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Giao dịch thanh toán
            </a>

        </div>

        {{-- =====================================================
            CẤU HÌNH TRẠNG THÁI
        ====================================================== --}}

        <div class="sidebar-section">

            <div class="sidebar-section-title">
                Cấu hình trạng thái
            </div>

            <a
                href="{{ route('admin.appointment-statuses.index') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('admin.appointment-statuses.*')
                        ? 'active'
                        : '' }}
                "
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái lịch hẹn
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái lượt khám
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái hàng đợi
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái dịch vụ
            </a>

            <a
                href="#"
                class="sidebar-link"
            >
                <span class="sidebar-link-dot"></span>

                Trạng thái thanh toán
            </a>

        </div>

    </nav>

</aside>