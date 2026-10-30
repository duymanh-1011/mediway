@extends('portal.layouts.app')
@section('title', 'Hồ sơ sức khỏe - Mediway')
@section('content')
<section class="medical-page"><div class="portal-shell medical-shell">
    {{-- Nút quay lại tài khoản được dùng chung cho các trang hồ sơ. --}}
    @include('portal.clinical._navigation')
    <header class="medical-hero"><div><p class="medical-hero__eyebrow">HỒ SƠ CỦA BẠN</p><h1>Hồ sơ sức khỏe</h1><p>Nhìn lại các lần thăm khám và theo dõi những chỉ số sức khỏe đã được ghi nhận.</p></div><div class="medical-hero__symbol" aria-hidden="true">♡</div></header>
    <div class="medical-content">
        <div class="medical-section-head"><div><span class="medical-section-head__eyebrow">QUÁ TRÌNH CHĂM SÓC</span><h2>Lịch sử khám</h2><p>Những lượt khám đã hoàn tất, mới nhất trước.</p></div><span class="medical-count">{{ $visits->total() }} lượt khám</span></div>
        {{-- Mỗi thẻ là một lượt khám; @empty xử lý trường hợp chưa có lịch sử. --}}
        <div class="medical-timeline">
        @forelse ($visits as $visit)
            <article class="medical-visit">
                <div class="medical-visit__date"><span>{{ $visit->checked_in_at?->format('d') ?? '—' }}</span><small>{{ $visit->checked_in_at?->format('m/Y') ?? 'Chưa có ngày' }}</small></div>
                <div class="medical-visit__body">
                    <div class="medical-visit__top"><div><span class="medical-kicker">{{ $visit->visit_code }}</span><h3>{{ $visit->specialty?->name ?? 'Lượt khám' }}</h3><p>{{ $visit->checked_in_at?->format('H:i · d/m/Y') ?? 'Chưa cập nhật thời gian' }} · {{ $visit->doctor?->full_name ?? 'Chưa cập nhật bác sĩ' }}</p></div><span class="medical-status">{{ $visit->status?->description ?: 'Hoàn tất' }}</span></div>
                    {{-- Chỉ hiển thị chẩn đoán và kết luận khi có dữ liệu. --}}
                    @if ($visit->diagnoses->isNotEmpty())<div class="medical-visit__detail"><h4>Chẩn đoán</h4><ul>@foreach ($visit->diagnoses as $diagnosis)<li>{{ $diagnosis->disease?->name ?? 'Chẩn đoán' }}@if ($diagnosis->diagnosis_description) — {{ $diagnosis->diagnosis_description }}@endif</li>@endforeach</ul></div>@endif
                    @if ($visit->conclusion)
                        @foreach (['professional_conclusion' => 'Kết luận', 'disposition' => 'Hướng xử trí', 'treatment_plan' => 'Kế hoạch điều trị', 'instructions' => 'Hướng dẫn'] as $field => $label)
                            @if ($visit->conclusion->{$field})<div class="medical-visit__detail"><h4>{{ $label }}</h4><p>{{ $visit->conclusion->{$field} }}</p></div>@endif
                        @endforeach
                        @if ($visit->conclusion->has_follow_up)<div class="medical-visit__detail"><h4>Tái khám</h4><p>{{ $visit->conclusion->follow_up_date?->format('d/m/Y') ?? 'Theo hướng dẫn của cơ sở y tế' }}@if ($visit->conclusion->follow_up_reason) — {{ $visit->conclusion->follow_up_reason }}@endif</p></div>@endif
                    @endif
                </div>
            </article>
        @empty
            <div class="medical-empty"><span aria-hidden="true">♡</span><div><h3>Chưa có lịch sử khám</h3><p>Các lượt khám hoàn tất sẽ xuất hiện tại đây.</p></div></div>
        @endforelse
        </div>
        <div class="medical-pagination">{{ $visits->links() }}</div>
        <div class="medical-section-head medical-section-head--spaced"><div><span class="medical-section-head__eyebrow">THEO DÕI SỨC KHỎE</span><h2>Chỉ số sức khỏe</h2><p>Các chỉ số được ghi nhận theo từng thời điểm.</p></div><span class="medical-count">{{ $healthRecords->total() }} bản ghi</span></div>
        {{-- Chỉ số được nhóm theo ngày ghi nhận và chỉ hiện trường có giá trị. --}}
        <div class="medical-measurements">
        @forelse ($healthRecords as $record)
            <article class="medical-measurement"><div class="medical-measurement__head"><div><span class="medical-kicker">CHỈ SỐ ĐƯỢC GHI NHẬN</span><h3>{{ $record->recorded_at?->format('d/m/Y') ?? 'Chưa cập nhật ngày' }}</h3></div><span>{{ $record->recorded_at?->format('H:i') ?? '' }}</span></div>
                <dl class="medical-metrics">
                    @if ($record->height_cm !== null)<div><dt>Chiều cao</dt><dd>{{ $record->height_cm }} <small>cm</small></dd></div>@endif
                    @if ($record->weight_kg !== null)<div><dt>Cân nặng</dt><dd>{{ $record->weight_kg }} <small>kg</small></dd></div>@endif
                    @if ($record->bmi !== null)<div><dt>BMI</dt><dd>{{ $record->bmi }}</dd></div>@endif
                    @if ($record->systolic_blood_pressure !== null || $record->diastolic_blood_pressure !== null)<div><dt>Huyết áp</dt><dd>{{ $record->systolic_blood_pressure ?? '—' }}/{{ $record->diastolic_blood_pressure ?? '—' }} <small>mmHg</small></dd></div>@endif
                    @if ($record->heart_rate !== null)<div><dt>Nhịp tim</dt><dd>{{ $record->heart_rate }} <small>lần/phút</small></dd></div>@endif
                    @if ($record->body_temperature !== null)<div><dt>Nhiệt độ</dt><dd>{{ $record->body_temperature }} <small>°C</small></dd></div>@endif
                    @if ($record->spo2 !== null)<div><dt>SpO₂</dt><dd>{{ $record->spo2 }} <small>%</small></dd></div>@endif
                    @if ($record->respiratory_rate !== null)<div><dt>Nhịp thở</dt><dd>{{ $record->respiratory_rate }} <small>lần/phút</small></dd></div>@endif
                    @if ($record->blood_glucose !== null)<div><dt>Đường huyết</dt><dd>{{ $record->blood_glucose }} <small>{{ \App\Models\PersonalHealthRecord::BLOOD_GLUCOSE_UNIT }}</small></dd></div>@endif
                </dl>
            </article>
        @empty
            <div class="medical-empty"><span aria-hidden="true">⌁</span><div><h3>Chưa có chỉ số sức khỏe</h3><p>Chỉ số sẽ xuất hiện khi được ghi nhận trong hồ sơ của bạn.</p></div></div>
        @endforelse
        </div>
        <div class="medical-pagination">{{ $healthRecords->links() }}</div>
    </div>
</div></section>
@endsection
