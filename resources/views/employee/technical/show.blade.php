@extends('employee.layout')
@section('title', $title)
@section('content')
@php
    $order = $record->serviceOrder;
    $patient = $order?->medicalVisit?->patient;
    $result = $record->result;
    $stage = $record->technicalStage();
    $displayStatus = \App\Support\TechnicalServiceStatus::for($record);
    $isWorking = $stage === 'PROCESSING' && (int) $record->performed_by_employee_id === (int) $employee->id;
    $canEdit = $isWorking && ! $result?->verified_by_user_id && $result?->patient_publication_status !== 'PUBLISHED';
    $hasResult = $result?->hasContent();
    $isClosedVisit = $order?->medicalVisit?->status?->name === 'COMPLETED';
    $detailQuery = ['item' => $record->id, ...$listQuery];
    $currentVersion = \App\Models\ServiceResult::editVersion($result);
    $submittedVersion = old('result_version');
@endphp
<section class="tech-page tech-detail-page">
    <a class="portal-btn portal-btn--secondary tech-back" href="{{ $backUrl }}">← Quay lại danh sách chỉ định</a>

    @include('employee.technical._heading', [
        'eyebrow' => $record->medicalService?->serviceGroups->pluck('name')->join(' · ') ?: 'CHỈ ĐỊNH DỊCH VỤ',
        'heading' => $record->medicalService?->name ?? 'Chi tiết chỉ định',
        'description' => implode(' · ', array_filter([$order?->service_order_code, $order?->ordered_at?->format('d/m/Y H:i')])),
        'stageLabel' => $displayStatus['label'],
        'statusTone' => $displayStatus['tone'],
    ])

    <section class="tech-panel tech-detail-panel">
        <h2>Thông tin chỉ định</h2>
        <dl class="tech-facts">
            <div><dt>Bệnh nhân</dt><dd>{{ $patient?->full_name ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Mã bệnh nhân</dt><dd>{{ $patient?->patient_code ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Nhân viên thực hiện</dt><dd>{{ $record->performer?->full_name ?? 'Chưa có người thực hiện' }}</dd></div>
            <div><dt>Thanh toán</dt><dd>{{ $record->payable?->paymentStatus?->description ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Bắt đầu lúc</dt><dd>{{ $record->started_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div><dt>Hoàn tất lúc</dt><dd>{{ $record->completed_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
        @if ($order?->reason || $order?->instructions)
            <div class="tech-order-notes">
                @if ($order?->reason)<p><strong>Lý do chỉ định</strong> {{ $order->reason }}</p>@endif
                @if ($order?->instructions)<p><strong>Hướng dẫn</strong> {{ $order->instructions }}</p>@endif
            </div>
        @endif
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
        @php
            $existingDetails = \App\Models\ServiceResult::editableTechnicalDetails($result?->technical_details);
            $techniqueName = \App\Models\ServiceResult::TECHNIQUE_DETAIL_NAME;
            $techniqueIndex = array_search($techniqueName, array_column($existingDetails ?? [], 'name'), true);
            $technique = $techniqueIndex === false ? null : $existingDetails[$techniqueIndex]['value'];
            $extraDetails = $techniqueIndex === false ? $existingDetails : \Illuminate\Support\Arr::except($existingDetails, [$techniqueIndex]);
            $oldDetails = old('details', old('details_present') ? [] : $extraDetails);
            $detailRows = is_array($oldDetails) ? array_slice($oldDetails, 0, 30, true) : [];
            $submittedTechnique = old('technique', $technique);
            $submittedConclusion = old('result_conclusion', $result?->result_conclusion);
            $submittedSummary = old('result_summary', $result?->result_summary);
        @endphp
        <section class="tech-panel tech-detail-panel tech-entry-panel">
            <h2>{{ $hasResult ? 'Cập nhật kết quả kỹ thuật' : 'Nhập kết quả kỹ thuật' }}</h2>
            <p>Lưu kết quả để xem lại phiếu. Bệnh nhân có thể xem kết quả sau khi bạn xác nhận hoàn tất dịch vụ.</p>
            @if (! $groupCode)<p>Dịch vụ cần có một nhóm xác định để xem trước phiếu và dùng các trường riêng theo nhóm.</p>@endif
            <form method="post" enctype="multipart/form-data" action="{{ route($routePrefix.'.save', $detailQuery) }}" class="tech-form">
                @csrf
                <input type="hidden" name="result_version" value="{{ is_string($submittedVersion) ? $submittedVersion : $currentVersion }}">
                <fieldset class="tech-parameter-editor">
                    <legend>1. Kỹ thuật</legend>
                    <p>Phiếu hiển thị tên dịch vụ và thông tin thực hiện đã ghi nhận.</p>
                    @if ($existingDetails !== null)
                        <label>Cách thức và vị trí thực hiện
                            <textarea name="technique" rows="3" maxlength="2000" placeholder="Ví dụ: Chụp X-quang xương đùi trái, tư thế thẳng và nghiêng">{{ is_string($submittedTechnique) ? $submittedTechnique : '' }}</textarea>
                        </label>
                        @if ($supportsDetails)
                            <div class="tech-entry-fields" data-detail-editor>
                                <input type="hidden" name="details_present" value="1">
                                <div data-detail-list>
                                    @foreach ($detailRows as $index => $detail)
                                        <div class="tech-detail-row" data-detail-row>
                                            <label>Tên thông tin<input name="details[{{ $index }}][name]" maxlength="120" value="{{ is_string($detail['name'] ?? null) ? $detail['name'] : '' }}"></label>
                                            <label>Nội dung<textarea name="details[{{ $index }}][value]" maxlength="2000" rows="2">{{ is_scalar($detail['value'] ?? null) ? $detail['value'] : '' }}</textarea></label>
                                            <button type="button" class="portal-btn portal-btn--secondary" data-remove-detail>Xóa dòng</button>
                                        </div>
                                    @endforeach
                                </div>
                                <template data-detail-template><div class="tech-detail-row" data-detail-row><label>Tên thông tin<input data-field="name" maxlength="120"></label><label>Nội dung<textarea data-field="value" maxlength="2000" rows="2"></textarea></label><button type="button" class="portal-btn portal-btn--secondary" data-remove-detail>Xóa dòng</button></div></template>
                                <button type="button" class="portal-btn portal-btn--secondary" data-add-detail>+ Thêm thông tin</button>
                            </div>
                        @endif
                    @else
                        <p>Thông tin kỹ thuật đã lưu theo định dạng cũ được giữ nguyên.</p>
                    @endif
                </fieldset>
                <fieldset class="tech-parameter-editor">
                    <legend>2. Kết luận</legend>
                    <p>Ghi kết luận của dịch vụ trong phạm vi chuyên môn. Thêm mô tả khi cần.</p>
                    <div class="tech-entry-fields">
                        <label>Kết luận dịch vụ
                            <textarea name="result_conclusion" rows="4" maxlength="10000">{{ is_string($submittedConclusion) ? $submittedConclusion : '' }}</textarea>
                        </label>
                        <label>Mô tả kết quả (nếu có)
                            <textarea name="result_summary" rows="3" maxlength="10000">{{ is_string($submittedSummary) ? $submittedSummary : '' }}</textarea>
                        </label>
                    </div>
                </fieldset>
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
                            <legend>{{ $groupCode === 'XN' ? 'Chỉ số xét nghiệm' : 'Chỉ số đo' }}</legend>
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
                @if (! $hideUploads)
                <div class="tech-upload">
                    <label>Phiếu kết quả PDF (tối đa 5 MB)<input type="file" name="result_pdf" accept=".pdf,application/pdf"></label>
                    <label>Ảnh kết quả (tối đa 2 MB)<input type="file" name="result_image" accept=".jpg,.jpeg,.png,.webp"></label>
                </div>
                @endif
                <button type="submit" class="portal-btn portal-btn--primary" data-pending-label="Đang lưu…">Lưu kết quả</button>
            </form>
        </section>
    @endif

    @if ($result)
        <section class="tech-panel tech-detail-panel tech-result-panel">
            <h2>Kết quả đã lưu</h2>
            <section class="tech-result-section" aria-labelledby="saved-details-title">
                <h3 id="saved-details-title">1. Kỹ thuật</h3>
                <p>{{ $record->medicalService?->name ?? 'Chưa cập nhật tên dịch vụ' }}</p>
                @if ($rows = $result->technicalDetailRows())
                    <dl class="tech-parameters">@foreach ($rows as $row)<div><dt>{{ $row['label'] }}</dt><dd class="tech-result-summary">{{ $row['value'] }}</dd></div>@endforeach</dl>
                @endif
            </section>
            <section class="tech-result-section" aria-labelledby="saved-conclusion-title">
                <h3 id="saved-conclusion-title">2. Kết luận</h3>
                <div class="tech-result-content">
                    <p class="tech-result-summary">{{ filled($result->result_conclusion) ? $result->result_conclusion : 'Chưa ghi nhận kết luận.' }}</p>
                </div>
                @if (filled($result->result_summary) && $result->result_summary !== $result->result_conclusion)
                    <div class="tech-result-content tech-entry-fields"><span>Mô tả kết quả</span><p class="tech-result-summary">{{ $result->result_summary }}</p></div>
                @endif
            </section>
            @php
                $resultParameters = \App\Models\ServiceResult::editableParameters($result->result_parameters);
                $displayParameters = $resultParameters === null ? $result->parameterDisplayRows() : $resultParameters;
            @endphp
            @if ($displayParameters)
                <section class="tech-result-section" aria-labelledby="saved-parameters-title">
                    <h3 id="saved-parameters-title">Chỉ số đã ghi nhận</h3>
                    <div class="tech-result-table-wrap" role="region" aria-label="Bảng chỉ số đã ghi nhận" tabindex="0">
                        <table class="tech-result-table"><thead><tr><th scope="col">Tên chỉ số</th><th scope="col">Giá trị</th><th scope="col">Đơn vị</th></tr></thead><tbody>
                            @foreach ($displayParameters as $row)
                                <tr><td>{{ $row['name'] ?? $row['label'] }}</td><td>{{ $row['value'] }}</td><td>{{ filled($row['unit'] ?? null) ? $row['unit'] : '—' }}</td></tr>
                            @endforeach
                        </tbody></table>
                    </div>
                </section>
            @endif
            @if ($groupCode && $hasResult)
                <div class="tech-actions"><a class="portal-btn portal-btn--primary" href="{{ route($routePrefix.'.print', $detailQuery) }}">Xem và in phiếu kết quả</a></div>
            @endif
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
                <p>{{ $hasResult ? 'Kiểm tra kết quả đã lưu. Khi xác nhận hoàn tất, bệnh nhân có thể xem kết quả trên cổng Mediway.' : 'Cần lưu kết quả trước khi xác nhận hoàn tất.' }}</p>
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
