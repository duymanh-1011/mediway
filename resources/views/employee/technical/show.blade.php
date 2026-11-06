@extends('employee.layout')
@section('title', $title)
@section('content')
<section class="tech-page">
    <a class="tech-back" href="{{ route($routePrefix.'.index') }}">← Quay lại danh sách</a>
    <header class="tech-header"><p class="portal-eyebrow">{{ $title }}</p><h1>{{ $record->medicalService?->name ?? 'Chi tiết chỉ định' }}</h1><p>{{ $record->serviceOrder?->service_order_code }} · {{ $record->serviceStatus?->description ?? 'Chưa cập nhật' }}</p></header>
    <section class="tech-panel"><h2>Thông tin chỉ định</h2><dl class="tech-facts"><div><dt>Bệnh nhân</dt><dd>{{ $record->serviceOrder?->medicalVisit?->patient?->full_name ?? 'Chưa cập nhật' }}</dd></div><div><dt>Mã bệnh nhân</dt><dd>{{ $record->serviceOrder?->medicalVisit?->patient?->patient_code ?? 'Chưa cập nhật' }}</dd></div><div><dt>Nhân viên thực hiện</dt><dd>{{ $record->performer?->full_name ?? 'Chưa có nhân viên phụ trách' }}</dd></div><div><dt>Thời gian bắt đầu</dt><dd>{{ $record->started_at?->format('d/m/Y H:i') ?? 'Chưa bắt đầu' }}</dd></div><div><dt>Thanh toán</dt><dd>{{ $record->payable?->paymentStatus?->description ?? 'Chưa cập nhật' }}</dd></div></dl>@if($record->serviceOrder?->instructions)<p><strong>Hướng dẫn:</strong> {{ $record->serviceOrder->instructions }}</p>@endif</section>
    {{-- Các nút là gợi ý theo trạng thái; máy chủ luôn kiểm tra lại trước khi ghi. --}}
    @if ($record->serviceStatus?->name === 'PENDING')<section class="tech-panel"><h2>Thực hiện dịch vụ</h2><p>Kiểm tra chỉ định và điều kiện thanh toán trước khi bắt đầu.</p><form method="post" action="{{ route($routePrefix.'.start', $record->id) }}">@csrf<button class="portal-btn portal-btn--primary">Bắt đầu thực hiện</button></form></section>@endif
    @if ($record->result)<section class="tech-panel"><h2>Kết quả kỹ thuật</h2><p>{{ $record->result->result_summary !== null && trim($record->result->result_summary) !== '' ? $record->result->result_summary : 'Không có nội dung văn bản.' }}</p>@if ($record->result->parameterDisplayRows())<h3>Chỉ số đã ghi nhận</h3><dl class="tech-parameters">@foreach ($record->result->parameterDisplayRows() as $row)<div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach</dl>@endif<p>Trạng thái xác minh: {{ $record->result->verified_by_user_id ? 'Đã xác minh' : 'Chưa xác minh' }}</p><div class="tech-actions">@if($record->result->hasDocument('pdf'))<a href="{{ route($routePrefix.'.document', [$record->id, 'pdf']) }}">Mở PDF</a>@endif @if($record->result->hasDocument('image'))<a href="{{ route($routePrefix.'.document', [$record->id, 'image']) }}">Mở ảnh</a>@endif</div></section>
    @endif
    @if ($record->serviceStatus?->name === 'PROCESSING' && (int) $record->performed_by_employee_id === (int) $employee->id && ! $record->result?->verified_by_user_id && $record->result?->patient_publication_status !== 'PUBLISHED')<section class="tech-panel"><h2>{{ $record->result ? 'Cập nhật kết quả kỹ thuật' : 'Nhập kết quả kỹ thuật' }}</h2><p>Kết quả được hiển thị cho bệnh nhân khi đã lưu. Bạn có thể nhập nội dung hoặc tải tệp.</p>@if ($routePrefix === 'employee.imaging')<p>Chỉ nhập thông tin kỹ thuật; kết luận y khoa thuộc người có thẩm quyền xác nhận.</p>@endif<form method="post" enctype="multipart/form-data" action="{{ route($routePrefix.'.save', $record->id) }}" class="tech-form">@csrf<input type="hidden" name="result_version" value="{{ old('result_version', \App\Models\ServiceResult::editVersion($record->result)) }}"><label>{{ $routePrefix === 'employee.imaging' ? 'Mô tả kỹ thuật / hình ảnh' : 'Nội dung kết quả' }}<textarea name="result_summary" rows="5" maxlength="10000">{{ old('result_summary', $record->result?->result_summary) }}</textarea></label>{{-- Bảng chỉ số áp dụng cho xét nghiệm; dữ liệu cũ không rõ định dạng được giữ nguyên. --}}
    @if ($routePrefix === 'employee.laboratory')
        @php($existingRows = \App\Models\ServiceResult::editableParameters($record->result?->result_parameters))
        @if ($existingRows !== null)
            @php($parameterRows = old('parameters', $existingRows ?: [['name' => '', 'value' => '', 'unit' => '']]))
            <fieldset class="tech-parameter-editor" data-parameter-editor><legend>Chỉ số xét nghiệm</legend><p>Tên chỉ số là mục được kiểm tra; giá trị là kết quả đo hoặc kết quả bằng chữ. Đơn vị có thể để trống nếu không có. Không tự thêm khoảng tham chiếu hay đánh giá.</p>
                <input type="hidden" name="parameters_present" value="1">
                <div data-parameter-list>
                    @foreach ($parameterRows as $index => $parameter)
                        <div class="tech-parameter-row" data-parameter-row>
                            <label>Tên chỉ số<input name="parameters[{{ $index }}][name]" maxlength="120" value="{{ $parameter['name'] ?? '' }}"></label>
                            <label>Giá trị<input name="parameters[{{ $index }}][value]" maxlength="200" value="{{ $parameter['value'] ?? '' }}"></label>
                            <label>Đơn vị (nếu có)<input name="parameters[{{ $index }}][unit]" maxlength="60" value="{{ $parameter['unit'] ?? '' }}"></label>
                            <button type="button" class="portal-btn portal-btn--secondary" data-remove-parameter>Xóa dòng</button>
                        </div>
                    @endforeach
                </div>
                <template data-parameter-template><div class="tech-parameter-row" data-parameter-row><label>Tên chỉ số<input data-field="name" maxlength="120"></label><label>Giá trị<input data-field="value" maxlength="200"></label><label>Đơn vị (nếu có)<input data-field="unit" maxlength="60"></label><button type="button" class="portal-btn portal-btn--secondary" data-remove-parameter>Xóa dòng</button></div></template>
                <button type="button" class="portal-btn portal-btn--secondary" data-add-parameter>+ Thêm chỉ số</button><small>Tối đa 30 chỉ số.</small>
            </fieldset>
        @else
            <p>Chỉ số cũ có định dạng khác và được giữ nguyên khi cập nhật nội dung.</p>
        @endif
    @endif
    <label>Phiếu kết quả PDF (tối đa 5 MB)<input type="file" name="result_pdf" accept=".pdf,application/pdf"></label><label>Ảnh kết quả (tối đa 2 MB)<input type="file" name="result_image" accept=".jpg,.jpeg,.png,.webp"></label><button class="portal-btn portal-btn--primary">Lưu kết quả</button></form></section>@endif
    @if ($record->serviceStatus?->name === 'PROCESSING' && (int) $record->performed_by_employee_id === (int) $employee->id && $record->result)<section class="tech-panel"><h2>Hoàn tất dịch vụ</h2><p>Kiểm tra kết quả trước khi hoàn tất.</p><form method="post" action="{{ route($routePrefix.'.complete', $record->id) }}">@csrf<button class="portal-btn portal-btn--primary">Hoàn tất dịch vụ</button></form></section>@endif
</section>
@endsection
