@if ($canEnterResult)
    @if (! $resultTemplate || ! $resultTemplate->is_active)
        <div class="portal-alert portal-alert--warning">Dịch vụ này chưa được gán mẫu phiếu kết quả đang hoạt động. Vui lòng liên hệ quản trị viên.</div>
    @else
        @php
            $resultFormHasErrors = $errors->any();
        @endphp
        <div class="tech-actions">
            <button type="button" class="portal-btn portal-btn--primary" data-open-result-form aria-expanded="{{ $resultFormHasErrors ? 'true' : 'false' }}" aria-controls="service-result-entry">+ Thêm phiếu kết quả</button>
        </div>
        <section id="service-result-entry" class="tech-panel tech-detail-panel tech-entry-panel" @unless ($resultFormHasErrors) hidden @endunless>
            <h2>Thêm phiếu kết quả</h2>
            <p>Mẫu áp dụng: <strong>{{ $resultTemplate->name }}</strong>. Nhập kết quả theo các trường được cấu hình cho dịch vụ này.</p>
            <form method="post" enctype="multipart/form-data" action="{{ route($routePrefix.'.save', $detailQuery) }}" class="tech-form">
                @csrf
                <input type="hidden" name="result_version" value="{{ is_string($submittedVersion) ? $submittedVersion : $currentVersion }}">
                <input type="hidden" name="template_version" value="{{ $templateVersion }}">
                @foreach ($resultTemplate->fields as $field)
                    @php
                        $fieldKey = $field['key'];
                        $fieldValue = old('template_values.'.$fieldKey, $result?->template_values[$fieldKey] ?? '');
                        $isConclusionField = in_array($fieldKey, ['conclusion', 'interpretation', 'comment'], true);
                    @endphp
                    <fieldset class="tech-parameter-editor">
                        <legend>{{ $field['label'] }} @if ($field['required'])<span aria-label="bắt buộc">*</span>@endif</legend>
                        @if (($field['type'] ?? '') === 'measurements')
                            <p>Ghi từng chỉ số; khoảng tham chiếu và đánh giá chỉ nhập khi có thông tin được chỉ định.</p>
                            <div class="tech-result-table-wrap tech-template-measurements" role="region" aria-label="Bảng {{ $field['label'] }}" tabindex="0">
                                <table class="tech-result-table">
                                    <thead><tr><th>Chỉ số</th><th>Kết quả</th><th>Đơn vị</th><th>Khoảng tham chiếu</th><th>Đánh giá</th></tr></thead>
                                    <tbody>
                                    @for ($rowIndex = 0; $rowIndex < 8; $rowIndex++)
                                        @php($rowValue = is_array($fieldValue) ? ($fieldValue[$rowIndex] ?? []) : [])
                                        <tr>
                                            <td><input aria-label="Tên chỉ số {{ $rowIndex + 1 }}" name="template_values[{{ $fieldKey }}][{{ $rowIndex }}][name]" maxlength="120" value="{{ $rowValue['name'] ?? '' }}"></td>
                                            <td><input aria-label="Kết quả {{ $rowIndex + 1 }}" name="template_values[{{ $fieldKey }}][{{ $rowIndex }}][value]" maxlength="200" value="{{ $rowValue['value'] ?? '' }}"></td>
                                            <td><input aria-label="Đơn vị {{ $rowIndex + 1 }}" name="template_values[{{ $fieldKey }}][{{ $rowIndex }}][unit]" maxlength="60" value="{{ $rowValue['unit'] ?? ($field['unit'] ?? '') }}"></td>
                                            <td><input aria-label="Khoảng tham chiếu {{ $rowIndex + 1 }}" name="template_values[{{ $fieldKey }}][{{ $rowIndex }}][reference]" maxlength="120" value="{{ $rowValue['reference'] ?? ($field['reference'] ?? '') }}"></td>
                                            <td><input aria-label="Đánh giá {{ $rowIndex + 1 }}" name="template_values[{{ $fieldKey }}][{{ $rowIndex }}][flag]" maxlength="60" value="{{ $rowValue['flag'] ?? '' }}"></td>
                                        </tr>
                                    @endfor
                                    </tbody>
                                </table>
                            </div>
                        @elseif (($field['type'] ?? '') === 'textarea')
                            <label>{{ $field['label'] }}
                                <textarea name="template_values[{{ $fieldKey }}]" rows="5" maxlength="10000" @required($field['required'] || $isConclusionField)>{{ is_string($fieldValue) ? $fieldValue : '' }}</textarea>
                            </label>
                        @else
                            <label>{{ $field['label'] }}
                                <input type="{{ ($field['type'] ?? '') === 'number' ? 'number' : 'text' }}" name="template_values[{{ $fieldKey }}]" value="{{ is_scalar($fieldValue) ? $fieldValue : '' }}" maxlength="10000" @if (($field['type'] ?? '') === 'number') step="any" @endif @required($field['required'] || $isConclusionField)>
                            </label>
                            @if (filled($field['unit'] ?? null) || filled($field['reference'] ?? null))
                                <small>{{ collect([$field['unit'] ?? null, $field['reference'] ?? null])->filter()->join(' · ') }}</small>
                            @endif
                        @endif
                        @error('template_values.'.$fieldKey)<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                @endforeach
                @unless (collect($resultTemplate->fields)->contains(fn ($field) => in_array($field['key'] ?? null, ['conclusion', 'interpretation', 'comment'], true)))
                    <fieldset class="tech-parameter-editor">
                        <legend>Kết luận <span aria-label="bắt buộc">*</span></legend>
                        <label>Kết luận dịch vụ
                            <textarea name="result_conclusion" rows="5" maxlength="10000" required>{{ old('result_conclusion', $result?->result_conclusion ?? '') }}</textarea>
                        </label>
                        @error('result_conclusion')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                @endunless
                <div class="tech-upload">
                    <label>Tệp kết quả PDF (không bắt buộc, tối đa 5 MB)<input type="file" name="result_pdf" accept=".pdf,application/pdf"></label>
                    <label>Ảnh kết quả (không bắt buộc, tối đa 2 MB)<input type="file" name="result_image" accept=".jpg,.jpeg,.png,.webp"></label>
                </div>
                <label class="tech-result-confirmation">
                    <input type="checkbox" name="confirm_result" value="1" required>
                    <span>Tôi đã kiểm tra thông tin và xác nhận lưu phiếu kết quả.</span>
                </label>
                <button type="submit" class="portal-btn portal-btn--primary" data-pending-label="Đang lưu…">Lưu phiếu kết quả</button>
            </form>
        </section>
        <script>
            (() => {
                const button = document.querySelector('[data-open-result-form]');
                const form = document.getElementById('service-result-entry');
                button?.addEventListener('click', () => {
                    const open = form.hidden;
                    form.hidden = !open;
                    button.setAttribute('aria-expanded', String(open));
                    if (open) form.querySelector('input:not([type="hidden"]), textarea')?.focus();
                });
            })();
        </script>
    @endif
@endif
