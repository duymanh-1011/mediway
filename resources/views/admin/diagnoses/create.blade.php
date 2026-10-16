@extends('admin.layouts.app')

@section('title', 'Thêm chẩn đoán - Mediway Admin')

@section('breadcrumb')
    <div class="breadcrumb">
        Quản trị /
        Lượt khám /
        {{ $visit->visit_code }} /
        Thêm chẩn đoán
    </div>
@endsection

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">
                Thêm chẩn đoán
            </h1>

            <p class="page-description">
                Ghi nhận chẩn đoán cho lượt khám
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
            'admin.visits.diagnoses.store',
            $visit
        ) }}"
        class="card form-card"
    >
        @csrf

        <h3 class="section-title">
            Thông tin chẩn đoán
        </h3>

        <div class="form-grid">
            <div class="form-group">
                <label for="disease_id">
                    Bệnh <span class="required">*</span>
                </label>

                <select
                    id="disease_id"
                    name="disease_id"
                    class="form-control"
                    required
                >
                    <option value="">
                        -- Chọn bệnh --
                    </option>

                    @foreach ($diseases as $disease)
                        <option
                            value="{{ $disease->id }}"
                            @selected(
                                (string) old('disease_id')
                                ===
                                (string) $disease->id
                            )
                        >
                            {{ $disease->disease_code }}
                            -
                            {{ $disease->name }}
                        </option>
                    @endforeach
                </select>

                @error('disease_id')
                    <div class="field-error">
                        {{ $message }}
                    </div>
                @enderror
            </div>


            <div class="form-group">
                <label for="diagnosis_type">
                    Loại chẩn đoán
                    <span class="required">*</span>
                </label>

                <select
                    id="diagnosis_type"
                    name="diagnosis_type"
                    class="form-control"
                    required
                >
                    <option value="">
                        -- Chọn loại chẩn đoán --
                    </option>

                    @foreach ($diagnosisTypes as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(
                                old('diagnosis_type')
                                ===
                                $value
                            )
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('diagnosis_type')
                    <div class="field-error">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>


        <div class="form-group">
            <label for="severity">
                Mức độ
            </label>

            <input
                id="severity"
                name="severity"
                type="text"
                class="form-control"
                list="severity-list"
                value="{{ old('severity') }}"
                placeholder="Chọn hoặc nhập mức độ"
            >

            <datalist id="severity-list">
                @foreach ($severityOptions as $severity)
                    <option value="{{ $severity }}"></option>
                @endforeach
            </datalist>

            @error('severity')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="diagnosis_description">
                Mô tả chẩn đoán
            </label>

            <textarea
                id="diagnosis_description"
                name="diagnosis_description"
                class="form-control"
                rows="5"
            >{{ old('diagnosis_description') }}</textarea>

            @error('diagnosis_description')
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
                Lưu chẩn đoán
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

    .form-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 18px;
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

    .required,
    .field-error {
        color: #dc2626;
    }

    .field-error {
        margin-top: 5px;
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

        .detail-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@endpush
