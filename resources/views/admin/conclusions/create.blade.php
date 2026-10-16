@extends('admin.layouts.app')

@section('title', 'Thêm kết luận lượt khám - Mediway Admin')

@section('breadcrumb')
    <div class="breadcrumb">
        Quản trị /
        Lượt khám /
        {{ $visit->visit_code }} /
        Thêm kết luận
    </div>
@endsection

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">
                Thêm kết luận lượt khám
            </h1>

            <p class="page-description">
                Ghi nhận kết luận chuyên môn cho lượt khám
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
            'admin.visits.conclusion.store',
            $visit
        ) }}"
        class="card form-card"
    >
        @csrf

        <h3 class="section-title">
            Nội dung kết luận
        </h3>


        <div class="form-group">
            <label for="professional_conclusion">
                Kết luận chuyên môn
                <span class="required">*</span>
            </label>

            <textarea
                id="professional_conclusion"
                name="professional_conclusion"
                class="form-control"
                rows="5"
                required
            >{{ old('professional_conclusion') }}</textarea>

            @error('professional_conclusion')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="disposition">
                Hướng xử trí
            </label>

            <input
                id="disposition"
                name="disposition"
                type="text"
                class="form-control"
                list="disposition-list"
                value="{{ old('disposition') }}"
                placeholder="Ví dụ: ngoại trú, theo dõi, chuyển khoa, nhập viện..."
            >

            <datalist id="disposition-list">
                @foreach ($dispositionOptions as $disposition)
                    <option value="{{ $disposition }}"></option>
                @endforeach
            </datalist>

            @error('disposition')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="treatment_plan">
                Kế hoạch điều trị
            </label>

            <textarea
                id="treatment_plan"
                name="treatment_plan"
                class="form-control"
                rows="5"
            >{{ old('treatment_plan') }}</textarea>

            @error('treatment_plan')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>


        <div class="form-group">
            <label for="instructions">
                Dặn dò bệnh nhân
            </label>

            <textarea
                id="instructions"
                name="instructions"
                class="form-control"
                rows="4"
            >{{ old('instructions') }}</textarea>

            @error('instructions')
                <div class="field-error">
                    {{ $message }}
                </div>
            @enderror
        </div>


        <div class="follow-up-box">

            <label class="checkbox-row">
                <input
                    id="has_follow_up"
                    name="has_follow_up"
                    type="checkbox"
                    value="1"
                    @checked(old('has_follow_up'))
                >

                <span>
                    Có hẹn tái khám
                </span>
            </label>


            <div
                id="follow-up-fields"
                class="follow-up-fields"
            >
                <div class="form-grid">

                    <div class="form-group">
                        <label for="follow_up_date">
                            Ngày tái khám
                        </label>

                        <input
                            id="follow_up_date"
                            name="follow_up_date"
                            type="date"
                            class="form-control"
                            value="{{ old('follow_up_date') }}"
                        >

                        @error('follow_up_date')
                            <div class="field-error">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>


                    <div class="form-group">
                        <label for="follow_up_reason">
                            Lý do tái khám
                        </label>

                        <textarea
                            id="follow_up_reason"
                            name="follow_up_reason"
                            class="form-control"
                            rows="3"
                        >{{ old('follow_up_reason') }}</textarea>

                        @error('follow_up_reason')
                            <div class="field-error">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </div>
            </div>

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
                Lưu kết luận
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

    .follow-up-box {
        margin-bottom: 18px;
        padding: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .checkbox-row {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        cursor: pointer;
    }

    .follow-up-fields {
        display: none;
        margin-top: 15px;
    }

    .follow-up-fields.is-visible {
        display: block;
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox =
            document.getElementById('has_follow_up');

        const fields =
            document.getElementById('follow-up-fields');

        const toggleFollowUpFields = function () {
            if (checkbox.checked) {
                fields.classList.add('is-visible');
            } else {
                fields.classList.remove('is-visible');
            }
        };

        checkbox.addEventListener(
            'change',
            toggleFollowUpFields
        );

        toggleFollowUpFields();
    });
</script>

@endpush
