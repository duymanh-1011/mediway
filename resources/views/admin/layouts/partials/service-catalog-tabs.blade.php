<nav class="admin-tabs" aria-label="Danh mục dịch vụ">
    <a class="{{ request()->routeIs('admin.service-groups.*') ? 'active' : '' }}" href="{{ route('admin.service-groups.index') }}">Nhóm dịch vụ</a>
    <a class="{{ request()->routeIs('admin.medical-services.*') ? 'active' : '' }}" href="{{ route('admin.medical-services.index') }}">Dịch vụ y tế</a>
    <a class="{{ request()->routeIs('admin.service-result-templates.*') ? 'active' : '' }}" href="{{ route('admin.service-result-templates.index') }}">Mẫu phiếu kết quả</a>
</nav>
