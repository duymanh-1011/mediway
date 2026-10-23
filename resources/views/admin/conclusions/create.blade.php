@extends('admin.layouts.app')
@section('admin-page', 'conclusions-create')

@section('title', 'Thêm kết luận lượt khám - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Lượt khám</span>
    <span aria-hidden="true">/</span>
    <span>{{ $visit->visit_code }}</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm kết luận</strong>
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
                    {{ \App\Models\AdminDisplay::text($message) }}
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
                    {{ \App\Models\AdminDisplay::text($message) }}
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
                    {{ \App\Models\AdminDisplay::text($message) }}
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
                    {{ \App\Models\AdminDisplay::text($message) }}
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
                                {{ \App\Models\AdminDisplay::text($message) }}
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
                                {{ \App\Models\AdminDisplay::text($message) }}
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


@push('styles')



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
