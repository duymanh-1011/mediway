@extends('admin.layouts.app')
@section('admin-page', 'diagnoses-create')

@section('title', 'Thêm chẩn đoán - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Lượt khám</span>
    <span aria-hidden="true">/</span>
    <span>@include('admin.layouts.partials.record-code', ['code' => $visit->visit_code])</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm chẩn đoán</strong>
@endsection

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">
                Thêm chẩn đoán
            </h1>

            <p class="page-description">
                Ghi nhận chẩn đoán cho lượt khám
                @include('admin.layouts.partials.record-code', ['code' => $visit->visit_code]).
            </p>
        </div>

        <a
            href="{{ route('admin.visits.show', $visit) }}"
            class="admin-btn admin-btn-secondary"
        >← Quay lại</a>
    </div>


    <div class="detail-grid">
        <div class="card">
            <h3 class="section-title">
                Bệnh nhân
            </h3>

            <div class="detail-row">
                <span>Mã bệnh nhân</span>
                <strong>
                    @include('admin.layouts.partials.record-code', ['code' => $visit->patient?->patient_code ?? '—'])
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
                        name="disease_id" data-lookup="diseases" data-lookup-scope="active"
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
                        {{ \App\Models\AdminDisplay::text($message) }}
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
                        {{ \App\Models\AdminDisplay::text($message) }}
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
                    {{ \App\Models\AdminDisplay::text($message) }}
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
                class="admin-btn admin-btn-secondary"
            >Hủy</a>

            <button
                type="submit"
                class="admin-btn admin-btn-primary"
            >Lưu</button>
        </div>
    </form>

@endsection
