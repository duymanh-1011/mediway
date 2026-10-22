<form method="POST" action="{{ route('admin.catalog.status', ['catalog' => $catalog, 'id' => $record->id]) }}" class="admin-catalog-status" data-confirm="{{ $record->status === 'ACTIVE' ? 'Ngừng hoạt động bản ghi này?' : 'Kích hoạt lại bản ghi này?' }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="status" value="{{ $record->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }}">
    <button type="submit" class="admin-btn {{ $record->status === 'ACTIVE' ? 'admin-btn-warning' : 'admin-btn-success' }}">{{ $record->status === 'ACTIVE' ? 'Ngừng hoạt động' : 'Kích hoạt lại' }}</button>
</form>
