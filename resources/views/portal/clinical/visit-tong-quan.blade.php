<section class="patient-record portal-visit-panel" aria-labelledby="visit-information-title">
    <h2 id="visit-information-title">Thông tin lượt khám</h2>
    <dl class="portal-visit-facts">
        <div><dt>Ngày tiếp nhận</dt><dd>{{ $record->checked_in_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</dd></div>
        <div><dt>Bắt đầu khám</dt><dd>{{ $record->examination_started_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        <div><dt>Ngày kết thúc</dt><dd>{{ $endedAt?->format('d/m/Y H:i') ?? ($record->status?->name === 'COMPLETED' ? 'Chưa ghi nhận thời điểm' : '—') }}</dd></div>
        <div><dt>Bác sĩ</dt><dd>{{ $record->doctor?->full_name ?? 'Chưa cập nhật' }}</dd></div>
        <div><dt>Phòng khám</dt><dd>{{ $record->examinationRoom?->name ?? 'Chưa cập nhật' }}</dd></div>
        <div><dt>Ngày sinh</dt><dd>{{ $patient->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd></div>
    </dl>
    @if ($record->diagnoses->isNotEmpty())
        <div class="portal-visit-section">
            <h3>Chẩn đoán</h3>
            <ul>
                @foreach ($record->diagnoses as $diagnosis)
                    @php
                        $name = $diagnosis->disease?->name;
                    @endphp
                    <li>{{ $diagnosis->disease?->disease_code ? $diagnosis->disease->disease_code.' · ' : '' }}{{ $name ?: $diagnosis->diagnosis_description }}@if ($name && $diagnosis->diagnosis_description && mb_strtolower(trim($diagnosis->diagnosis_description)) !== mb_strtolower(trim($name))) — {{ $diagnosis->diagnosis_description }}@endif</li>
                @endforeach
            </ul>
        </div>
    @endif
</section>

<section class="patient-record portal-visit-panel" aria-labelledby="visit-progress-title">
    <h2 id="visit-progress-title">Tiến độ thực hiện</h2>
    @php
        $totals = $entries->countBy('status');
        $completed = $totals->get('COMPLETED', 0);
        $cancelled = $totals->get('CANCELLED', 0);
        $total = $entries->count() - $cancelled;
    @endphp
    @if ($total)
        <div class="portal-visit-line"><strong>Hoàn thành</strong><span>{{ $completed }}/{{ $total }}</span></div>
        <progress class="portal-visit-progress" max="{{ $total }}" value="{{ $completed }}" aria-label="Tiến độ hoàn thành"></progress>
    @endif
    <div class="portal-visit-counts">
        <span><strong>{{ $completed }}</strong> Hoàn thành</span>
        <span><strong>{{ $totals->get('PROCESSING', 0) }}</strong> Đang thực hiện</span>
        <span><strong>{{ $totals->get('PENDING', 0) }}</strong> Chờ thực hiện</span>
        @if ($cancelled)<span><strong>{{ $cancelled }}</strong> Đã hủy</span>@endif
    </div>
    <h3>Chi tiết theo nhóm dịch vụ</h3>
    <div class="portal-visit-rows">
        @forelse ($groups as $key => $group)
            @php($counts = $group->countBy('status'))
            <a class="portal-visit-row" href="{{ route('patient.results.visit', ['visit' => $record->id, 'tab' => 'dich-vu', 'nhom' => $key]) }}">
                <span><strong>{{ $group->first()['groupName'] }}</strong><small>{{ $counts->get('COMPLETED', 0) }} hoàn thành · {{ $counts->get('PROCESSING', 0) }} đang thực hiện · {{ $counts->get('PENDING', 0) }} chờ</small></span>
                <span class="medical-count">{{ $group->count() }} mục</span>
            </a>
        @empty
            <p>Chưa có dịch vụ được ghi nhận.</p>
        @endforelse
    </div>
</section>

<section class="patient-record portal-visit-panel" aria-labelledby="visit-conclusion-title" id="ket-luan">
    <h2 id="visit-conclusion-title">Kết luận khám</h2>
    @if ($record->conclusion)
        @foreach (['professional_conclusion' => 'Kết luận', 'disposition' => 'Hướng xử trí', 'treatment_plan' => 'Kế hoạch điều trị', 'instructions' => 'Hướng dẫn'] as $field => $label)
            @if ($record->conclusion->{$field})
                <div class="portal-visit-section">
                    <h3>{{ $label }}</h3>
                    <p>{{ $field === 'disposition' ? \App\Models\AdminDisplay::label($record->conclusion->{$field}) : $record->conclusion->{$field} }}</p>
                </div>
            @endif
        @endforeach
        @if ($record->conclusion->has_follow_up)
            <div class="portal-visit-section"><h3>Tái khám</h3><p>{{ $record->conclusion->follow_up_date?->format('d/m/Y') ?? 'Theo hướng dẫn của cơ sở y tế' }}@if ($record->conclusion->follow_up_reason) — {{ $record->conclusion->follow_up_reason }}@endif</p></div>
        @endif
    @else
        <p>Chưa có kết luận khám.</p>
    @endif
</section>
