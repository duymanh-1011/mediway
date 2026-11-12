@extends('doctor.layout')
@section('title', 'Chi tiết lượt khám')
@section('breadcrumb', 'Chi tiết lượt khám')
@section('content')
<section class="tech-page tech-detail-page">
    <a class="portal-btn portal-btn--secondary tech-back" href="{{ route('doctor.home') }}">← Quay lại lượt khám</a>
    <section class="tech-panel tech-detail-panel"><p class="portal-eyebrow">HỒ SƠ KHÁM BỆNH</p><h1>{{ $record->patient?->full_name ?? 'Chưa cập nhật' }}</h1>
        <p>{{ $record->visit_code }} · {{ $record->status?->description ?? 'Chưa cập nhật trạng thái' }}</p>
        <h2>Thông tin lượt khám</h2><dl class="tech-facts">
            <div><dt>Mã bệnh nhân</dt><dd>{{ $record->patient?->patient_code ?? '—' }}</dd></div>
            <div><dt>Bác sĩ phụ trách</dt><dd>{{ $doctor->full_name }}</dd></div>
            <div><dt>Chuyên khoa</dt><dd>{{ $record->specialty?->name ?? '—' }}</dd></div>
            <div><dt>Phòng khám</dt><dd>{{ $record->examinationRoom?->name ?? '—' }}</dd></div>
            <div><dt>Tiếp nhận lúc</dt><dd>{{ $record->checked_in_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div><dt>Lịch hẹn</dt><dd>{{ $record->appointment?->appointment_code ?? 'Không có lịch hẹn' }}</dd></div>
        </dl>
    </section>
    <section class="tech-panel"><h2>Nội dung khám</h2>
        @if($canEdit)
        @php($noteForm = old('form_key') === 'clinical_note')
        <p>Ghi nhận thông tin chuyên môn của lượt khám này.</p>
        <form class="tech-form" method="post" action="{{ route('doctor.visits.clinical.update', $record->id) }}">
            @csrf @method('PUT')
            <input type="hidden" name="form_key" value="clinical_note">
            <input type="hidden" name="record_version" value="{{ $noteForm ? old('record_version') : \App\Services\DoctorClinicalVersion::of($record->clinicalNote) }}">
            @foreach(['reason_for_visit' => 'Lý do đến khám', 'current_symptoms' => 'Triệu chứng hiện tại', 'present_illness_history' => 'Bệnh sử hiện tại', 'relevant_medical_history' => 'Tiền sử liên quan', 'physical_examination' => 'Khám thực thể', 'initial_assessment' => 'Đánh giá ban đầu', 'notes' => 'Ghi chú'] as $field => $label)
                <label for="clinical-{{ $field }}">{{ $label }}<textarea id="clinical-{{ $field }}" name="{{ $field }}" rows="2" maxlength="{{ $field === 'reason_for_visit' ? 2000 : 5000 }}">{{ $noteForm ? old($field) : $record->clinicalNote?->{$field} }}</textarea></label>
            @endforeach
            <button class="portal-btn portal-btn--primary" type="submit">Lưu nội dung khám</button>
        </form>
        @else
        @forelse(['Lý do đến khám' => $record->clinicalNote?->reason_for_visit, 'Triệu chứng' => $record->clinicalNote?->current_symptoms, 'Bệnh sử' => $record->clinicalNote?->present_illness_history, 'Tiền sử liên quan' => $record->clinicalNote?->relevant_medical_history, 'Khám thực thể' => $record->clinicalNote?->physical_examination, 'Đánh giá ban đầu' => $record->clinicalNote?->initial_assessment] as $label => $value)
            @if($value)<p><strong>{{ $label }}:</strong> {{ $value }}</p>@endif
        @empty @endforelse
        @unless($record->clinicalNote)<p>Chưa có nội dung khám.</p>@endunless
        <p>Chỉ có thể sửa nội dung khi lượt khám đang khám hoặc bệnh nhân đã quay lại sau dịch vụ.</p>
        @endif
    </section>
    <section class="tech-panel"><h2>Chẩn đoán</h2>
        @php($diagnosisTypes = ['PRIMARY' => 'Chẩn đoán chính', 'SECONDARY' => 'Chẩn đoán phụ', 'PRELIMINARY' => 'Chẩn đoán sơ bộ', 'CONFIRMED' => 'Chẩn đoán xác định'])
        @forelse($record->diagnoses as $diagnosis)
            <p><strong>{{ $diagnosis->disease?->name ?? 'Chưa cập nhật' }}</strong> · {{ $diagnosisTypes[$diagnosis->diagnosis_type] ?? $diagnosis->diagnosis_type }} · {{ $diagnosis->diagnosis_description ?: 'Chưa có mô tả' }}</p>
            @if($canEdit)
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
            @endif
        @empty<p>Chưa có chẩn đoán.</p>@endforelse
        @if($canEdit)
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
    <section class="tech-panel"><h2>Chỉ định và kết quả dịch vụ</h2>
        @if($record->canOrderServices())<a class="portal-btn portal-btn--primary" href="{{ route('doctor.visits.orders.create', $record->id) }}">Chỉ định dịch vụ</a>@endif
        @forelse($record->serviceOrders as $order)
            <h3>{{ $order->service_order_code }} · {{ $order->ordered_at?->format('d/m/Y H:i') ?? 'Chưa ghi thời gian' }}</h3>
            @if($order->reason)<p><strong>Lý do:</strong> {{ $order->reason }}</p>@endif
            @if($order->instructions)<p><strong>Hướng dẫn:</strong> {{ $order->instructions }}</p>@endif
            @foreach($order->items as $item)<p><strong>{{ $item->medicalService?->name ?? 'Dịch vụ' }}</strong> · {{ $item->serviceStatus?->description ?? 'Chưa cập nhật' }}</p>
                @unless($item->result?->hasContent())<p>Chưa có kết quả.</p>@endunless
                @if($item->result?->result_summary)<p>{{ $item->result->result_summary }}</p>@endif
                @if($item->result?->result_conclusion)<p>{{ $item->result->result_conclusion }}</p>@endif
                @if($item->result && ($rows = $item->result->parameterDisplayRows())) @foreach($rows as $row)<p><strong>{{ $row['label'] }}:</strong> {{ $row['value'] }}</p>@endforeach @endif
                @if($item->result && ($rows = $item->result->technicalDetailRows())) @foreach($rows as $row)<p><strong>{{ $row['label'] }}:</strong> {{ $row['value'] }}</p>@endforeach @endif
            @endforeach
        @empty<p>Chưa có chỉ định dịch vụ.</p>@endforelse
    </section>
    <section class="tech-panel"><h2>Đơn thuốc</h2>
        @if($record->prescription)
            <p><strong>{{ $record->prescription->prescription_code }}</strong> · {{ $record->prescription->prescribed_at?->format('d/m/Y H:i') ?? 'Chưa ghi thời gian' }}</p>
            <p>Trạng thái: {{ $record->prescription->status === 'ACTIVE' ? 'Đang hiệu lực' : ($record->prescription->status === 'CANCELLED' ? 'Đã hủy' : $record->prescription->status) }}</p>
            <a class="portal-btn portal-btn--secondary" href="{{ route('doctor.visits.prescriptions.print', $record->id) }}">Xem và in đơn thuốc</a>
            @foreach($record->prescription->items as $item)
                <p><strong>{{ $item->medicine?->name ?? 'Thuốc' }}</strong> · {{ $item->quantity }} {{ $item->usage_unit }}</p>
                <p>Liều mỗi lần: {{ $item->dose_per_use }} · {{ $item->times_per_day }} lần/ngày · {{ $item->duration_days }} ngày · {{ $item->usage_method }}</p>
                @if($item->administration_time)<p>Thời điểm dùng: {{ $item->administration_time }}</p>@endif
                @if($item->usage_instructions)<p>Hướng dẫn: {{ $item->usage_instructions }}</p>@endif
            @endforeach
            @if($record->prescription->general_instructions)<p><strong>Lời dặn:</strong> {{ $record->prescription->general_instructions }}</p>@endif
            @if($canEdit && $record->prescription->status === 'ACTIVE')
                @php($prescriptionForm = old('form_key') === 'prescription-update')
                <details @if($prescriptionForm) open @endif><summary>Chỉnh sửa lời dặn</summary>
                    <form class="tech-form" method="post" action="{{ route('doctor.visits.prescriptions.update', $record->id) }}">
                        @csrf @method('PUT')<input type="hidden" name="form_key" value="prescription-update"><input type="hidden" name="action" value="save">
                        <input type="hidden" name="record_version" value="{{ $prescriptionForm ? old('record_version') : \App\Services\DoctorClinicalVersion::of($record->prescription) }}">
                        <label>Lời dặn chung<textarea name="general_instructions" rows="3" maxlength="5000">{{ $prescriptionForm ? old('general_instructions') : $record->prescription->general_instructions }}</textarea></label>
                        <label>Ghi chú của bác sĩ<textarea name="doctor_notes" rows="2" maxlength="5000">{{ $prescriptionForm ? old('doctor_notes') : $record->prescription->doctor_notes }}</textarea></label>
                        <button class="portal-btn portal-btn--primary" type="submit">Lưu lời dặn</button>
                    </form>
                </details>
                <details><summary>Hủy đơn thuốc</summary><p>Chỉ có thể hủy khi không còn khoản thu đang hiệu lực. Hiện hệ thống chưa hỗ trợ tạo lại đơn khác cho cùng lượt khám sau khi hủy.</p>
                    <form method="post" action="{{ route('doctor.visits.prescriptions.update', $record->id) }}">@csrf @method('PUT')
                        <input type="hidden" name="action" value="cancel"><input type="hidden" name="record_version" value="{{ \App\Services\DoctorClinicalVersion::of($record->prescription) }}">
                        <button class="portal-btn portal-btn--secondary" type="submit">Xác nhận hủy đơn</button>
                    </form>
                </details>
            @endif
        @else
            <p>Chưa có đơn thuốc.</p>
            @if($canEdit)<a class="portal-btn portal-btn--primary" href="{{ route('doctor.visits.prescriptions.create', $record->id) }}">Kê đơn thuốc</a>@endif
        @endif
    </section>
    <section class="tech-panel"><h2>Kết luận khám</h2>
        @if($canEdit)
            @php($conclusionForm = old('form_key') === 'conclusion')
            @php($followUp = $conclusionForm ? old('has_follow_up', '0') : ($record->conclusion?->has_follow_up ? '1' : '0'))
            <form class="tech-form" method="post" action="{{ route('doctor.visits.conclusion.save', $record->id) }}">
                @csrf @method('PUT')<input type="hidden" name="form_key" value="conclusion">
                <input type="hidden" name="record_version" value="{{ $conclusionForm ? old('record_version') : \App\Services\DoctorClinicalVersion::of($record->conclusion) }}">
                <label for="professional-conclusion">Kết luận chuyên môn <span aria-hidden="true">*</span><textarea id="professional-conclusion" name="professional_conclusion" maxlength="10000" rows="3" required>{{ $conclusionForm ? old('professional_conclusion') : $record->conclusion?->professional_conclusion }}</textarea></label>
                <label for="conclusion-disposition">Hướng xử lý<input id="conclusion-disposition" name="disposition" maxlength="120" value="{{ $conclusionForm ? old('disposition') : $record->conclusion?->disposition }}"></label>
                <label for="treatment-plan">Kế hoạch điều trị<textarea id="treatment-plan" name="treatment_plan" maxlength="10000" rows="2">{{ $conclusionForm ? old('treatment_plan') : $record->conclusion?->treatment_plan }}</textarea></label>
                <label for="conclusion-instructions">Lời dặn<textarea id="conclusion-instructions" name="instructions" maxlength="10000" rows="2">{{ $conclusionForm ? old('instructions') : $record->conclusion?->instructions }}</textarea></label>
                <label for="has-follow-up">Hẹn tái khám<select id="has-follow-up" name="has_follow_up"><option value="0" @selected((string) $followUp === '0')>Không</option><option value="1" @selected((string) $followUp === '1')>Có</option></select></label>
                <label for="follow-up-date">Ngày tái khám nếu có<input id="follow-up-date" type="date" name="follow_up_date" value="{{ $conclusionForm ? old('follow_up_date') : $record->conclusion?->follow_up_date?->format('Y-m-d') }}"></label>
                <label for="follow-up-reason">Lý do tái khám nếu có<textarea id="follow-up-reason" name="follow_up_reason" maxlength="5000" rows="2">{{ $conclusionForm ? old('follow_up_reason') : $record->conclusion?->follow_up_reason }}</textarea></label>
                <label for="conclusion-notes">Ghi chú<textarea id="conclusion-notes" name="notes" maxlength="5000" rows="2">{{ $conclusionForm ? old('notes') : $record->conclusion?->notes }}</textarea></label>
                <button type="submit" class="portal-btn portal-btn--primary">Lưu kết luận</button>
            </form>
        @else<p>{{ $record->conclusion?->professional_conclusion ?? 'Chưa có kết luận.' }}</p>@endif
        @if($check['ready'])<p>Đã có đủ chẩn đoán, kết luận và không còn dịch vụ cần chờ.</p>@else<p>Hồ sơ còn thiếu:</p><ul>@foreach($check['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>@endif
    </section>
</section>
@endsection
