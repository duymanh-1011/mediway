@extends('employee.layout')
@section('title', $title)
@section('content')
@php
    $order = $record->serviceOrder;
    $patient = $order?->medicalVisit?->patient;
    $result = $record->result;
    $stage = $record->technicalStage();
    $isWorking = $stage === 'PROCESSING' && (int) $record->performed_by_employee_id === (int) $employee->id;
    $canEdit = $isWorking && ! $result?->verified_by_user_id && $result?->patient_publication_status !== 'PUBLISHED';
    $hasResult = $result?->hasContent();
    $isClosedVisit = $order?->medicalVisit?->status?->name === 'COMPLETED';
    $detailQuery = ['item' => $record->id, ...$listQuery];
    $currentVersion = \App\Models\ServiceResult::editVersion($result);
    $submittedVersion = old('result_version');
    $submittedSummary = old('result_summary', $result?->result_summary);
@endphp
<section class="tech-page">
    <a class="tech-back" href="{{ route($routePrefix.'.index', $listQuery) }}">← Quay lại danh sách chỉ định</a>

    <header class="tech-header">
        <div>
            <p class="portal-eyebrow">{{ $record->medicalService?->serviceGroups->pluck('name')->join(' · ') ?: 'CHỈ ĐỊNH DỊCH VỤ' }}</p>
            <h1>{{ $record->medicalService?->name ?? 'Chi tiết chỉ định' }}</h1>
            <p>{{ $order?->service_order_code }} @if ($order?->ordered_at) · {{ $order->ordered_at->format('d/m/Y H:i') }} @endif</p>
        </div>
        <span class="tech-status" data-stage="{{ $stage }}">{{ \App\Models\ServiceOrderItem::TECHNICAL_STAGES[$stage] ?? 'Chưa cập nhật' }}</span>
    </header>

    <section class="tech-panel">
        <h2>Thông tin chỉ định</h2>
        <dl class="tech-facts">
            <div><dt>Bệnh nhân</dt><dd>{{ $patient?->full_name ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Mã bệnh nhân</dt><dd>{{ $patient?->patient_code ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Nhân viên thực hiện</dt><dd>{{ $record->performer?->full_name ?? 'Chưa có người thực hiện' }}</dd></div>
            <div><dt>Thanh toán</dt><dd>{{ $record->payable?->paymentStatus?->description ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Bắt đầu lúc</dt><dd>{{ $record->started_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div><dt>Hoàn tất lúc</dt><dd>{{ $record->completed_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
        @if ($order?->reason)<p><strong>Lý do chỉ định</strong> {{ $order->reason }}</p>@endif
        @if ($order?->instructions)<p><strong>Hướng dẫn</strong> {{ $order->instructions }}</p>@endif
    </section>

    @if ($isClosedVisit && ! in_array($stage, ['COMPLETED', 'CANCELLED'], true))
        <div class="portal-alert portal-alert--info">Lượt khám đã hoàn tất. Chỉ định này chỉ có thể xem.</div>
    @elseif ($stage === 'WAITING_PAYMENT')
        <div class="portal-alert portal-alert--info">Có thể bắt đầu sau khi khoản thu của dịch vụ được xác nhận thanh toán.</div>
    @elseif ($stage === 'READY' && $record->serviceStatus?->allows_transition)
        <section class="tech-panel tech-work-step">
            <div><h2>Bắt đầu dịch vụ</h2><p>Chỉ định đã đủ điều kiện thanh toán. Sau khi nhận, chỉ định sẽ được gắn với tài khoản của bạn.</p></div>
            <form method="post" action="{{ route($routePrefix.'.start', $detailQuery) }}">
                @csrf
                <button type="submit" class="portal-btn portal-btn--primary" data-pending-label="Đang bắt đầu…">Bắt đầu thực hiện</button>
            </form>
        </section>
    @endif

    @if ($canEdit && ! $isClosedVisit)
        <section class="tech-panel">
            <h2>{{ $hasResult ? 'Cập nhật kết quả kỹ thuật' : 'Nhập kết quả kỹ thuật' }}</h2>
            <p>Nhập nội dung hoặc đính kèm tệp. Bệnh nhân có thể xem kết quả sau khi lưu.</p>
            <form method="post" enctype="multipart/form-data" action="{{ route($routePrefix.'.save', $detailQuery) }}" class="tech-form">
                @csrf
                <input type="hidden" name="result_version" value="{{ is_string($submittedVersion) ? $submittedVersion : $currentVersion }}">
                <label>{{ $isImaging ? 'Mô tả kỹ thuật / hình ảnh' : 'Nội dung kết quả' }}
                    <textarea name="result_summary" rows="5" maxlength="10000">{{ is_string($submittedSummary) ? $submittedSummary : '' }}</textarea>
                </label>
                @if ($supportsParameters)
                    @php
                        $existingRows = \App\Models\ServiceResult::editableParameters($result?->result_parameters);
                    @endphp
                    @if ($existingRows !== null)
                        @php
                            $defaultRows = $existingRows ?: [['name' => '', 'value' => '', 'unit' => '']];
                            $submittedRows = old('parameters', $defaultRows);
                            $parameterRows = is_array($submittedRows) ? array_slice($submittedRows, 0, 30, true) : $defaultRows;
                        @endphp
                        <fieldset class="tech-parameter-editor" data-parameter-editor>
                            <legend>Chỉ số xét nghiệm</legend>
                            <p>Nhập tên và giá trị cho mỗi chỉ số. Đơn vị có thể để trống.</p>
                            <input type="hidden" name="parameters_present" value="1">
                            <div data-parameter-list>
                                @foreach ($parameterRows as $index => $parameter)
                                    <div class="tech-parameter-row" data-parameter-row>
                                        <label>Tên chỉ số<input name="parameters[{{ $index }}][name]" maxlength="120" value="{{ is_string($parameter['name'] ?? null) ? $parameter['name'] : '' }}"></label>
                                        <label>Giá trị<input name="parameters[{{ $index }}][value]" maxlength="200" value="{{ is_scalar($parameter['value'] ?? null) ? $parameter['value'] : '' }}"></label>
                                        <label>Đơn vị (nếu có)<input name="parameters[{{ $index }}][unit]" maxlength="60" value="{{ is_string($parameter['unit'] ?? null) ? $parameter['unit'] : '' }}"></label>
                                        <button type="button" class="portal-btn portal-btn--secondary" data-remove-parameter>Xóa dòng</button>
                                    </div>
                                @endforeach
                            </div>
                            <template data-parameter-template>
                                <div class="tech-parameter-row" data-parameter-row>
                                    <label>Tên chỉ số<input data-field="name" maxlength="120"></label>
                                    <label>Giá trị<input data-field="value" maxlength="200"></label>
                                    <label>Đơn vị (nếu có)<input data-field="unit" maxlength="60"></label>
                                    <button type="button" class="portal-btn portal-btn--secondary" data-remove-parameter>Xóa dòng</button>
                                </div>
                            </template>
                            <button type="button" class="portal-btn portal-btn--secondary" data-add-parameter>+ Thêm chỉ số</button>
                            <small>Tối đa 30 chỉ số.</small>
                        </fieldset>
                    @else
                        <p>Chỉ số cũ có định dạng khác và được giữ nguyên khi cập nhật nội dung.</p>
                    @endif
                @endif
                <div class="tech-upload">
                    <label>Phiếu kết quả PDF (tối đa 5 MB)<input type="file" name="result_pdf" accept=".pdf,application/pdf"></label>
                    <label>Ảnh kết quả (tối đa 2 MB)<input type="file" name="result_image" accept=".jpg,.jpeg,.png,.webp"></label>
                </div>
                <button type="submit" class="portal-btn portal-btn--primary" data-pending-label="Đang lưu…">Lưu kết quả</button>
            </form>
        </section>
    @endif

    @if ($result)
        <section class="tech-panel">
            <h2>Kết quả đã lưu</h2>
            <p class="tech-result-summary">{{ $result->result_summary !== null && trim($result->result_summary) !== '' ? $result->result_summary : 'Không có nội dung văn bản.' }}</p>
            @if ($rows = $result->parameterDisplayRows())
                <h3>Chỉ số đã ghi nhận</h3>
                <dl class="tech-parameters">
                    @foreach ($rows as $row)
                        <div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
                    @endforeach
                </dl>
            @endif
            <p>Trạng thái xác minh: {{ $result->verified_by_user_id ? 'Đã xác minh' : 'Chưa xác minh' }}</p>
            @if ($result->hasDocument('pdf') || $result->hasDocument('image'))
                <div class="tech-actions">
                    @if ($result->hasDocument('pdf'))<a class="portal-btn portal-btn--secondary" href="{{ route($routePrefix.'.document', [$record->id, 'pdf']) }}">Mở PDF</a>@endif
                    @if ($result->hasDocument('image'))<a class="portal-btn portal-btn--secondary" href="{{ route($routePrefix.'.document', [$record->id, 'image']) }}">Mở ảnh</a>@endif
                </div>
            @endif
        </section>
    @endif

    @if ($isWorking && ! $isClosedVisit && $record->serviceStatus?->allows_transition)
        <section class="tech-panel tech-work-step">
            <div>
                <h2>Hoàn tất dịch vụ</h2>
                <p>{{ $hasResult ? 'Kiểm tra kết quả đã lưu trước khi xác nhận hoàn tất.' : 'Cần lưu kết quả trước khi xác nhận hoàn tất.' }}</p>
            </div>
            @if ($hasResult)
                <form method="post" action="{{ route($routePrefix.'.complete', $detailQuery) }}">
                    @csrf
                    <button type="submit" class="portal-btn portal-btn--primary" data-pending-label="Đang hoàn tất…">Xác nhận hoàn tất</button>
                </form>
            @endif
        </section>
    @endif
</section>
@endsection
