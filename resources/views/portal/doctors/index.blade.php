@extends('portal.layouts.app')

@section('title', 'Tra cứu bác sĩ và lịch khám - Mediway')

@section('content')
    <style>
        .doctor-search-page {
            --doctor-primary: #2563eb;
            --doctor-primary-dark: #1d4ed8;
            --doctor-primary-soft: #eff6ff;

            --doctor-text: #0f172a;
            --doctor-text-soft: #334155;
            --doctor-muted: #64748b;

            --doctor-border: #d9e4ee;
            --doctor-border-soft: #eaf1f6;

            --doctor-bg-hero: #f1f6fd;
            --doctor-bg-filter: #eef7fb;
            --doctor-bg-content: #ffffff;
            --doctor-bg-card: #ffffff;
            --doctor-bg-slot: #f8fbff;

            --doctor-chip-bg: #dff3fa;
            --doctor-chip-text: #0b6b8a;

            --doctor-danger: #b42318;

            --doctor-radius-lg: 18px;
            --doctor-radius-md: 14px;
            --doctor-radius-sm: 11px;

            --doctor-shadow:
                0 5px 18px rgba(22, 54, 81, 0.05);

            min-height: 100%;
            background: #ffffff;
        }

        /*
            |--------------------------------------------------------------------------
            | Tầng 1 - Tiêu đề
            |--------------------------------------------------------------------------
            */

        .doctor-search-page .portal-page-head {
            padding: 42px 0 38px;

            background: var(--doctor-bg-hero);

            border-bottom: 1px solid #e2ecf4;
        }

        .doctor-search-page .portal-page-head h1 {
            margin: 8px 0 10px;

            color: var(--doctor-text);

            font-size: clamp(32px, 4vw, 48px);
            font-weight: 800;
            line-height: 1.15;
        }

        .doctor-search-page .portal-lead {
            max-width: 760px;

            margin: 0;

            color: var(--doctor-muted);

            font-size: 15px;
            line-height: 1.7;
        }

        /*
            |--------------------------------------------------------------------------
            | Tầng 2 - Bộ lọc
            |--------------------------------------------------------------------------
            */

        .doctor-filter-section {
            padding: 36px 0;

            background: var(--doctor-bg-filter);

            border-bottom: 1px solid #e1edf3;
        }

        .doctor-filter-card {
            padding: 24px;

            background: var(--doctor-bg-card);

            border: 1px solid var(--doctor-border);
            border-radius: var(--doctor-radius-lg);

            box-shadow: var(--doctor-shadow);
        }

        .doctor-filter-card h2 {
            margin: 0 0 6px;

            color: var(--doctor-text);

            font-size: 24px;
            font-weight: 800;
            line-height: 1.3;
        }

        .doctor-filter-description {
            margin: 0 0 22px;

            color: var(--doctor-muted);

            font-size: 14px;
            line-height: 1.6;
        }

        .doctor-filter-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 16px;

            align-items: start;
        }

        .doctor-filter-field {
            display: grid;

            gap: 8px;
        }

        .doctor-filter-field label {
            margin: 0;

            color: var(--doctor-text-soft);

            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
        }

        .doctor-filter-field select,
        .doctor-filter-field input[type="date"] {
            width: 100%;
            height: 46px;

            box-sizing: border-box;

            padding: 0 14px;

            border: 1px solid #c9d6e2;
            border-radius: 12px;

            background: #ffffff;
            color: var(--doctor-text);

            font-size: 14px;

            outline: none;

            transition:
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }

        .doctor-filter-field select:hover,
        .doctor-filter-field input[type="date"]:hover {
            border-color: #aabccc;
        }

        .doctor-filter-field select:focus,
        .doctor-filter-field input[type="date"]:focus {
            border-color: var(--doctor-primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .doctor-filter-error {
            margin: 0;

            color: var(--doctor-danger);

            font-size: 13px;
            font-weight: 600;
            line-height: 1.45;
        }

        .doctor-filter-actions {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-top: 18px;

            flex-wrap: wrap;
        }

        /*
            |--------------------------------------------------------------------------
            | Buttons
            |--------------------------------------------------------------------------
            */

        .doctor-button {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 42px;

            box-sizing: border-box;

            padding: 0 17px;

            border: 1px solid transparent;
            border-radius: var(--doctor-radius-sm);

            font-size: 14px;
            font-weight: 700;
            line-height: 1;

            text-decoration: none;

            cursor: pointer;

            transition:
                background-color 0.15s ease,
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }

        .doctor-button--primary {
            background: var(--doctor-primary);
            color: #ffffff;
        }

        .doctor-button--primary:hover {
            background: var(--doctor-primary-dark);
        }

        .doctor-button--primary:focus {
            outline: none;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .doctor-button--secondary {
            background: #ffffff;
            color: var(--doctor-primary);

            border-color: #cbdcf8;
        }

        .doctor-button--secondary:hover {
            background: #f5f9ff;
        }

        /*
            |--------------------------------------------------------------------------
            | Tầng 3 - Danh sách bác sĩ
            |--------------------------------------------------------------------------
            */

        .doctor-results-section {
            min-height: 360px;

            padding: 36px 0 68px;

            background: var(--doctor-bg-content);
        }

        .doctor-results-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;

            align-items: stretch;
        }

        /*
            |--------------------------------------------------------------------------
            | Doctor Card
            |--------------------------------------------------------------------------
            */

        .doctor-card {
            display: flex;

            flex-direction: column;

            gap: 18px;

            height: 100%;

            box-sizing: border-box;

            padding: 20px;

            background: var(--doctor-bg-card);

            border: 1px solid var(--doctor-border);
            border-radius: var(--doctor-radius-lg);

            box-shadow: var(--doctor-shadow);
        }

        /*
            |--------------------------------------------------------------------------
            | Header bác sĩ
            |--------------------------------------------------------------------------
            */

        .doctor-card-top {
            display: grid;

            grid-template-columns:
                44px minmax(0, 1fr);

            gap: 14px;

            align-items: start;
        }

        .doctor-card-icon {
            display: grid;

            place-items: center;

            width: 44px;
            height: 44px;

            border-radius: 12px;

            background: var(--doctor-primary-soft);
            color: var(--doctor-primary);

            font-size: 22px;
            font-weight: 800;
            line-height: 1;
        }

        .doctor-card-head {
            min-width: 0;
        }

        .doctor-card-specialty {
            margin: 0 0 5px;

            color: var(--doctor-muted);

            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
        }

        .doctor-card-name {
            margin: 0;

            color: var(--doctor-text);

            font-size: 21px;
            font-weight: 800;
            line-height: 1.3;

            overflow-wrap: anywhere;
        }

        /*
            |--------------------------------------------------------------------------
            | Thông tin bác sĩ
            |--------------------------------------------------------------------------
            */

        .doctor-card-meta {
            display: grid;

            gap: 10px;
        }

        .doctor-card-row {
            display: grid;

            grid-template-columns:
                105px minmax(0, 1fr);

            gap: 10px;

            align-items: start;

            min-height: 22px;
        }

        .doctor-card-row span {
            color: var(--doctor-text-soft);

            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        .doctor-card-row strong {
            min-width: 0;

            color: var(--doctor-muted);

            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;

            overflow-wrap: anywhere;
        }

        /*
            |--------------------------------------------------------------------------
            | Khung giờ
            |--------------------------------------------------------------------------
            */

        .doctor-slot-box {
            margin-top: auto;

            padding: 14px;

            background: var(--doctor-bg-slot);

            border: 1px solid #d9e6f2;
            border-radius: var(--doctor-radius-md);
        }

        .doctor-slot-title {
            margin: 0 0 12px;

            color: var(--doctor-text-soft);

            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
        }

        .doctor-slot-list {
            display: grid;

            gap: 10px;
        }

        .doctor-slot-item {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 12px;

            padding: 10px 12px;

            background: #ffffff;

            border: 1px solid #e3edf6;
            border-radius: 12px;
        }

        .doctor-slot-left {
            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 0;

            flex-wrap: wrap;
        }

        .doctor-slot-time {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 34px;

            padding: 0 12px;

            border-radius: 999px;

            background: var(--doctor-chip-bg);
            color: var(--doctor-chip-text);

            font-size: 13px;
            font-weight: 800;
            line-height: 1;

            white-space: nowrap;
        }

        .doctor-slot-remaining {
            color: var(--doctor-muted);

            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;

            white-space: nowrap;
        }

        .doctor-slot-action {
            flex-shrink: 0;
        }

        /*
            |--------------------------------------------------------------------------
            | Empty
            |--------------------------------------------------------------------------
            */

        .doctor-empty {
            padding: 30px;

            background: #ffffff;

            border: 1px dashed #cbd8e6;
            border-radius: var(--doctor-radius-lg);

            text-align: center;
        }

        .doctor-empty h3 {
            margin: 0 0 8px;

            color: var(--doctor-text);

            font-size: 19px;
            font-weight: 800;
        }

        .doctor-empty p {
            margin: 0;

            color: var(--doctor-muted);

            font-size: 14px;
            line-height: 1.6;
        }

        /*
            |--------------------------------------------------------------------------
            | Responsive Tablet
            |--------------------------------------------------------------------------
            */

        @media (max-width: 992px) {
            .doctor-filter-grid {
                grid-template-columns: 1fr;
            }

            .doctor-results-grid {
                grid-template-columns: 1fr;
            }
        }

        /*
            |--------------------------------------------------------------------------
            | Responsive Mobile
            |--------------------------------------------------------------------------
            */

        @media (max-width: 640px) {
            .doctor-search-page .portal-page-head {
                padding: 30px 0 28px;
            }

            .doctor-search-page .portal-page-head h1 {
                font-size: 32px;
            }

            .doctor-filter-section {
                padding: 24px 0;
            }

            .doctor-results-section {
                padding: 28px 0 48px;
            }

            .doctor-filter-card,
            .doctor-card {
                padding: 16px;
            }

            .doctor-card-row {
                grid-template-columns: 1fr;

                gap: 2px;
            }

            .doctor-slot-item {
                align-items: stretch;

                flex-direction: column;
            }

            .doctor-slot-left {
                justify-content: space-between;
            }

            .doctor-slot-action,
            .doctor-slot-action .doctor-button {
                width: 100%;
            }

            .doctor-filter-actions {
                flex-direction: column;
            }

            .doctor-filter-actions .doctor-button {
                width: 100%;
            }
        }
    </style>

    <div class="doctor-search-page">

        {{-- ================================================================
        TẦNG 1 - HEADER
        ================================================================= --}}

        <section class="portal-page-head">
            <div class="portal-shell">

                <p class="portal-eyebrow">
                    ĐẶT LỊCH KHÁM
                </p>

                <h1>
                    Tra cứu bác sĩ và lịch khám
                </h1>

                <p class="portal-lead">
                    Tìm chuyên khoa, bác sĩ và các khung giờ đang còn nhận lịch.
                </p>

            </div>
        </section>

        {{-- ================================================================
        TẦNG 2 - BỘ LỌC
        ================================================================= --}}

        <section class="doctor-filter-section">
            <div class="portal-shell">

                <div class="doctor-filter-card">

                    <h2>
                        Bộ lọc
                    </h2>

                    <p class="doctor-filter-description">
                        Chỉ hiển thị bác sĩ, chuyên khoa và lịch khám đang hoạt động.
                    </p>

                    <form method="GET" action="{{ route('patient.doctors.index') }}">
                        <div class="doctor-filter-grid">

                            {{-- Chuyên khoa --}}
                            <div class="doctor-filter-field">

                                <label for="specialty_id">
                                    Chuyên khoa
                                </label>

                                <select id="specialty_id" name="specialty_id">
                                    <option value="">
                                        Tất cả chuyên khoa
                                    </option>

                                    @foreach ($specialties as $specialty)
                                        <option value="{{ $specialty->id }}" @selected(
                                            (string) $specialtyId
                                            ===
                                            (string) $specialty->id
                                        )>
                                            {{ $specialty->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('specialty_id')
                                    <p class="doctor-filter-error">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                            {{-- Bác sĩ --}}
                            <div class="doctor-filter-field">

                                <label for="doctor_id">
                                    Bác sĩ
                                </label>

                                <select id="doctor_id" name="doctor_id">
                                    <option value="">
                                        Tất cả bác sĩ
                                    </option>

                                    @foreach ($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" @selected(
                                            (string) $doctorId
                                            ===
                                            (string) $doctor->id
                                        )>
                                            {{ $doctor->full_name }}

                                            @if ($doctor->specialty)
                                                — {{ $doctor->specialty->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                                @error('doctor_id')
                                    <p class="doctor-filter-error">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                            {{-- Ngày khám --}}
                            <div class="doctor-filter-field">

                                <label for="date">
                                    Ngày khám
                                </label>

                                <input id="date" type="date" name="date" value="{{ $date }}"
                                    min="{{ today()->toDateString() }}" max="{{ today()
        ->addDays(
            \App\Models\Appointment::BOOKING_ADVANCE_DAYS
        )
        ->toDateString() }}">

                                @error('date')
                                    <p class="doctor-filter-error">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                        </div>

                        <div class="doctor-filter-actions">

                            <button type="submit" class="doctor-button doctor-button--primary">
                                Tra cứu
                            </button>

                            <a href="{{ route('patient.doctors.index') }}" class="doctor-button doctor-button--secondary">
                                Xóa bộ lọc
                            </a>

                        </div>
                    </form>

                </div>

            </div>
        </section>

        {{-- ================================================================
        TẦNG 3 - DANH SÁCH BÁC SĨ
        ================================================================= --}}

        <section class="doctor-results-section">
            <div class="portal-shell">

                @if ($schedules->isEmpty())

                    <div class="doctor-empty">

                        <h3>
                            Không tìm thấy lịch phù hợp
                        </h3>

                        <p>
                            Hiện chưa có lịch khám còn chỗ theo bộ lọc bạn đã chọn.
                        </p>

                    </div>

                @else

                    <div class="doctor-results-grid">

                        @foreach ($schedules as $schedule)

                                    <article class="doctor-card">

                                        {{-- Header bác sĩ --}}
                                        <div class="doctor-card-top">

                                            <div class="doctor-card-icon" aria-hidden="true">
                                                ✚
                                            </div>

                                            <div class="doctor-card-head">

                                                <p class="doctor-card-specialty">
                                                    {{ $schedule->specialty->name }}
                                                </p>

                                                <h3 class="doctor-card-name">
                                                    {{ $schedule->doctor->full_name }}
                                                </h3>

                                            </div>

                                        </div>

                                        {{-- Thông tin --}}
                                        <div class="doctor-card-meta">

                                            <div class="doctor-card-row">

                                                <span>
                                                    Ngày khám:
                                                </span>

                                                <strong>
                                                    {{ $schedule
                                ->work_date
                                ->format('d/m/Y') }}
                                                </strong>

                                            </div>

                                            <div class="doctor-card-row">

                                                <span>
                                                    Ca làm việc:
                                                </span>

                                                <strong>
                                                    {{ substr(
                                $schedule->start_time,
                                0,
                                5
                            ) }}
                                                    -
                                                    {{ substr(
                                $schedule->end_time,
                                0,
                                5
                            ) }}
                                                </strong>

                                            </div>

                                            <div class="doctor-card-row">

                                                <span>
                                                    Phòng khám:
                                                </span>

                                                <strong>
                                                    {{ $schedule
                                ->examinationRoom
                                ->name }}
                                                </strong>

                                            </div>

                                        </div>

                                        {{-- Slots --}}
                                        <div class="doctor-slot-box">

                                            <p class="doctor-slot-title">
                                                Khung giờ còn chỗ
                                            </p>

                                            <div class="doctor-slot-list">

                                                @foreach (
                                                                        $schedule->appointmentTimeSlots
                                                                        as $slot
                                                                    )

                                                                    @php
                                                                        $remaining = max(
                                                                            (int) $slot->max_patients
                                                                            -
                                                                            (int) $slot->booked_count,
                                                                            0
                                                                        );
                                                                    @endphp

                                                                    <div class="doctor-slot-item">

                                                                        <div class="doctor-slot-left">

                                                                            <span class="doctor-slot-time">
                                                                                {{ substr(
                                                        $slot->start_time,
                                                        0,
                                                        5
                                                    ) }}
                                                                                -
                                                                                {{ substr(
                                                        $slot->end_time,
                                                        0,
                                                        5
                                                    ) }}
                                                                            </span>

                                                                            <span class="doctor-slot-remaining">
                                                                                Còn {{ $remaining }} chỗ
                                                                            </span>

                                                                        </div>

                                                                        <div class="doctor-slot-action">

                                                                            <a href="{{ route(
                                                        'patient.appointments.create',
                                                        $slot
                                                    ) }}" class="
                                                                                                    doctor-button
                                                                                                    doctor-button--primary
                                                                                                ">
                                                                                Đặt lịch
                                                                            </a>

                                                                        </div>

                                                                    </div>

                                                @endforeach

                                            </div>

                                        </div>

                                    </article>

                        @endforeach

                    </div>

                @endif

            </div>
        </section>

    </div>
@endsection