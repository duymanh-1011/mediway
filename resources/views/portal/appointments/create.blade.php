@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/appointments-create.css')
@endpush

@section('title', 'Xác nhận đặt lịch khám - Mediway')

@section('content')
    @php
        $patient->loadMissing(['user', 'detail']);

        $schedule = $slot->doctorSchedule;
        $detail = $patient->detail;
        $citizenId = trim((string) ($detail?->citizen_id_number ?? ''));
        $maskedCitizenId = $citizenId === ''
            ? 'Chưa cập nhật'
            : '••••••••' . (strlen($citizenId) > 4 ? substr($citizenId, -4) : '');

        $dateLabel = $schedule->work_date?->format('d/m/Y')
            ?? 'Chưa cập nhật';

        $clock = static fn($value) => $value
            ? substr((string) $value, 0, 5)
            : '--:--';

        $maskPhone = static function ($value): string {
            $digits = preg_replace(
                '/\D+/',
                '',
                (string) $value
            );

            return strlen($digits) >= 9
                ? substr($digits, 0, 3)
                . ' ••• •'
                . substr($digits, -3)
                : 'Chưa cập nhật';
        };

        $genderKey = mb_strtoupper(
            trim((string) $patient->gender),
            'UTF-8'
        );

        $genderLabel = match ($genderKey) {
            'NAM', 'MALE', 'M' => 'Nam',
            'NỮ', 'NU', 'FEMALE', 'F' => 'Nữ',
            'KHÁC', 'KHAC', 'OTHER' => 'Khác',
            default => 'Chưa cập nhật',
        };

        $address = trim(
            (string) ($detail?->current_address ?? '')
        );

        $address = $address !== ''
            ? $address
            : (
                trim(
                    (string) ($detail?->permanent_address ?? '')
                ) ?: 'Chưa cập nhật'
            );

        // Chi hien thi nhung thong tin ho so con thieu.
        $missingProfile = app(
            \App\Support\Portal\BookingProfileGate::class
        )->missingFields($patient);
    @endphp



    <div class="mw-book-page">
        <div class="mw-book-shell">

            <a
                class="mw-book-back"
                href="{{ route('patient.doctors.index') }}"
            >
                ← Chọn lịch khám khác
            </a>

            <article class="mw-book-sheet">

                {{-- =====================================
                     HEADER
                     ===================================== --}}

                <header class="mw-book-header">
                    <small>
                        MEDIWAY · ĐẶT LỊCH KHÁM
                    </small>

                    <h1>
                        Xác nhận lịch khám
                    </h1>

                    <p>
                        Kiểm tra thông tin người khám,
                        bác sĩ và thời gian trước khi xác nhận.
                    </p>
                </header>

                <div class="mw-book-body">

                    {{-- =====================================
                         01. THONG TIN BENH NHAN
                         ===================================== --}}

                    <section
                        class="mw-book-panel mw-book-panel--patient"
                        aria-labelledby="mw-patient-title"
                    >

                        <h2 id="mw-patient-title">
                            01. Thông tin bệnh nhân
                        </h2>

                        {{-- Chi hien thi thong tin con thieu --}}
                        @if ($missingProfile !== [])
                            <div
                                class="mw-book-profile-warning"
                                role="{{ $errors->has('profile') ? 'alert' : 'status' }}"
                                @if ($errors->has('profile')) tabindex="-1" data-profile-error @endif
                            >
                                <div class="mw-book-profile-warning__content">
                                    <span class="mw-book-profile-warning__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v4.5M12 17h.01"/></svg>
                                    </span>
                                    <div class="mw-book-profile-warning__text">
                                        <strong>{{ $errors->has('profile') ? 'Chưa thể xác nhận lịch khám' : 'Hồ sơ bệnh nhân chưa đầy đủ' }}</strong>
                                        <p>{{ $errors->has('profile') ? 'Hồ sơ còn thiếu thông tin bắt buộc. Vui lòng nhấn Bổ sung thông tin để cập nhật trước khi đặt lịch.' : 'Vui lòng hoàn thiện các thông tin dưới đây để tiếp tục đặt lịch khám.' }}</p>
                                        <ul>
                                            @foreach ($missingProfile as $field)
                                                <li>{{ $field }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <a class="mw-book-complete-profile" href="{{ route('patient.profile.edit', ['booking_slot' => $slot->id]) }}">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 10-4.5M15 18l4-4 2 2-4 4-2 .5V18Z"/></svg>
                                    <span>Bổ sung thông tin</span>
                                </a>
                            </div>
                        @endif

                        <dl class="mw-book-grid">

                            <div class="mw-book-field">
                                <dt>Họ và tên</dt>

                                <dd>
                                    {{
        $patient->full_name
        ?: 'Chưa cập nhật'
                                    }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Mã bệnh nhân</dt>

                                <dd>
                                    {{
        $patient->patient_code
        ?: 'Chưa cập nhật'
                                    }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Ngày sinh</dt>

                                <dd>
                                    {{
        $patient->date_of_birth
                ?->format('d/m/Y')
        ?? 'Chưa cập nhật'
                                    }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Giới tính</dt>

                                <dd>
                                    {{ $genderLabel }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Số điện thoại liên hệ</dt>

                                <dd>
                                    {{
        $maskPhone(
            $patient->user?->phone
        )
                                    }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Email (nếu có)</dt>

                                <dd>
                                    {{
        $detail?->email
        ?: 'Chưa cập nhật'
                                    }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Địa chỉ liên hệ</dt>

                                <dd>
                                    {{ $address }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Số CCCD</dt>
                                <dd>{{ $maskedCitizenId }}</dd>
                            </div>

                                                    <div class="mw-book-field">
                                                        <dt>Họ tên liên hệ khẩn cấp</dt>

                                                        <dd>
                                                            {{
                                $detail?->emergency_contact_name
                                ?: 'Chưa cập nhật'
                                                            }}
                                                        </dd>
                                                    </div>

                                                    <div class="mw-book-field">
                                                        <dt>Số điện thoại liên hệ khẩn cấp</dt>

                                                        <dd>
                                                            {{
                                $maskPhone(
                                    $detail?->emergency_contact_phone
                                )
                                                            }}
                                                        </dd>
                                                    </div>

                                                    @if (
                                                                            filled(
                                                                                $detail?->emergency_contact_relationship
                                                                            )
                                                                        )
                                                                            <div class="mw-book-field wide">
                                                                                <dt>Quan hệ với bệnh nhân</dt>

                                                                                <dd>
                                                                                    {{
                                                        $detail->emergency_contact_relationship
                                                                                    }}
                                                                                </dd>
                                                                            </div>
                                                    @endif


                        </dl>

                    </section>

                    {{-- =====================================
                         02. BAC SI VA LICH KHAM
                         ===================================== --}}

                    <section
                        class="mw-book-panel"
                        aria-labelledby="mw-doctor-title"
                    >

                        <h2 id="mw-doctor-title">
                            02. Bác sĩ và lịch khám
                        </h2>

                        <div class="mw-book-doctor">
                            BS.
                            {{
        $schedule->doctor?->full_name
        ?? 'Chưa cập nhật'
                            }}
                        </div>

                        <dl class="mw-book-grid">

                            <div class="mw-book-field">
                                <dt>Chuyên khoa</dt>

                                <dd>
                                    {{
        $schedule->specialty?->name
        ?? 'Chưa cập nhật'
                                    }}
                                </dd>
                            </div>

                            <div class="mw-book-field">
                                <dt>Phòng khám</dt>

                                <dd>
                                    {{
        $schedule->examinationRoom?->name
        ?? 'Chưa cập nhật'
                                    }}
                                </dd>
                            </div>

                        </dl>

                        <div class="mw-book-visit">

                            <div>
                                <small>Ngày khám</small>

                                <strong>
                                    {{ $dateLabel }}
                                </strong>
                            </div>

                            <div>
                                <small>Khung giờ</small>

                                <strong>
                                    {{ $clock($slot->start_time) }}
                                    –
                                    {{ $clock($slot->end_time) }}
                                </strong>
                            </div>

                        </div>

                        @error('appointment_time_slot_id')
                            <p
                                class="mw-book-error"
                                role="alert"
                            >
                                {{ $message }}
                            </p>
                        @enderror

                    </section>

                    {{-- =====================================
                         FORM XAC NHAN DAT LICH
                         ===================================== --}}

                    <form
                        id="mw-book-form"
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

                        {{-- =====================================
                             03. LY DO KHAM
                             ===================================== --}}

                        <section
                            class="mw-book-panel"
                            aria-labelledby="mw-reason-title"
                        >

                            <h2 id="mw-reason-title">
                                03. Lý do khám
                            </h2>

                            <label
                                class="mw-book-label"
                                for="reason_for_visit"
                            >
                                Lý do khám / Triệu chứng

                                <span class="mw-book-required">
                                    *
                                </span>
                            </label>

                            <textarea
                                id="reason_for_visit"
                                name="reason_for_visit"
                                rows="4"
                                class="mw-book-textarea"
                                maxlength="2000"
                                required
                                aria-invalid="{{ $errors->has('reason_for_visit') ? 'true' : 'false' }}"
                                aria-describedby="mw-reason-error mw-reason-help"
                                placeholder="Ví dụ: Đau họng, sốt từ ngày hôm qua..."
                            >{{ old('reason_for_visit') }}</textarea>

                            <p
                                id="mw-reason-error"
                                class="mw-book-error"
                                role="alert"
                                aria-live="polite"
                            >{{ $errors->first('reason_for_visit') }}</p>

                            <small
                                id="mw-reason-help"
                                class="mw-book-help"
                            >
                                Bắt buộc có chữ cái,
                                không chỉ nhập số hoặc ký tự đặc biệt.
                                Tối đa 2000 ký tự.
                            </small>

                            <label
                                class="mw-book-label"
                                for="patient_notes"
                            >
                                Ghi chú thêm (không bắt buộc)
                            </label>

                            <textarea
                                id="patient_notes"
                                name="patient_notes"
                                rows="3"
                                class="mw-book-textarea"
                                maxlength="2000"
                                aria-invalid="{{ $errors->has('patient_notes') ? 'true' : 'false' }}"
                                aria-describedby="mw-notes-error"
                                placeholder="Thông tin bổ sung cần gửi bệnh viện..."
                            >{{ old('patient_notes') }}</textarea>

                            <p
                                id="mw-notes-error"
                                class="mw-book-error"
                                role="alert"
                                aria-live="polite"
                            >{{ $errors->first('patient_notes') }}</p>

                        </section>

                        {{-- =====================================
                             04. XAC NHAN THONG TIN
                             ===================================== --}}

                        <section
                            id="mw-confirm-panel"
                            class="mw-book-panel mw-book-confirm {{ $errors->has('confirmed_information') ? 'has-error' : '' }}"
                            aria-labelledby="mw-confirm-title"
                        >

                            <h2 id="mw-confirm-title">
                                04. Xác nhận thông tin
                            </h2>

                            <label
                                class="mw-book-consent"
                                for="confirmed_information"
                            >

                                <input
                                    type="checkbox"
                                    id="confirmed_information"
                                    name="confirmed_information"
                                    value="1"
                                    required
                                    aria-invalid="{{ $errors->has('confirmed_information') ? 'true' : 'false' }}"
                                    aria-describedby="mw-confirm-error"
                                    @checked(old('confirmed_information'))
                                >

                                <span>
                                    Tôi đã kiểm tra thông tin bệnh nhân,
                                    bác sĩ, ngày giờ khám và lý do khám
                                    ở trên. Tôi xác nhận những thông tin
                                    mình cung cấp là chính xác.

                                    <span class="mw-book-required">
                                        *
                                    </span>
                                </span>

                            </label>

                            <p
                                id="mw-confirm-error"
                                class="mw-book-error"
                                role="alert"
                                aria-live="polite"
                            >{{ $errors->first('confirmed_information') }}</p>

                            <small class="mw-book-help">
                                Bạn phải tích chọn trước khi
                                xác nhận đặt lịch khám.
                            </small>

                        </section>

                        {{-- =====================================
                             CAC NUT CHUC NANG
                             ===================================== --}}

                        <div class="mw-book-actions">

                            <button
                                type="submit"
                                class="mw-book-button primary"
                            >
                                Xác nhận đặt lịch khám
                            </button>

                            <a
                                class="mw-book-button"
                                href="{{ route('patient.doctors.index') }}"
                            >
                                Chọn lịch khác
                            </a>

                        </div>

                    </form>

                </div>
            </article>

        </div>
    </div>

    <template data-mediway-page-script="appointments-create"></template>

@endsection
