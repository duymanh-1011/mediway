@extends('portal.layouts.app')
@section('title', 'Hồ sơ sức khỏe - Mediway')
@section('content')
<section class="medical-page"><div class="portal-shell medical-shell">
    @include('portal.clinical._navigation')
    <header class="medical-hero"><div><p class="medical-hero__eyebrow">HỒ SƠ CỦA BẠN</p><h1>Hồ sơ sức khỏe</h1><p>Theo dõi các chỉ số sức khỏe được ghi nhận qua từng lần đo.</p></div><div class="medical-hero__symbol" aria-hidden="true">♡</div></header>
    <div class="medical-content patient-records">
        <div class="medical-section-head"><div><span class="medical-section-head__eyebrow">THEO DÕI SỨC KHỎE</span><h2>Lịch sử đo chỉ số sức khỏe</h2><p>Chọn một lần đo để xem các chỉ số chi tiết.</p></div><span class="medical-count">{{ $healthRecords->total() }} lần đo</span></div>
        <div class="patient-records__list">
        @forelse ($healthRecords as $record)
            <article class="patient-record health-record" data-health-record-id="{{ $record->id }}">
                <div class="patient-record__header"><div><span class="patient-record__label">LẦN ĐO SỨC KHỎE</span><h3>{{ $record->recorded_at?->format('d/m/Y') ?? 'Chưa cập nhật ngày' }}</h3></div><span class="patient-record__badge">Đã ghi nhận</span></div>
                <div class="patient-record__meta"><span>Thời gian ghi nhận <strong>{{ $record->recorded_at?->format('H:i · d/m/Y') ?? 'Chưa cập nhật' }}</strong></span></div>
                <details class="clinical-visit-details health-record__details"><summary>Xem chỉ số chi tiết <span aria-hidden="true">⌄</span></summary><div class="clinical-visit-details__body">
                    <section><h4>Chỉ số sức khỏe</h4><dl class="medical-metrics">
                        @if ($record->height_cm !== null)<div><dt>Chiều cao</dt><dd>{{ $record->height_cm }} <small>cm</small></dd></div>@endif
                        @if ($record->weight_kg !== null)<div><dt>Cân nặng</dt><dd>{{ $record->weight_kg }} <small>kg</small></dd></div>@endif
                        @if ($record->bmi !== null)<div><dt>BMI</dt><dd>{{ $record->bmi }}</dd></div>@endif
                        @if ($record->systolic_blood_pressure !== null || $record->diastolic_blood_pressure !== null)<div><dt>Huyết áp</dt><dd>{{ $record->systolic_blood_pressure ?? '—' }}/{{ $record->diastolic_blood_pressure ?? '—' }} <small>mmHg</small></dd></div>@endif
                        @if ($record->heart_rate !== null)<div><dt>Nhịp tim</dt><dd>{{ $record->heart_rate }} <small>lần/phút</small></dd></div>@endif
                        @if ($record->body_temperature !== null)<div><dt>Nhiệt độ</dt><dd>{{ $record->body_temperature }} <small>°C</small></dd></div>@endif
                        @if ($record->spo2 !== null)<div><dt>SpO₂</dt><dd>{{ $record->spo2 }} <small>%</small></dd></div>@endif
                        @if ($record->respiratory_rate !== null)<div><dt>Nhịp thở</dt><dd>{{ $record->respiratory_rate }} <small>lần/phút</small></dd></div>@endif
                        @if ($record->blood_glucose !== null)<div><dt>Đường huyết</dt><dd>{{ $record->blood_glucose }} <small>{{ \App\Models\PersonalHealthRecord::BLOOD_GLUCOSE_UNIT }}</small></dd></div>@endif
                    </dl></section>
                </div></details>
            </article>
        @empty
            <div class="patient-records__empty"><span aria-hidden="true">♡</span><h3>Chưa có lần đo sức khỏe</h3><p>Các chỉ số sẽ xuất hiện tại đây khi được ghi nhận trong hồ sơ của bạn.</p></div>
        @endforelse
        </div>
        <div class="medical-pagination">{{ $healthRecords->links() }}</div>
    </div>
</div></section>
@endsection
