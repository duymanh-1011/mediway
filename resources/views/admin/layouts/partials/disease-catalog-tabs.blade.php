<nav class="admin-tabs" aria-label="Danh mục bệnh">
    <a class="{{ request()->routeIs('admin.disease-groups.*') ? 'active' : '' }}" href="{{ route('admin.disease-groups.index') }}" @if(request()->routeIs('admin.disease-groups.*')) aria-current="page" @endif>Nhóm bệnh</a>
    <a class="{{ request()->routeIs('admin.diseases.*') ? 'active' : '' }}" href="{{ route('admin.diseases.index') }}" @if(request()->routeIs('admin.diseases.*')) aria-current="page" @endif>Danh mục bệnh</a>
</nav>
