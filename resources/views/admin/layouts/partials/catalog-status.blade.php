<form method="POST" action="{{ route('admin.catalog.status', ['catalog' => $catalog, 'id' => $record->id]) }}" class="admin-catalog-status" data-confirm="{{ $record->status === 'ACTIVE' ? 'Ngừng hoạt động mục “'.$record->name.'”? Dữ liệu và lịch sử đã có vẫn được giữ nguyên.' : 'Kích hoạt lại mục “'.$record->name.'” để tiếp tục sử dụng?' }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" value="{{ $record->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }}">
    <button type="submit" class="admin-btn {{ $record->status === 'ACTIVE' ? 'admin-btn-warning' : 'admin-btn-success' }}">{{ $record->status === 'ACTIVE' ? 'Ngừng hoạt động' : 'Kích hoạt lại' }}</button>
</form>
