@foreach (['pdf' => ['Tệp PDF kết quả', '.pdf', '5 MB'], 'image' => ['Ảnh kết quả', '.jpg,.jpeg,.png,.webp', '2 MB']] as $type => [$label, $accept, $maximum])
    <div class="admin-form-group">
        <label for="result_{{ $type }}">{{ $label }}</label>
        <input id="result_{{ $type }}" name="result_{{ $type }}" type="file" accept="{{ $accept }}" class="admin-input">
        <p class="form-help">Tối đa {{ $maximum }}. Tệp được lưu riêng tư; không chọn tệp mới để giữ tệp hiện tại.</p>
        @if (($result ?? null)?->hasDocument($type))
            <a class="admin-btn admin-btn-detail" href="{{ route('admin.service-results.document', [$result->id, $type]) }}" target="_blank" rel="noopener noreferrer">Xem tệp hiện tại</a>
        @elseif (($result ?? null)?->getAttribute('result_'.$type.'_path'))
            <p class="form-help">Tệp trước đây không khả dụng. Vui lòng tải lại tệp thực tế nếu cần.</p>
        @endif
        @error('result_'.$type)<div class="field-error">{{ $message }}</div>@enderror
    </div>
@endforeach
