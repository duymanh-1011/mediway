@extends('doctor.layout')
@section('title', 'Chi tiết lượt khám')
@section('breadcrumb', 'Chi tiết lượt khám')
@section('content')
<section class="tech-page tech-detail-page doctor-visit" data-doctor-workspace data-visit-id="{{ $record->id }}">
    <a class="portal-btn portal-btn--secondary tech-back" href="{{ route('doctor.visits.index') }}">← Quay lại lượt khám</a>
    <section class="tech-panel tech-detail-panel doctor-visit__hero"><p class="portal-eyebrow">HỒ SƠ KHÁM BỆNH</p><h1>{{ $record->patient?->full_name ?? 'Chưa cập nhật' }}</h1>
        <p class="doctor-visit__meta"><span>Mã lượt khám: <strong>{{ $record->visit_code }}</strong></span>@include('doctor.visits._status', ['visit' => $record])</p>
        <h2>Thông tin lượt khám</h2><dl class="tech-facts">
            <div><dt>Mã bệnh nhân</dt><dd>{{ $record->patient?->patient_code ?? '—' }}</dd></div>
            <div><dt>Bác sĩ phụ trách</dt><dd>{{ $record->doctor?->full_name ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Chuyên khoa</dt><dd>{{ $record->specialty?->name ?? '—' }}</dd></div>
            <div><dt>Phòng khám</dt><dd>{{ $record->examinationRoom?->name ?? '—' }}</dd></div>
            <div><dt>Tiếp nhận lúc</dt><dd>{{ $record->checked_in_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div><dt>Lịch hẹn</dt><dd>{{ $record->appointment?->appointment_code ?? 'Không có lịch hẹn' }}</dd></div>
        </dl>
    </section>
    {{-- Các liên kết đưa đến đúng phần trong cùng trang và vẫn dùng được khi tắt JavaScript. --}}
    <nav class="doctor-visit__quick" aria-label="Chuyển nhanh trong lượt khám">
        <a href="#noi-dung-kham">Nội dung khám</a><a href="#chan-doan">Chẩn đoán</a><a href="#dich-vu">Dịch vụ &amp; kết quả</a><a href="#don-thuoc">Đơn thuốc</a><a href="#ket-luan">Kết luận</a>
    </nav>
    @if($conclusionLocked)<p class="portal-alert">Đã lưu kết luận khám. Hồ sơ và kết luận khám chỉ còn được xem.</p>@elseif($prescriptionLocked)<p class="portal-alert">Đã tạo đơn thuốc. Nội dung khám, chẩn đoán và chỉ định dịch vụ chỉ còn được xem.</p>@endif
    <section id="noi-dung-kham" class="tech-panel doctor-visit__section"><h2>Nội dung khám</h2>
        @if($canEditBeforePrescription)
        @php($noteForm = old('form_key') === 'clinical_note')
        @if($record->clinicalNote)
            <div class="doctor-visit__clinical-summary">
                @foreach(['Lý do đến khám' => $record->clinicalNote->reason_for_visit, 'Triệu chứng hiện tại' => $record->clinicalNote->current_symptoms, 'Bệnh sử hiện tại' => $record->clinicalNote->present_illness_history, 'Tiền sử liên quan' => $record->clinicalNote->relevant_medical_history, 'Khám thực thể' => $record->clinicalNote->physical_examination, 'Đánh giá ban đầu' => $record->clinicalNote->initial_assessment, 'Ghi chú' => $record->clinicalNote->notes] as $label => $value)
                    @if($value)<div><strong>{{ $label }}</strong><p>{{ $value }}</p></div>@endif
                @endforeach
            </div>
        @else
            <p>Chưa có nội dung khám. Mở biểu mẫu để ghi nhận thông tin chuyên môn.</p>
        @endif
        {{-- Giữ biểu mẫu đang nhập mở nếu dữ liệu gửi lên chưa hợp lệ. --}}
        <details class="doctor-visit__editor" @if($noteForm) open @endif><summary>{{ $record->clinicalNote ? 'Xem và chỉnh sửa nội dung khám' : 'Nhập nội dung khám' }}</summary>
        <form class="tech-form" method="post" action="{{ route('doctor.visits.clinical.update', $record->id) }}">
            @csrf @method('PUT')
            <input type="hidden" name="form_key" value="clinical_note">
            <input type="hidden" name="record_version" value="{{ $noteForm ? old('record_version') : \App\Services\DoctorClinicalVersion::of($record->clinicalNote) }}">
            @foreach(['reason_for_visit' => 'Lý do đến khám', 'current_symptoms' => 'Triệu chứng hiện tại', 'present_illness_history' => 'Bệnh sử hiện tại', 'relevant_medical_history' => 'Tiền sử liên quan', 'physical_examination' => 'Khám thực thể', 'initial_assessment' => 'Đánh giá ban đầu', 'notes' => 'Ghi chú'] as $field => $label)
                <label for="clinical-{{ $field }}">{{ $label }}<textarea id="clinical-{{ $field }}" name="{{ $field }}" rows="2" maxlength="{{ $field === 'reason_for_visit' ? 2000 : 5000 }}">{{ $noteForm ? old($field) : $record->clinicalNote?->{$field} }}</textarea></label>
            @endforeach
            <button class="portal-btn portal-btn--primary" type="submit">Lưu nội dung khám</button>
        </form></details>
        @else
        @forelse(['Lý do đến khám' => $record->clinicalNote?->reason_for_visit, 'Triệu chứng' => $record->clinicalNote?->current_symptoms, 'Bệnh sử' => $record->clinicalNote?->present_illness_history, 'Tiền sử liên quan' => $record->clinicalNote?->relevant_medical_history, 'Khám thực thể' => $record->clinicalNote?->physical_examination, 'Đánh giá ban đầu' => $record->clinicalNote?->initial_assessment, 'Ghi chú' => $record->clinicalNote?->notes] as $label => $value)
            @if($value)<p><strong>{{ $label }}:</strong> {{ $value }}</p>@endif
        @empty @endforelse
        @unless($record->clinicalNote)<p>Chưa có nội dung khám.</p>@endunless
        @unless($prescriptionLocked || $conclusionLocked)<p>Chỉ có thể sửa nội dung khi lượt khám đang khám hoặc bệnh nhân đã quay lại sau dịch vụ.</p>@endunless
        @endif
    </section>
    <section id="chan-doan" class="tech-panel doctor-visit__section"><h2>Chẩn đoán</h2>
        @php($diagnosisTypes = ['PRIMARY' => 'Chẩn đoán chính', 'SECONDARY' => 'Chẩn đoán phụ', 'PRELIMINARY' => 'Chẩn đoán sơ bộ', 'CONFIRMED' => 'Chẩn đoán xác định'])
        @forelse($record->diagnoses as $diagnosis)
            <article class="doctor-visit__card doctor-clinical-card">
                <div class="doctor-clinical-card__heading"><div><span class="doctor-clinical-card__label">Tên bệnh</span><h3>{{ $diagnosis->disease?->name ?? 'Chưa cập nhật' }}</h3></div><span class="doctor-clinical-card__type">{{ $diagnosisTypes[$diagnosis->diagnosis_type] ?? $diagnosis->diagnosis_type }}</span></div>
                <dl class="doctor-clinical-card__facts">
                    <div><dt>Mã bệnh</dt><dd>{{ $diagnosis->disease?->disease_code ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Mức độ</dt><dd>{{ filled($diagnosis->severity) ? $diagnosis->severity : 'Chưa ghi nhận' }}</dd></div>
                </dl>
                <div class="doctor-clinical-card__description"><span class="doctor-clinical-card__label">Mô tả chẩn đoán</span><p>{{ filled($diagnosis->diagnosis_description) ? $diagnosis->diagnosis_description : 'Chưa có mô tả' }}</p></div>
                <div class="doctor-clinical-card__description"><span class="doctor-clinical-card__label">Ghi chú</span><p>{{ filled($diagnosis->notes) ? $diagnosis->notes : 'Chưa có ghi chú' }}</p></div>
            @if($canEditBeforePrescription)
                @php($editForm = old('form_key') === 'diagnosis-'.$diagnosis->id)
                <details @if($editForm) open @endif><summary>Chỉnh sửa chẩn đoán</summary>
                    <form class="tech-form" method="post" action="{{ route('doctor.visits.diagnoses.update', [$record->id, $diagnosis->id]) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="form_key" value="diagnosis-{{ $diagnosis->id }}">
                        <input type="hidden" name="record_version" value="{{ $editForm ? old('record_version') : \App\Services\DoctorClinicalVersion::of($diagnosis) }}">
                        @include('doctor.visits._diagnosis-fields', ['selectedDisease' => $editForm ? old('disease_id') : $diagnosis->disease_id, 'selectedType' => $editForm ? old('diagnosis_type') : $diagnosis->diagnosis_type, 'severity' => $editForm ? old('severity') : $diagnosis->severity, 'description' => $editForm ? old('diagnosis_description') : $diagnosis->diagnosis_description, 'notes' => $editForm ? old('notes') : $diagnosis->notes, 'currentDisease' => $diagnosis->disease])
                        <button class="portal-btn portal-btn--primary" type="submit">Lưu chẩn đoán</button>
                    </form>
                </details>
            @endif</article>
        @empty<p>Chưa có chẩn đoán.</p>@endforelse
        @if($canEditBeforePrescription)
            @php($newForm = old('form_key') === 'diagnosis-new')
            <details @if($newForm) open @endif><summary>+ Thêm chẩn đoán</summary>
                <form class="tech-form" method="post" action="{{ route('doctor.visits.diagnoses.store', $record->id) }}">
                    @csrf<input type="hidden" name="form_key" value="diagnosis-new">
                    @include('doctor.visits._diagnosis-fields', ['selectedDisease' => $newForm ? old('disease_id') : null, 'selectedType' => $newForm ? old('diagnosis_type') : null, 'severity' => $newForm ? old('severity') : null, 'description' => $newForm ? old('diagnosis_description') : null, 'notes' => $newForm ? old('notes') : null, 'currentDisease' => null])
                    <button class="portal-btn portal-btn--primary" type="submit">Thêm chẩn đoán</button>
                </form>
            </details>
        @endif
    </section>
    <section id="dich-vu" class="tech-panel doctor-visit__section"><h2>Chỉ định và kết quả dịch vụ</h2>
        @if(! $prescriptionLocked && ! $conclusionLocked && $record->canOrderServices())<a class="portal-btn portal-btn--primary" href="{{ route('doctor.visits.orders.create', $record->id) }}">Chỉ định dịch vụ</a>@endif
        @forelse($record->serviceOrders as $order)
            <article class="doctor-visit__card"><h3>{{ $order->service_order_code }} · {{ $order->ordered_at?->format('d/m/Y H:i') ?? 'Chưa ghi thời gian' }}</h3>
            <p><strong>Trạng thái phiếu chỉ định:</strong> {{ \App\Models\AdminDisplay::label($order->status) }}</p>
            @if($order->reason)<p><strong>Lý do:</strong> {{ $order->reason }}</p>@endif
            @if($order->instructions)<p><strong>Hướng dẫn:</strong> {{ $order->instructions }}</p>@endif
            @foreach($order->items as $item)<div class="doctor-visit__item"><p><strong>{{ $item->medicalService?->name ?? 'Dịch vụ' }}</strong> · Trạng thái dịch vụ: {{ \App\Models\AdminDisplay::label($item->serviceStatus?->name, 'service') }}</p>
                @unless($item->result?->hasContent())<p>Chưa có kết quả.</p>@endunless
                @if($item->result?->result_summary)<p>{{ $item->result->result_summary }}</p>@endif
                @if($item->result?->result_conclusion)<p>{{ $item->result->result_conclusion }}</p>@endif
                @if($item->result && ($rows = $item->result->parameterDisplayRows())) @foreach($rows as $row)<p><strong>{{ $row['label'] }}:</strong> {{ $row['value'] }}</p>@endforeach @endif
                @if($item->result && ($rows = $item->result->technicalDetailRows())) @foreach($rows as $row)<p><strong>{{ $row['label'] }}:</strong> {{ $row['value'] }}</p>@endforeach @endif
                @php($hasPdf = $item->result?->hasDocument('pdf') ?? false)
                @php($hasImage = $item->result?->hasDocument('image') ?? false)
                @if($hasPdf || $hasImage)
                    <div class="doctor-result-files" aria-label="Tệp kết quả dịch vụ">
                        @if($hasPdf)<a class="portal-btn portal-btn--secondary" href="{{ route('doctor.visits.results.document', ['visit' => $record->id, 'result' => $item->result->id, 'type' => 'pdf']) }}" target="_blank" rel="noopener noreferrer">Xem tệp PDF <span aria-hidden="true">↗</span></a>@endif
                        @if($hasImage)<a class="portal-btn portal-btn--secondary" href="{{ route('doctor.visits.results.document', ['visit' => $record->id, 'result' => $item->result->id, 'type' => 'image']) }}" target="_blank" rel="noopener noreferrer">Xem ảnh kết quả <span aria-hidden="true">↗</span></a>@endif
                    </div>
                @endif
            </div>@endforeach</article>
        @empty<p>Chưa có chỉ định dịch vụ.</p>@endforelse
    </section>
    <section id="don-thuoc" class="tech-panel doctor-visit__section"><h2>Đơn thuốc</h2>
        @if($record->prescription)
            <div class="doctor-prescription-overview">
                <dl class="doctor-prescription-overview__facts">
                    <div><dt>Mã đơn thuốc</dt><dd>{{ $record->prescription->prescription_code }}</dd></div>
                    <div><dt>Thời gian kê đơn</dt><dd>{{ $record->prescription->prescribed_at?->format('d/m/Y H:i') ?? 'Chưa ghi thời gian' }}</dd></div>
                    <div><dt>Trạng thái</dt><dd>{{ \App\Models\AdminDisplay::label($record->prescription->status) }}</dd></div>
                </dl>
                <a class="portal-btn portal-btn--secondary" href="{{ route('doctor.visits.prescriptions.print', $record->id) }}">Xem và in đơn thuốc</a>
            </div>
            <h3 class="doctor-prescription-heading">Thuốc được kê</h3>
            @foreach($record->prescription->items as $item)<article class="doctor-visit__card doctor-medicine-card">
                <div class="doctor-medicine-card__heading"><h4>{{ $item->medicine?->name ?? 'Thuốc' }}@if(filled($item->medicine?->strength)) {{ $item->medicine->strength }}@endif</h4><span>Số lượng: {{ $item->quantity }} {{ $item->usage_unit }}</span></div>
                <dl class="doctor-medicine-card__facts">
                    <div><dt>Liều mỗi lần</dt><dd>{{ $item->dose_per_use }}</dd></div>
                    <div><dt>Số lần mỗi ngày</dt><dd>{{ $item->times_per_day }} lần/ngày</dd></div>
                    <div><dt>Thời gian dùng</dt><dd>{{ $item->duration_days }} ngày</dd></div>
                    <div><dt>Cách dùng</dt><dd>{{ $item->usage_method }}</dd></div>
                    @if($item->administration_time)<div><dt>Thời điểm dùng</dt><dd>{{ $item->administration_time }}</dd></div>@endif
                    @if($item->usage_instructions)<div class="doctor-medicine-card__wide"><dt>Hướng dẫn</dt><dd>{{ $item->usage_instructions }}</dd></div>@endif
                </dl>
            </article>@endforeach
            @if($record->prescription->general_instructions)<div class="doctor-prescription-advice"><strong>Lời dặn</strong><p>{{ $record->prescription->general_instructions }}</p></div>@endif
            @if(filled($record->prescription->doctor_notes))<div class="doctor-prescription-advice"><strong>Ghi chú của bác sĩ</strong><p>{{ $record->prescription->doctor_notes }}</p></div>@endif
        @else
            @if($canEdit && ! $conclusionLocked)<a class="portal-btn portal-btn--primary" href="{{ route('doctor.visits.prescriptions.create', $record->id) }}">Kê đơn thuốc</a>@endif
            <p>Chưa có đơn thuốc.</p>
        @endif
    </section>
    <section id="ket-luan" class="tech-panel doctor-visit__section"><h2>Kết luận khám</h2>
        @if($record->conclusion)
            {{-- Hiển thị nội dung đã lưu ở cả lượt đang khám và lượt đã hoàn tất. --}}
            <dl class="doctor-conclusion-summary">
                <div><dt>Kết luận chuyên môn</dt><dd>{{ filled($record->conclusion->professional_conclusion) ? $record->conclusion->professional_conclusion : 'Chưa ghi nhận' }}</dd></div>
                <div><dt>Hướng xử trí</dt><dd>{{ filled($record->conclusion->disposition) ? $record->conclusion->disposition : 'Chưa ghi nhận' }}</dd></div>
                <div><dt>Kế hoạch điều trị</dt><dd>{{ filled($record->conclusion->treatment_plan) ? $record->conclusion->treatment_plan : 'Chưa ghi nhận' }}</dd></div>
                <div><dt>Lời dặn</dt><dd>{{ filled($record->conclusion->instructions) ? $record->conclusion->instructions : 'Chưa ghi nhận' }}</dd></div>
                <div><dt>Hẹn tái khám</dt><dd>{{ is_null($record->conclusion->has_follow_up) ? 'Chưa ghi nhận' : ($record->conclusion->has_follow_up ? 'Có' : 'Không') }}</dd></div>
                <div><dt>Ngày tái khám</dt><dd>{{ $record->conclusion->follow_up_date?->format('d/m/Y') ?? 'Chưa ghi nhận' }}</dd></div>
                <div><dt>Lý do tái khám</dt><dd>{{ filled($record->conclusion->follow_up_reason) ? $record->conclusion->follow_up_reason : 'Chưa ghi nhận' }}</dd></div>
                <div><dt>Ghi chú</dt><dd>{{ filled($record->conclusion->notes) ? $record->conclusion->notes : 'Chưa ghi nhận' }}</dd></div>
            </dl>
        @else
            <p>Chưa có kết luận khám.</p>
        @endif
        @if($canEdit && ! $conclusionLocked)
            @if($conclusionPrerequisites)<div class="portal-alert portal-alert--error" role="status"><strong>Cần bổ sung trước khi lưu kết luận:</strong><ul>@foreach($conclusionPrerequisites as $reason)<li>{{ $reason }}</li>@endforeach</ul>@if($prescriptionLocked)<p>Đơn thuốc đã được tạo nên bác sĩ không thể sửa các mục này trong trang hiện tại.</p>@endif</div>@endif
            @php($conclusionForm = old('form_key') === 'conclusion')
            @php($followUp = $conclusionForm ? old('has_follow_up', '0') : ($record->conclusion?->has_follow_up ? '1' : '0'))
            <details class="doctor-visit__editor" @if($conclusionForm) open @endif><summary>Nhập kết luận khám</summary>
            <form class="tech-form doctor-conclusion-form" method="post" action="{{ route('doctor.visits.conclusion.save', $record->id) }}" data-doctor-conclusion>
                @csrf @method('PUT')<input type="hidden" name="form_key" value="conclusion">
                <input type="hidden" name="record_version" value="{{ $conclusionForm ? old('record_version') : \App\Services\DoctorClinicalVersion::of($record->conclusion) }}">
                <h3>Nội dung kết luận</h3>
                <div><label for="professional-conclusion">Kết luận chuyên môn <span aria-hidden="true">*</span><textarea id="professional-conclusion" name="professional_conclusion" maxlength="10000" rows="4" required>{{ $conclusionForm ? old('professional_conclusion') : $record->conclusion?->professional_conclusion }}</textarea></label>@error('professional_conclusion')<p class="doctor-conclusion-error">{{ $message }}</p>@enderror</div>
                <div><label for="conclusion-disposition">Hướng xử trí<input id="conclusion-disposition" name="disposition" maxlength="120" list="doctor-disposition-options" placeholder="Ví dụ: ngoại trú, theo dõi, chuyển khoa..." value="{{ $conclusionForm ? old('disposition') : $record->conclusion?->disposition }}"></label><datalist id="doctor-disposition-options">@foreach($dispositionOptions as $option)<option value="{{ $option }}"></option>@endforeach</datalist>@error('disposition')<p class="doctor-conclusion-error">{{ $message }}</p>@enderror</div>
                <div><label for="treatment-plan">Kế hoạch điều trị<textarea id="treatment-plan" name="treatment_plan" maxlength="10000" rows="3">{{ $conclusionForm ? old('treatment_plan') : $record->conclusion?->treatment_plan }}</textarea></label>@error('treatment_plan')<p class="doctor-conclusion-error">{{ $message }}</p>@enderror</div>
                <div><label for="conclusion-instructions">Dặn dò bệnh nhân<textarea id="conclusion-instructions" name="instructions" maxlength="10000" rows="3">{{ $conclusionForm ? old('instructions') : $record->conclusion?->instructions }}</textarea></label>@error('instructions')<p class="doctor-conclusion-error">{{ $message }}</p>@enderror</div>
                <fieldset class="doctor-follow-up"><legend>Hẹn tái khám</legend>
                    <input type="hidden" name="has_follow_up" value="0">
                    <label class="doctor-follow-up__toggle" for="has-follow-up"><input id="has-follow-up" type="checkbox" name="has_follow_up" value="1" @checked((string) $followUp === '1') data-follow-up-toggle> Có hẹn tái khám</label>
                    {{-- Không ẩn sẵn: nếu trình duyệt tắt JavaScript vẫn nhập được ngày và lý do. --}}
                    <div class="doctor-follow-up__fields" data-follow-up-fields>
                        <div><label for="follow-up-date">Ngày tái khám <span aria-hidden="true">*</span><input id="follow-up-date" type="date" name="follow_up_date" value="{{ $conclusionForm ? old('follow_up_date') : $record->conclusion?->follow_up_date?->format('Y-m-d') }}"></label>@error('follow_up_date')<p class="doctor-conclusion-error">{{ $message }}</p>@enderror</div>
                        <div><label for="follow-up-reason">Lý do tái khám <span aria-hidden="true">*</span><textarea id="follow-up-reason" name="follow_up_reason" maxlength="5000" rows="3">{{ $conclusionForm ? old('follow_up_reason') : $record->conclusion?->follow_up_reason }}</textarea></label>@error('follow_up_reason')<p class="doctor-conclusion-error">{{ $message }}</p>@enderror</div>
                    </div>
                </fieldset>
                <div><label for="conclusion-notes">Ghi chú<textarea id="conclusion-notes" name="notes" maxlength="5000" rows="3">{{ $conclusionForm ? old('notes') : $record->conclusion?->notes }}</textarea></label>@error('notes')<p class="doctor-conclusion-error">{{ $message }}</p>@enderror</div>
                <p>Vui lòng kiểm tra kỹ. Sau khi lưu kết luận, bác sĩ không thể chỉnh sửa hồ sơ hoặc kết luận khám trong trang này.</p>
                <button type="submit" class="portal-btn portal-btn--primary">Lưu kết luận</button>
            </form></details>
        @endif
        @if($check['ready'])<p>Đã có đủ chẩn đoán, kết luận và không còn dịch vụ cần chờ.</p>@else<p>Hồ sơ còn thiếu:</p><ul>@foreach($check['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>@endif
        @if($canComplete)
            <form method="post" action="{{ route('doctor.visits.complete', $record->id) }}">@csrf<button type="submit" class="portal-btn portal-btn--primary">Hoàn tất lượt khám</button></form>
        @elseif($conclusionLocked && $record->status?->name !== 'COMPLETED')
            <p>Chưa thể hoàn tất lượt khám. Vui lòng kiểm tra trạng thái lượt khám và các mục còn thiếu ở trên.</p>
        @endif
    </section>
</section>
@endsection
