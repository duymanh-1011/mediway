@extends('portal.layouts.app')

@section('title', 'Xác nhận đặt lịch - Mediway')

@section('content')
    @php
        $schedule = $slot->doctorSchedule;
    @endphp

    <div class="appointment-booking-page">

        <section class="portal-page-head appointment-booking-head">
            <div class="portal-shell">
                <p class="portal-eyebrow">
                    ĐẶT LỊCH KHÁM
                </p>

                <h1>
                    Xác nhận lịch khám
                </h1>

                <p class="portal-lead">
                    Kiểm tra thông tin lịch khám và nhập lý do trước khi xác nhận.
                </p>
            </div>
        </section>

        <section class="appointment-summary-section">
            <div class="portal-shell">

                @if ($errors->has('appointment_time_slot_id'))
                    <x-portal.alert type="error">
                        <strong>Không thể đặt lịch.</strong>

                        <div class="appointment-alert-message">
                            {{ $errors->first('appointment_time_slot_id') }}
                        </div>
                    </x-portal.alert>
                @elseif ($errors->has('auth'))
                    <x-portal.alert type="error">
                        <strong>Không thể đặt lịch.</strong>

                        <div class="appointment-alert-message">
                            {{ $errors->first('auth') }}
                        </div>
                    </x-portal.alert>
                @endif

                <div class="appointment-summary-grid">

                    {{-- Bệnh nhân --}}
                    <article class="appointment-summary-card">
                        <div class="appointment-summary-icon">
                            ♡
                        </div>

                        <div class="appointment-summary-content">
                            <p class="appointment-summary-label">
                                BỆNH NHÂN
                            </p>

                            <h2>
                                {{ $patient->full_name }}
                            </h2>

                            <div class="appointment-summary-list">
                                <div class="appointment-summary-row">
                                    <span>Mã bệnh nhân</span>
                                    <strong>
                                        {{ $patient->patient_code }}
                                    </strong>
                                </div>

                                <div class="appointment-summary-row">
                                    <span>Ngày sinh</span>
                                    <strong>
                                        {{ $patient->date_of_birth?->format('d/m/Y') ?? '—' }}
                                    </strong>
                                </div>

                                <div class="appointment-summary-row">
                                    <span>Giới tính</span>
                                    <strong>
                                        {{ $patient->gender ?? '—' }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </article>

                    {{-- Bác sĩ --}}
                    <article class="appointment-summary-card">
                        <div class="appointment-summary-icon">
                            ✚
                        </div>

                        <div class="appointment-summary-content">
                            <p class="appointment-summary-label">
                                BÁC SĨ
                            </p>

                            <h2>
                                {{ $schedule->doctor->full_name }}
                            </h2>

                            <div class="appointment-summary-list">
                                <div class="appointment-summary-row">
                                    <span>Chuyên khoa</span>
                                    <strong>
                                        {{ $schedule->specialty->name }}
                                    </strong>
                                </div>

                                <div class="appointment-summary-row">
                                    <span>Phòng khám</span>
                                    <strong>
                                        {{ $schedule->examinationRoom->name }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </article>

                    {{-- Thời gian --}}
                    <article class="appointment-summary-card">
                        <div class="appointment-summary-icon">
                            ◷
                        </div>

                        <div class="appointment-summary-content">
                            <p class="appointment-summary-label">
                                THỜI GIAN
                            </p>

                            <h2>
                                {{ $schedule->work_date->format('d/m/Y') }}
                            </h2>

                            <div class="appointment-summary-list">
                                <div class="appointment-summary-row">
                                    <span>Khung giờ</span>
                                    <strong>
                                        {{ substr($slot->start_time, 0, 5) }}
                                        -
                                        {{ substr($slot->end_time, 0, 5) }}
                                    </strong>
                                </div>

                                <div class="appointment-summary-row">
                                    <span>Ca làm việc</span>
                                    <strong>
                                        {{ substr($schedule->start_time, 0, 5) }}
                                        -
                                        {{ substr($schedule->end_time, 0, 5) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </article>

                </div>
            </div>
        </section>

        <section class="appointment-form-section">
            <div class="portal-shell">

                <div class="appointment-form-card">

                    <div class="appointment-form-head">
                        <div>
                            <p class="portal-eyebrow">
                                THÔNG TIN ĐẶT KHÁM
                            </p>

                            <h2>
                                Lý do khám
                            </h2>

                            <p>
                                Mô tả ngắn gọn tình trạng hoặc vấn đề sức khỏe bạn muốn được thăm khám.
                            </p>
                        </div>

                        <span class="appointment-required-note">
                            * Thông tin bắt buộc
                        </span>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('patient.appointments.store') }}"
                        novalidate
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="appointment_time_slot_id"
                            value="{{ $slot->id }}"
                        >

                        <div class="appointment-field">
                            <div class="appointment-label-row">
                                <label for="reason_for_visit">
                                    Lý do khám
                                    <span>*</span>
                                </label>

                                <small>
                                    Tối đa 2000 ký tự
                                </small>
                            </div>

                            <textarea
                                id="reason_for_visit"
                                name="reason_for_visit"
                                rows="5"
                                maxlength="2000"
                                aria-invalid="{{ $errors->has('reason_for_visit') ? 'true' : 'false' }}"
                                @if ($errors->has('reason_for_visit'))
                                    class="appointment-control appointment-control--error"
                                @else
                                    class="appointment-control"
                                @endif
                            >{{ old('reason_for_visit') }}</textarea>

                            @error('reason_for_visit')
                                <p class="appointment-field-error">
                                    <span aria-hidden="true">!</span>
                                    {{ $message }}
                                </p>
                            @enderror

                            <p class="appointment-field-help">
                                Ví dụ: Đau họng 2 ngày, ho nhiều về đêm.
                            </p>
                        </div>

                        <div class="appointment-field">
                            <div class="appointment-label-row">
                                <label for="patient_notes">
                                    Ghi chú thêm
                                </label>

                                <small>
                                    Không bắt buộc
                                </small>
                            </div>

                            <textarea
                                id="patient_notes"
                                name="patient_notes"
                                rows="4"
                                maxlength="2000"
                                aria-invalid="{{ $errors->has('patient_notes') ? 'true' : 'false' }}"
                                @if ($errors->has('patient_notes'))
                                    class="appointment-control appointment-control--error"
                                @else
                                    class="appointment-control"
                                @endif
                            >{{ old('patient_notes') }}</textarea>

                            @error('patient_notes')
                                <p class="appointment-field-error">
                                    <span aria-hidden="true">!</span>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="appointment-form-actions">
                            <x-portal.button
                                type="submit"
                                variant="primary"
                            >
                                Xác nhận đặt lịch
                            </x-portal.button>

                            <x-portal.button
                                :href="route('patient.doctors.index')"
                                variant="secondary"
                            >
                                Chọn lại lịch
                            </x-portal.button>
                        </div>
                    </form>

                </div>

            </div>
        </section>

    </div>

    <style>
    .appointment-booking-page {
        --appointment-primary: #087ea4;
        --appointment-primary-dark: #056785;
        --appointment-primary-soft: #eaf7fb;

        --appointment-text: #13253d;
        --appointment-text-soft: #334155;
        --appointment-muted: #64748b;

        --appointment-border: #d8e3ec;
        --appointment-border-soft: #eaf0f5;

        --appointment-bg: #ffffff;
        --appointment-section-bg: #eef7fb;

        --appointment-danger: #b42318;
        --appointment-danger-border: #ef4444;
        --appointment-danger-bg: #fff7f6;

        --appointment-radius-lg: 18px;
        --appointment-radius-md: 13px;

        --appointment-shadow:
            0 5px 18px rgba(22, 54, 81, 0.05);
    }

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    .appointment-booking-page .portal-page-head {
        padding-top: 40px;
        padding-bottom: 34px;
    }

    .appointment-booking-page .portal-page-head h1 {
        margin-top: 6px;
        margin-bottom: 10px;
        color: var(--appointment-text);
    }

    .appointment-booking-page .portal-lead {
        max-width: 720px;
        margin-bottom: 0;
        color: var(--appointment-muted);
        line-height: 1.6;
    }

    /*
    |--------------------------------------------------------------------------
    | Khu thông tin bệnh nhân / bác sĩ / thời gian
    |--------------------------------------------------------------------------
    */

    .appointment-summary-section {
        padding: 34px 0 38px;
        background: var(--appointment-section-bg);
        border-top: 1px solid #e2edf3;
        border-bottom: 1px solid #e2edf3;
    }

    .appointment-alert-message {
        margin-top: 5px;
    }

    .appointment-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        align-items: stretch;
    }

    .appointment-summary-card {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr);
        gap: 15px;

        height: 100%;
        min-height: 220px;

        padding: 22px;

        border: 1px solid var(--appointment-border);
        border-radius: var(--appointment-radius-lg);

        background: var(--appointment-bg);

        box-shadow:
            0 3px 12px rgba(22, 54, 81, 0.04);
    }

    /*
    |--------------------------------------------------------------------------
    | Icon
    |--------------------------------------------------------------------------
    */

    .appointment-summary-icon {
        display: grid;
        place-items: center;

        width: 44px;
        height: 44px;

        flex: 0 0 44px;

        border-radius: 13px;

        background: var(--appointment-primary-soft);
        color: var(--appointment-primary);

        font-size: 22px;
        font-weight: 800;
        line-height: 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Nội dung card
    |--------------------------------------------------------------------------
    */

    .appointment-summary-content {
        display: grid;
        grid-template-rows:
            20px
            38px
            1fr;

        min-width: 0;
    }

    .appointment-summary-label {
        margin: 0;

        color: var(--appointment-primary);

        font-size: 12px;
        font-weight: 800;
        line-height: 20px;

        letter-spacing: 0.08em;
    }

    .appointment-summary-card h2 {
        display: flex;
        align-items: center;

        min-width: 0;

        margin: 0;

        color: var(--appointment-text);

        font-size: 18px;
        font-weight: 800;
        line-height: 1.3;

        overflow-wrap: anywhere;
    }

    /*
    |--------------------------------------------------------------------------
    | Danh sách thông tin trong card
    |--------------------------------------------------------------------------
    */

    .appointment-summary-list {
        display: grid;
        align-content: start;
        gap: 11px;

        padding-top: 12px;
    }

    .appointment-summary-row {
        display: grid;

        grid-template-columns:
            105px
            minmax(0, 1fr);

        gap: 10px;

        min-height: 22px;

        align-items: start;

        font-size: 14px;
        line-height: 1.5;
    }

    .appointment-summary-row span {
        color: var(--appointment-muted);

        font-weight: 500;

        white-space: nowrap;
    }

    .appointment-summary-row strong {
        min-width: 0;

        color: var(--appointment-text-soft);

        font-weight: 650;

        overflow-wrap: anywhere;
    }

    /*
    |--------------------------------------------------------------------------
    | Khu form
    |--------------------------------------------------------------------------
    */

    .appointment-form-section {
        padding: 42px 0 68px;
        background: #ffffff;
    }

    .appointment-form-card {
        width: 100%;
        max-width: none;

        margin: 0;

        padding: 28px;

        border: 1px solid var(--appointment-border);
        border-radius: var(--appointment-radius-lg);

        background: var(--appointment-bg);

        box-shadow: var(--appointment-shadow);
    }

    /*
    |--------------------------------------------------------------------------
    | Header form
    |--------------------------------------------------------------------------
    */

    .appointment-form-head {
        display: flex;

        align-items: flex-start;
        justify-content: space-between;

        gap: 24px;

        margin-bottom: 24px;
        padding-bottom: 20px;

        border-bottom: 1px solid var(--appointment-border-soft);
    }

    .appointment-form-head > div {
        min-width: 0;
    }

    .appointment-form-head .portal-eyebrow {
        margin-bottom: 6px;
    }

    .appointment-form-head h2 {
        margin: 0 0 7px;

        color: var(--appointment-text);

        font-size: 23px;
        font-weight: 800;
        line-height: 1.3;
    }

    .appointment-form-head p:not(.portal-eyebrow) {
        max-width: 700px;

        margin: 0;

        color: var(--appointment-muted);

        font-size: 14px;
        line-height: 1.6;
    }

    .appointment-required-note {
        flex-shrink: 0;

        margin-top: 2px;

        color: var(--appointment-danger);

        font-size: 13px;
        font-weight: 700;
    }

    /*
    |--------------------------------------------------------------------------
    | Field
    |--------------------------------------------------------------------------
    */

    .appointment-field {
        margin-bottom: 22px;
    }

    .appointment-field:last-of-type {
        margin-bottom: 0;
    }

    .appointment-label-row {
        display: flex;

        align-items: center;
        justify-content: space-between;

        gap: 12px;

        margin-bottom: 8px;
    }

    .appointment-label-row label {
        margin: 0;

        color: var(--appointment-text);

        font-size: 14px;
        font-weight: 750;
        line-height: 1.4;
    }

    .appointment-label-row label span {
        color: var(--appointment-danger);
    }

    .appointment-label-row small {
        color: var(--appointment-muted);

        font-size: 12px;
        font-weight: 500;

        white-space: nowrap;
    }

    /*
    |--------------------------------------------------------------------------
    | Textarea
    |--------------------------------------------------------------------------
    */

    .appointment-control {
        display: block;

        width: 100%;
        min-height: 112px;

        box-sizing: border-box;

        margin: 0;
        padding: 12px 14px;

        resize: vertical;

        border: 1px solid #b8c7d5;
        border-radius: 11px;

        background: #ffffff;
        color: var(--appointment-text);

        font: inherit;
        font-size: 14px;
        line-height: 1.55;

        outline: none;

        transition:
            border-color 0.15s ease,
            box-shadow 0.15s ease,
            background-color 0.15s ease;
    }

    .appointment-control:hover {
        border-color: #94a8ba;
    }

    .appointment-control:focus {
        border-color: var(--appointment-primary);

        box-shadow:
            0 0 0 3px rgba(8, 126, 164, 0.13);
    }

    /*
    |--------------------------------------------------------------------------
    | Validate lỗi
    |--------------------------------------------------------------------------
    */

    .appointment-control--error {
        border-color: var(--appointment-danger-border);
        background: var(--appointment-danger-bg);
    }

    .appointment-control--error:hover,
    .appointment-control--error:focus {
        border-color: var(--appointment-danger-border);

        box-shadow:
            0 0 0 3px rgba(180, 35, 24, 0.11);
    }

    .appointment-field-error {
        display: flex;

        align-items: flex-start;

        gap: 7px;

        margin: 7px 0 0;

        color: var(--appointment-danger);

        font-size: 13px;
        font-weight: 650;
        line-height: 1.45;
    }

    .appointment-field-error span {
        display: inline-grid;
        place-items: center;

        width: 17px;
        height: 17px;

        flex: 0 0 17px;

        margin-top: 1px;

        border-radius: 50%;

        background: var(--appointment-danger);
        color: #ffffff;

        font-size: 11px;
        font-weight: 800;
        line-height: 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Gợi ý dưới field
    |--------------------------------------------------------------------------
    */

    .appointment-field-help {
        margin: 7px 0 0;

        color: var(--appointment-muted);

        font-size: 12px;
        line-height: 1.45;
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */

    .appointment-form-actions {
        display: flex;

        align-items: center;

        gap: 10px;

        margin-top: 28px;
        padding-top: 20px;

        border-top: 1px solid var(--appointment-border-soft);
    }

    /*
    |--------------------------------------------------------------------------
    | Tablet
    |--------------------------------------------------------------------------
    */

    @media (max-width: 1024px) {
        .appointment-summary-grid {
            grid-template-columns: 1fr;
        }

        .appointment-summary-card {
            min-height: auto;
        }

        .appointment-form-card {
            padding: 24px;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mobile
    |--------------------------------------------------------------------------
    */

    @media (max-width: 640px) {
        .appointment-booking-page .portal-page-head {
            padding-top: 28px;
            padding-bottom: 26px;
        }

        .appointment-summary-section {
            padding: 24px 0 28px;
        }

        .appointment-summary-grid {
            gap: 14px;
        }

        .appointment-summary-card {
            grid-template-columns:
                40px
                minmax(0, 1fr);

            gap: 12px;

            padding: 18px;
        }

        .appointment-summary-icon {
            width: 40px;
            height: 40px;

            font-size: 20px;
        }

        .appointment-summary-content {
            grid-template-rows:
                20px
                auto
                1fr;
        }

        .appointment-summary-card h2 {
            min-height: 34px;

            margin-bottom: 4px;

            font-size: 17px;
        }

        .appointment-summary-row {
            grid-template-columns: 1fr;

            gap: 2px;
        }

        .appointment-summary-row span {
            white-space: normal;
        }

        .appointment-form-section {
            padding: 30px 0 48px;
        }

        .appointment-form-card {
            padding: 20px;
        }

        .appointment-form-head {
            display: block;

            margin-bottom: 20px;
            padding-bottom: 18px;
        }

        .appointment-required-note {
            display: inline-block;

            margin-top: 12px;
        }

        .appointment-label-row {
            align-items: flex-start;

            flex-direction: column;

            gap: 4px;
        }

        .appointment-label-row small {
            white-space: normal;
        }

        .appointment-control {
            min-height: 105px;
        }

        .appointment-form-actions {
            align-items: stretch;

            flex-direction: column;
        }

        .appointment-form-actions > * {
            width: 100%;
        }

        .appointment-form-actions .portal-btn {
            width: 100%;
        }
    }
</style>
@endsection