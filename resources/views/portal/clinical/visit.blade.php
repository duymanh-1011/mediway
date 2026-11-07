@extends('portal.layouts.app')

@section('title', $tabs[$tab].' · '.$record->visit_code.' - Mediway')

@section('content')
<section class="medical-page portal-visit-page">
    <div class="portal-shell medical-shell">
        <a class="portal-back" href="{{ route('patient.results') }}">← Quay lại kết quả khám bệnh</a>
        <header class="medical-hero portal-visit-hero">
            <div>
                <p class="medical-hero__eyebrow">LƯỢT KHÁM · {{ $record->visit_code }}</p>
                <h1>{{ $record->specialty?->name ?? 'Chi tiết lượt khám' }}</h1>
                <p>{{ $patient->full_name }} · {{ $patient->patient_code }}</p>
            </div>
            <span @class(['patient-record__badge', 'patient-record__badge--pending' => $record->status?->name !== 'COMPLETED'])>{{ $record->status?->description ?? 'Chưa cập nhật trạng thái' }}</span>
        </header>
        <nav class="portal-visit-tabs" aria-label="Các mục của lượt khám">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('patient.results.visit', ['visit' => $record->id, 'tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="portal-visit-content">
            @include('portal.clinical.visit-'.$tab)
        </div>
    </div>
</section>
@endsection
