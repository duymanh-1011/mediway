<input type="text" id="{{ $field }}" name="{{ $field }}" class="admin-input admin-record-code" value="{{ $displayCode }}" readonly aria-readonly="true" aria-describedby="{{ $field }}-help">
<small class="form-help" id="{{ $field }}-help">{{ isset($record) ? 'Mã đã cấp được giữ nguyên.' : 'Mã dự kiến; hệ thống tự cấp mã chính thức khi lưu.' }}</small>
