@extends('admin.layouts.app')
@section('title', ($template->exists ? 'Sửa' : 'Thêm').' mẫu phiếu kết quả - Mediway')
@section('admin-page', 'service-result-templates-form')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a><span aria-hidden="true">/</span><span>Dịch vụ</span><span aria-hidden="true">/</span><a href="{{ route('admin.service-result-templates.index') }}">Mẫu phiếu kết quả</a><span aria-hidden="true">/</span><strong aria-current="page">{{ $template->exists ? 'Cập nhật' : 'Thêm mới' }}</strong>
@endsection
@section('content')
    <div class="page-header"><div><h1 class="page-title">{{ $template->exists ? 'Sửa mẫu phiếu kết quả' : 'Thêm mẫu phiếu kết quả' }}</h1><p class="page-description">Gán dịch vụ và khoảng thời gian áp dụng cho mẫu. Kết quả đã lưu luôn giữ bản mẫu tại thời điểm lập.</p></div><div class="form-actions">@if ($template->exists)<a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.create', ['copy' => $template->id]) }}">Tạo phiên bản mới</a>@endif<a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.index') }}">← Quay lại</a></div></div>
    @if ($template->exists && $template->medicalServices()->exists())<div class="alert">Mẫu này đang được gán cho {{ $template->medicalServices()->count() }} dịch vụ.</div>@endif
    <form method="post" action="{{ $template->exists ? route('admin.service-result-templates.update', $template) : route('admin.service-result-templates.store') }}">
        @csrf @if ($template->exists) @method('PUT') @endif
        <div class="card">
            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="name">Tên mẫu <span class="required">*</span></label><input class="form-control" id="name" name="name" maxlength="150" required value="{{ old('name', $template->name) }}">@error('name')<div class="field-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label" for="is_active">Trạng thái <span class="required">*</span></label><select class="form-control" id="is_active" name="is_active" required><option value="1" @selected((string) old('is_active', (int) ($template->is_active ?? true)) === '1')>Đang hoạt động</option><option value="0" @selected((string) old('is_active', (int) ($template->is_active ?? true)) === '0')>Ngừng hoạt động</option></select></div>
            </div>
            <div class="form-group"><label class="form-label" for="description">Mô tả</label><textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $template->description) }}</textarea></div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="effective_from">Có hiệu lực từ <span class="required">*</span></label><input class="form-control" type="date" id="effective_from" name="effective_from" required value="{{ old('effective_from', $template->effective_from?->format('Y-m-d') ?? today()->toDateString()) }}">@error('effective_from')<div class="field-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label class="form-label" for="effective_until">Có hiệu lực đến</label><input class="form-control" type="date" id="effective_until" name="effective_until" value="{{ old('effective_until', $template->effective_until?->format('Y-m-d')) }}"><p class="form-help">Để trống nếu chưa có ngày kết thúc.</p>@error('effective_until')<div class="field-error">{{ $message }}</div>@enderror</div>
            </div>
            <p class="form-help">Thời gian hiệu lực tính theo ngày bắt đầu thực hiện dịch vụ. Khi tạo phiên bản mới cho cùng dịch vụ, hãy kết thúc thời gian của mẫu cũ trước để hai phiên bản không chồng lấn.</p>
        </div>
        <div class="card">
            <h2 class="card-title">Gán dịch vụ sử dụng mẫu này</h2>
            <p class="form-help">Chọn trực tiếp các dịch vụ. Tên mẫu chỉ để hiển thị, không dùng để tự ghép. Một dịch vụ không thể có hai mẫu cùng hiệu lực trong cùng thời gian.</p>
            <div class="form-group"><label class="form-label" for="service-template-search">Tìm dịch vụ</label><input class="form-control" type="search" id="service-template-search" placeholder="Tìm theo mã hoặc tên dịch vụ"></div>
            <div class="form-help">Đã chọn: <strong data-selected-service-count>0</strong> dịch vụ</div>
            <div class="service-template-options" data-service-options style="max-height: 360px; overflow-y: auto; border: 1px solid #d7e1f0; border-radius: 10px; padding: 8px;">
                @php($chosenServiceIds = old('medical_service_ids', $selectedServiceIds))
                @foreach ($services as $service)
                    <label data-service-option="{{ mb_strtolower($service->service_code.' '.$service->name) }}" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-bottom:1px solid #edf1f7; cursor:pointer;">
                        <input type="checkbox" name="medical_service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, array_map('intval', (array) $chosenServiceIds), true))>
                        <span><strong>{{ $service->name }}</strong><br><small>{{ $service->service_code }} · {{ $service->status === 'ACTIVE' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</small></span>
                    </label>
                @endforeach
                @if ($services->isEmpty())<p class="form-help">Chưa có dịch vụ trong danh mục.</p>@endif
            </div>
            @error('medical_service_ids')<div class="field-error">{{ $message }}</div>@enderror
            @error('medical_service_ids.*')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="card">
            <div class="page-header"><div><h2 class="card-title">Các trường trên phiếu</h2><p class="form-help">Dùng “Bảng chỉ số” cho xét nghiệm hoặc phép đo lặp lại. Không điền sẵn khoảng chuẩn y khoa nếu chưa được cơ sở xác nhận.</p></div><button class="admin-btn admin-btn-secondary" type="button" data-add-template-field>Thêm trường</button></div>
            @php($fields = old('fields', $template->fields ?: [['key' => 'result', 'label' => '', 'type' => 'textarea', 'required' => true]]))
            <div data-template-fields>
                @foreach ($fields as $i => $field)
                    <div class="card result-template-field" data-template-field>
                        <div class="form-grid">
                            <div class="form-group"><label class="form-label">Mã trường <span class="required">*</span></label><input class="form-control" name="fields[{{ $i }}][key]" value="{{ $field['key'] ?? '' }}" maxlength="40" pattern="[A-Za-z0-9_-]+" required placeholder="vd: findings"></div>
                            <div class="form-group"><label class="form-label">Nhãn hiển thị <span class="required">*</span></label><input class="form-control" name="fields[{{ $i }}][label]" value="{{ $field['label'] ?? '' }}" maxlength="120" required placeholder="vd: Mô tả kết quả"></div>
                            <div class="form-group"><label class="form-label">Kiểu dữ liệu</label><select class="form-control" name="fields[{{ $i }}][type]"><option value="text" @selected(($field['type'] ?? '') === 'text')>Một dòng</option><option value="textarea" @selected(($field['type'] ?? '') === 'textarea')>Nhiều dòng</option><option value="number" @selected(($field['type'] ?? '') === 'number')>Số</option><option value="measurements" @selected(($field['type'] ?? '') === 'measurements')>Bảng chỉ số</option></select></div>
                            <div class="form-group"><label class="form-label">Đơn vị mặc định</label><input class="form-control" name="fields[{{ $i }}][unit]" value="{{ $field['unit'] ?? '' }}" maxlength="40" placeholder="vd: mmol/L"></div>
                            <div class="form-group"><label class="form-label">Khoảng tham chiếu gợi ý</label><input class="form-control" name="fields[{{ $i }}][reference]" value="{{ $field['reference'] ?? '' }}" maxlength="120" placeholder="Chỉ nhập nếu đã được xác nhận"></div>
                            <div class="form-group"><label class="form-label">Bắt buộc</label><input type="hidden" name="fields[{{ $i }}][required]" value="0"><label><input type="checkbox" name="fields[{{ $i }}][required]" value="1" @checked($field['required'] ?? false)> Yêu cầu nhập trường này</label></div>
                        </div>
                        <button type="button" class="admin-btn admin-btn-secondary" data-remove-template-field>Xóa trường</button>
                    </div>
                @endforeach
            </div>
            @foreach (['fields' => 'Vui lòng kiểm tra danh sách trường.', 'fields.*.key' => 'Mã trường chỉ dùng chữ không dấu, số, gạch dưới hoặc gạch nối.', 'fields.*.label' => 'Vui lòng nhập nhãn hiển thị.'] as $key => $message)
                @error($key)<div class="field-error">{{ $message }}</div>@enderror
            @endforeach
            <template data-template-field-prototype>
                <div class="card result-template-field" data-template-field><div class="form-grid">
                    <div class="form-group"><label class="form-label">Mã trường <span class="required">*</span></label><input class="form-control" data-name="key" maxlength="40" pattern="[A-Za-z0-9_-]+" required placeholder="vd: findings"></div>
                    <div class="form-group"><label class="form-label">Nhãn hiển thị <span class="required">*</span></label><input class="form-control" data-name="label" maxlength="120" required></div>
                    <div class="form-group"><label class="form-label">Kiểu dữ liệu</label><select class="form-control" data-name="type"><option value="text">Một dòng</option><option value="textarea">Nhiều dòng</option><option value="number">Số</option><option value="measurements">Bảng chỉ số</option></select></div>
                    <div class="form-group"><label class="form-label">Đơn vị mặc định</label><input class="form-control" data-name="unit" maxlength="40"></div>
                    <div class="form-group"><label class="form-label">Khoảng tham chiếu gợi ý</label><input class="form-control" data-name="reference" maxlength="120"></div>
                    <div class="form-group"><label class="form-label">Bắt buộc</label><input type="hidden" data-name="required" value="0"><label><input type="checkbox" data-name="required" value="1" checked> Yêu cầu nhập trường này</label></div>
                </div><button type="button" class="admin-btn admin-btn-secondary" data-remove-template-field>Xóa trường</button></div>
            </template>
            <div class="form-actions"><a class="admin-btn admin-btn-secondary" href="{{ route('admin.service-result-templates.index') }}">Hủy</a><button class="admin-btn admin-btn-primary" type="submit">{{ $template->exists ? 'Lưu thay đổi' : 'Tạo mẫu phiếu' }}</button></div>
        </div>
    </form>
    <script>
        (() => {
            const search = document.querySelector('#service-template-search');
            const options = [...document.querySelectorAll('[data-service-option]')];
            const updateCount = () => {
                const count = document.querySelectorAll('[data-service-option] input:checked').length;
                document.querySelector('[data-selected-service-count]').textContent = count;
            };
            search?.addEventListener('input', () => {
                const term = search.value.trim().toLocaleLowerCase();
                options.forEach((option) => { option.hidden = !option.dataset.serviceOption.includes(term); });
            });
            options.forEach((option) => option.querySelector('input')?.addEventListener('change', updateCount));
            updateCount();

            const list = document.querySelector('[data-template-fields]');
            const prototype = document.querySelector('[data-template-field-prototype]');
            const nextIndex = () => Math.max(-1, ...[...list.querySelectorAll('[name^="fields["]')].map((el) => Number(el.name.match(/^fields\[(\d+)\]/)?.[1] ?? -1))) + 1;
            document.querySelector('[data-add-template-field]')?.addEventListener('click', () => {
                const index = nextIndex();
                const fragment = prototype.content.cloneNode(true);
                fragment.querySelectorAll('[data-name]').forEach((el) => { el.name = `fields[${index}][${el.dataset.name}]`; });
                list.append(fragment);
            });
            list.addEventListener('click', (event) => {
                if (event.target.closest('[data-remove-template-field]')) event.target.closest('[data-template-field]')?.remove();
            });
        })();
    </script>
@endsection
