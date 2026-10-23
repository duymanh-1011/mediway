<section class="admin-card">
    @include('admin.layouts.partials.list-heading', ['listTitle' => $listTitle])
    <div class="admin-table-scroll">
        <table class="admin-table admin-status-table">
            <thead><tr>
                <th scope="col" class="admin-row-number">STT</th>
                <th scope="col">Tên trạng thái</th><th scope="col">Mô tả</th>
                <th scope="col">Thứ tự xử lý</th>
                <th scope="col">{{ $permissionField === 'allows_service_execution' ? 'Cho phép thực hiện dịch vụ' : 'Cho phép chuyển trạng thái' }}</th>
                <th scope="col">Trạng thái</th><th scope="col">Thao tác</th>
            </tr></thead>
            <tbody>
                @forelse ($statusRows as $statusRow)
                    <tr>
                        @include('admin.layouts.partials.row-number', ['rows' => $statusRows, 'index' => $loop->index])
                        <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $statusRow->name, 'badgeContext' => $statusContext])</td>
                        <td>{{ \App\Models\AdminDisplay::text($statusRow->description ?: '—') }}</td>
                        <td><span class="admin-process-order">{{ $statusRow->process_order }}</span></td>
                        <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $statusRow->{$permissionField} ? 'ALLOWED' : 'DENIED'])</td>
                        <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $statusRow->is_active ? 'ACTIVE' : 'INACTIVE'])</td>
                        <td><div class="admin-actions admin-table-actions">
                            <a class="admin-btn admin-btn-detail" href="{{ route($statusRoute.'.show', $statusRow->id) }}">Chi tiết</a>
                            <a class="admin-btn admin-btn-secondary" href="{{ route($statusRoute.'.edit', $statusRow->id) }}">Sửa</a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">Không tìm thấy trạng thái phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $statusRows->links() }}
</section>
