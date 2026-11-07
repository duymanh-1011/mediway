@extends('portal.layouts.app')

@section('title', 'Kết quả khám bệnh - Mediway')

@section('content')
<section class="medical-page">
    <div class="portal-shell medical-shell">
        @include('portal.clinical._navigation')
        <header class="medical-hero">
            <div>
                <p class="medical-hero__eyebrow">HỒ SƠ CỦA BẠN</p>
                <h1>Kết quả khám bệnh</h1>
                <p>Chọn một lượt để xem kết quả, dịch vụ, giấy tờ, viện phí và đơn thuốc.</p>
            </div>
            <div class="medical-hero__symbol" aria-hidden="true">✳</div>
        </header>

        <div class="medical-content patient-records">
            <div class="medical-section-head">
                <div><h2>Các lượt khám của bạn</h2><p>{{ $patient->full_name }} · {{ $patient->patient_code }}</p></div>
                <span class="medical-count">{{ $visits->total() }} lượt khám</span>
            </div>
            <div class="patient-records__list">
                @forelse ($visits as $visit)
                    @php($endedAt = $visit->examination_ended_at ?? $recoveredEndTimes->get($visit->id))
                    <article class="patient-record portal-visit-list-card" data-visit-id="{{ $visit->id }}">
                        <div class="patient-record__header">
                            <div>
                                <span class="patient-record__label">{{ $visit->visit_code }}</span>
                                <h3>{{ $visit->specialty?->name ?? 'Lượt khám' }}</h3>
                            </div>
                            <span @class(['patient-record__badge', 'patient-record__badge--pending' => $visit->status?->name !== 'COMPLETED'])>{{ $visit->status?->description ?? 'Chưa cập nhật trạng thái' }}</span>
                        </div>
                        <dl class="portal-visit-facts">
                            <div><dt>Ngày tiếp nhận</dt><dd>{{ $visit->checked_in_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</dd></div>
                            <div><dt>Ngày kết thúc</dt><dd>{{ $endedAt?->format('d/m/Y H:i') ?? ($visit->status?->name === 'COMPLETED' ? 'Chưa ghi nhận thời điểm' : '—') }}</dd></div>
                            <div><dt>Bác sĩ</dt><dd>{{ $visit->doctor?->full_name ?? 'Chưa cập nhật' }}</dd></div>
                        </dl>
                        @if ($diagnosis = $visit->diagnoses->first())
                            <p class="portal-visit-lead"><strong>Chẩn đoán</strong> {{ $diagnosis->disease?->disease_code ? $diagnosis->disease->disease_code.' · ' : '' }}{{ $diagnosis->disease?->name ?: $diagnosis->diagnosis_description }}</p>
                        @endif
                        <div class="portal-visit-actions">
                            <x-portal.button :href="route('patient.results.visit', $visit->id)">Xem kết quả <span aria-hidden="true">→</span></x-portal.button>
                        </div>
                    </article>
                @empty
                    <x-portal.empty-state title="Chưa có lượt khám" description="Các lượt khám được ghi nhận sẽ xuất hiện tại đây." />
                @endforelse
            </div>
            {{ $visits->links() }}
        </div>
    </div>
</section>
@endsection
