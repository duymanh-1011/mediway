@extends('admin.layouts.app')

@section('title', 'Thêm nội dung khám - Mediway Admin')

@section('breadcrumb')
    <div class="breadcrumb">
        Quản trị /
        Lượt khám /
        {{ $visit->visit_code }} /
        Thêm nội dung khám
    </div>
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
            class="btn btn-secondary"
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


    <form
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
                    {{ $message }}
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
                    {{ $message }}
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
                    {{ $message }}
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
                    {{ $message }}
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
                    {{ $message }}
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
                    {{ $message }}
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
                    {{ $message }}
                </div>
            @enderror
        </div>


        <div class="form-actions">
            <a
                href="{{ route('admin.visits.show', $visit) }}"
                class="btn btn-secondary"
            >
                Hủy
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Lưu nội dung khám
            </button>
        </div>
    </form>

@endsection


@push('styles')

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 20px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .section-title {
        margin-top: 0;
        margin-bottom: 15px;
    }

    .detail-row {
        display: grid;
        grid-template-columns:
            145px minmax(0, 1fr);
        gap: 15px;
        padding: 11px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-row span {
        color: #64748b;
    }

    .form-card {
        margin-top: 20px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        margin-bottom: 7px;
        font-weight: 700;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        resize: vertical;

        font-family:
            "Times New Roman",
            Times,
            serif;
    }

    .field-error {
        margin-top: 5px;
        color: #dc2626;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
    }

    @media (max-width: 800px) {
        .page-header {
            flex-direction: column;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@endpush
