@extends('admin.layouts.app')
@section('admin-page', 'clinical-notes-create')

@section('title', 'Thêm nội dung khám - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Lượt khám</span>
    <span aria-hidden="true">/</span>
    <span>{{ $visit->visit_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm nội dung khám</strong>
@endsection

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">
                Thêm nội dung khám
            </h1>

            <p class="page-description">
                Ghi nhận thông tin lâm sàng cho lượt khám
                {{ $visit->visit_code }}.
            </p>
        </div>

        <a
            href="{{ route('admin.visits.show', $visit) }}"
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >
            ← Quay lại lượt khám
        </a>
    </div>


    <div class="detail-grid">

        <div class="card">
            <h3 class="section-title">
                Bệnh nhân
            </h3>

            <div class="detail-row">
                <span>Mã bệnh nhân</span>
                <strong>
                    {{ $visit->patient?->patient_code ?? '—' }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Họ tên</span>
                <strong>
                    {{ $visit->patient?->full_name ?? '—' }}
                </strong>
            </div>
        </div>


        <div class="card">
            <h3 class="section-title">
                Thông tin khám
            </h3>

            <div class="detail-row">
                <span>Bác sĩ</span>
                <strong>
                    {{ $visit->doctor?->full_name ?? '—' }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Chuyên khoa</span>
                <strong>
                    {{ $visit->specialty?->name ?? '—' }}
                </strong>
            </div>

            <div class="detail-row">
                <span>Phòng khám</span>
                <strong>
                    {{ $visit->examinationRoom?->name ?? '—' }}
                </strong>
            </div>
        </div>

    </div>


    <form novalidate
        method="POST"
        action="{{ route(
            'admin.visits.clinical-note.store',
            $visit
        ) }}"
        class="card form-card"
    >
        @csrf

        <h3 class="section-title">
            Nội dung lâm sàng
        </h3>


        <div class="form-group">
            <label for="reason_for_visit">
                Lý do khám
            </label>

            <textarea
                id="reason_for_visit"
                name="reason_for_visit"
                class="form-control"
                rows="3"
            >{{ old(
                'reason_for_visit',
                $visit->appointment?->reason_for_visit
            ) }}</textarea>

            @error('reason_for_visit')
                <div class="field-error">
                    {{ \App\Models\AdminDisplay::text($message) }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="current_symptoms">
                Triệu chứng hiện tại
            </label>

            <textarea
                id="current_symptoms"
                name="current_symptoms"
                class="form-control"
                rows="4"
            >{{ old('current_symptoms') }}</textarea>

            @error('current_symptoms')
                <div class="field-error">
                    {{ \App\Models\AdminDisplay::text($message) }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="present_illness_history">
                Bệnh sử hiện tại
            </label>

            <textarea
                id="present_illness_history"
                name="present_illness_history"
                class="form-control"
                rows="4"
            >{{ old('present_illness_history') }}</textarea>

            @error('present_illness_history')
                <div class="field-error">
                    {{ \App\Models\AdminDisplay::text($message) }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="relevant_medical_history">
                Tiền sử liên quan
            </label>

            <textarea
                id="relevant_medical_history"
                name="relevant_medical_history"
                class="form-control"
                rows="4"
            >{{ old('relevant_medical_history') }}</textarea>

            @error('relevant_medical_history')
                <div class="field-error">
                    {{ \App\Models\AdminDisplay::text($message) }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="physical_examination">
                Khám thực thể
            </label>

            <textarea
                id="physical_examination"
                name="physical_examination"
                class="form-control"
                rows="5"
            >{{ old('physical_examination') }}</textarea>

            @error('physical_examination')
                <div class="field-error">
                    {{ \App\Models\AdminDisplay::text($message) }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="initial_assessment">
                Nhận định ban đầu
            </label>

            <textarea
                id="initial_assessment"
                name="initial_assessment"
                class="form-control"
                rows="4"
            >{{ old('initial_assessment') }}</textarea>

            @error('initial_assessment')
                <div class="field-error">
                    {{ \App\Models\AdminDisplay::text($message) }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="notes">
                Ghi chú
            </label>

            <textarea
                id="notes"
                name="notes"
                class="form-control"
                rows="4"
            >{{ old('notes') }}</textarea>

            @error('notes')
                <div class="field-error">
                    {{ \App\Models\AdminDisplay::text($message) }}
                </div>
            @enderror
        </div>


        <div class="form-actions">
            <a
                href="{{ route('admin.visits.show', $visit) }}"
                class="btn btn-secondary"
            >Hủy</a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Lưu nội dung khám
            </button>
        </div>
    </form>

@endsection
