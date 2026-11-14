@extends($isAdmin ? 'admin.layouts.app' : 'employee.layout')
@section('title', 'Tiếp nhận khám trực tiếp')
@section('admin-page', 'visits-walk-in')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('admin.visits.index') }}">Lượt khám</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Tiếp nhận khám trực tiếp</strong>
@endsection

@section('content')
<section @class(['employee-section reception-work' => ! $isAdmin])>
    @if ($isAdmin)
        <div class="page-header">
            <div>
                <h1 class="page-title">Tiếp nhận khám trực tiếp</h1>
                <p class="page-description">Tìm hồ sơ trước khi tạo mới. Số khám được cấp khi tiếp nhận và bệnh nhân được gọi sau khi thanh toán phí khám.</p>
            </div>
            <a class="admin-btn admin-btn-secondary" href="{{ route('admin.visits.index') }}">Quay lại lượt khám</a>
        </div>
    @else
        <a class="employee-card__link" href="{{ route('employee.reception.index') }}">← Quay lại tiếp đón</a>
        <div class="employee-section__heading"><div><p class="portal-eyebrow">TIẾP ĐÓN TẠI QUẦY</p><h1>Tiếp nhận khám trực tiếp</h1><p>Tìm hồ sơ trước khi tạo mới. Số khám được cấp khi tiếp nhận và bệnh nhân được gọi sau khi thanh toán phí khám.</p></div></div>
    @endif

    @if ($unavailable ?? false)
        <div class="alert alert-error" role="alert">Không thể tải trang tiếp nhận lúc này. Vui lòng thử lại.</div>
    @else
@php
    $walkInRoute = $isAdmin ? 'admin.visits.walk-in' : 'employee.walk-in';
    $cardClass = $isAdmin ? 'admin-card' : 'employee-card';
    $primaryButtonClass = $isAdmin ? 'admin-btn admin-btn-primary' : 'portal-btn portal-btn--primary';
    $secondaryButtonClass = $isAdmin ? 'admin-btn admin-btn-secondary' : 'portal-btn portal-btn--secondary';
    $patientMode = old('patient_mode', $patients->isNotEmpty() ? 'existing' : 'new') === 'existing' ? 'existing' : 'new';
    $selectedPatientId = old('patient_id', $patients->count() === 1 ? $patients->first()->id : '');
    $selectedPatientId = is_scalar($selectedPatientId) ? (string) $selectedPatientId : '';
    $selectedPatient = $patientMode === 'existing' ? $patients->firstWhere('id', $selectedPatientId) : null;
    $needsAccount = $patientMode === 'new' || ($selectedPatient && $selectedPatient->user_id === null);
    $needsCitizenId = $needsAccount && blank($selectedPatient?->detail?->citizen_id_number);
    $phoneValue = $needsAccount && is_string(old('phone')) ? old('phone') : ($selectedPatient?->user?->phone ?? '');
    $genders = ['Nam', 'Nữ', 'Khác'];
    $profiles = $patients->mapWithKeys(function ($patient) use ($genders) {
        $gender = \App\Models\AdminDisplay::label($patient->gender);

        return [$patient->id => [
            'full_name' => $patient->full_name,
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'gender' => in_array($gender, $genders, true) ? $gender : '',
            'citizen_id_number' => $patient->detail?->citizen_id_number ?? '',
            'current_address' => $patient->detail?->current_address ?: $patient->detail?->permanent_address,
        ]];
    });
    $patientValue = function ($key) use ($patientMode, $selectedPatientId, $profiles) {
        $stored = $profiles[$selectedPatientId][$key] ?? '';
        $previous = old($key);
        if (is_string($previous) && (($patientMode === 'new' && old('patient_mode') === 'new')
            || ($patientMode === 'existing' && blank($stored)))) {
            return $previous;
        }
        return $patientMode === 'existing' ? $stored : '';
    };
    $rooms = $schedules->pluck('examinationRoom')->filter()->unique('id')->sortBy('name')->values();
    $specialties = $schedules->pluck('specialty')->filter()->unique('id')->sortBy('name')->values();
    $doctors = $schedules->pluck('doctor')->filter()->unique('id')->sortBy('full_name')->values();
    $scheduleOptions = $schedules->sortBy(fn ($schedule) => mb_strtolower(implode('|', [
        $schedule->examinationRoom?->name ?? '', $schedule->specialty?->name ?? '', $schedule->doctor?->full_name ?? '',
    ])))->values();
@endphp
    @push('styles')
    <style>
        .walk-in-picker-filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:14px 0 16px}
        .walk-in-picker-filters label{display:grid;gap:6px;color:#40536b;font-size:13px;font-weight:600}
        .walk-in-picker-filters select{width:100%;min-height:42px;padding:8px 11px;border:1px solid #cbd8e8;border-radius:8px;background:#fff;color:#17283e;font:inherit}
        .walk-in-room-options{display:grid;gap:10px}
        .walk-in-room-option{position:relative;display:grid;grid-template-columns:22px minmax(0,1fr);align-items:start;gap:12px;padding:15px 16px;border:1px solid #d8e3f0;border-radius:11px;background:#fff;cursor:pointer;transition:border-color .18s ease,background .18s ease,box-shadow .18s ease}
        .walk-in-room-option:hover{border-color:#9bbcf0;background:#f8fbff;box-shadow:0 4px 14px rgb(35 82 150 / 7%)}
        .walk-in-room-option.is-selected{border-color:#3476e5;background:#f1f6ff;box-shadow:0 0 0 2px rgb(52 118 229 / 12%)}
        .walk-in-room-option[hidden]{display:none!important}
        .walk-in-room-option input{width:18px;height:18px;margin:3px 0 0;accent-color:#2563eb}
        .walk-in-room-option__content{display:grid;gap:7px;min-width:0}
        .walk-in-room-option__heading{display:flex;align-items:center;justify-content:space-between;gap:12px}
        .walk-in-room-option__heading strong{color:#172b46;font-size:15px}
        .walk-in-room-option__load{flex:none;padding:4px 8px;border-radius:20px;background:#eef4ff;color:#2459b4;font-size:12px;font-weight:650}
        .walk-in-room-option__details{display:flex;flex-wrap:wrap;gap:5px 18px;color:#667993;font-size:13px}
        .walk-in-room-option__details strong{color:#344a64;font-weight:600}
        .walk-in-room-option__availability{color:#16815c;font-size:12px;font-weight:650}
        .walk-in-room-empty{margin:12px 0;padding:14px;border:1px dashed #cbd8e8;border-radius:9px;color:#62758d;text-align:center}
        @media(max-width:700px){.walk-in-picker-filters{grid-template-columns:1fr}.walk-in-room-option__heading{align-items:flex-start;flex-direction:column;gap:6px}}
    </style>
    @endpush
    <section class="{{ $cardClass }}">
        <h2>1. Tìm hồ sơ bệnh nhân</h2>
        <p id="identifier-help">Nhập chính xác mã bệnh nhân, số điện thoại đăng nhập hoặc số CCCD.</p>
        @php($identifier = old('identifier'))
        <form class="{{ $isAdmin ? 'flex flex-wrap items-end gap-3' : 'employee-filter' }}" method="post" action="{{ route($walkInRoute.'.search') }}">@csrf
            <div class="{{ $isAdmin ? 'min-w-0 flex-1 basis-60' : 'employee-filter__field' }}">
                <label for="identifier">Thông tin tìm kiếm</label>
                <input id="identifier" name="identifier" value="{{ is_string($identifier) ? $identifier : '' }}" maxlength="30" autocomplete="off" aria-describedby="identifier-help" required>
            </div>
            <button type="submit" class="{{ $secondaryButtonClass }}" data-pending-label="Đang tìm hồ sơ…">Tìm hồ sơ</button>
        </form>
        @if ($searched && $patients->isEmpty())
            <div class="{{ $isAdmin ? 'form-help' : 'portal-alert portal-alert--info portal-flash' }}" role="status">Không tìm thấy hồ sơ đang hoạt động. Kiểm tra lại mã bệnh nhân, số điện thoại hoặc CCCD.</div>
        @elseif ($searched)
            <div class="{{ $isAdmin ? 'alert alert-success mt-4' : 'portal-alert portal-alert--success portal-flash' }}" role="status">
                <strong>Tìm thấy {{ $patients->count() }} hồ sơ bệnh nhân</strong>
                <ul @class(['admin-alert-list list-disc' => $isAdmin])>
                    @foreach ($patients as $patient)
                        <li>{{ $patient->patient_code }} · {{ $patient->full_name }} · {{ $patient->date_of_birth?->format('d/m/Y') ?? 'Chưa có ngày sinh' }}</li>
                    @endforeach
                </ul>
                <a href="#patient_id" @class(['underline' => $isAdmin]) data-walk-in-choose>Chọn hồ sơ để tiếp nhận</a>
            </div>
            @if ($activation)
                <div class="{{ $isAdmin ? 'alert alert-success' : 'portal-alert portal-alert--success portal-flash' }}" role="status">
                    <p>Mã kích hoạt cho hồ sơ <strong>{{ $activation['patient_code'] }}</strong></p>
                    <p><code>{{ $activation['code'] }}</code></p>
                    <p>Giao mã trực tiếp cho bệnh nhân. Mã chỉ hiển thị lần này, có hiệu lực trong 24 giờ và dùng được một lần.</p>
                </div>
            @endif
            @foreach ($patients as $patient)
                @if ($patient->user_id === null && preg_match('/^[0-9]{12}$/', $patient->detail?->citizen_id_number ?? ''))
                    <form class="{{ $isAdmin ? 'grid gap-2 border-t pt-3 mt-3' : 'portal-form' }}" method="post" action="{{ route($walkInRoute.'.activation', $patient) }}">
                        @csrf
                        <label><input type="checkbox" name="confirm_identity" value="1" required> Tôi đã đối chiếu CCCD của bệnh nhân với hồ sơ {{ $patient->patient_code }}.</label>
                        <button type="submit" class="{{ $secondaryButtonClass }} {{ $isAdmin ? 'justify-self-start' : '' }}">Cấp mã kích hoạt tài khoản</button>
                    </form>
                @elseif (! $isAdmin && $patient->user?->must_change_password && preg_match('/^[0-9]{12}$/D', $patient->detail?->citizen_id_number ?? ''))
                    <form class="portal-form" method="post" action="{{ route('employee.walk-in.temporary-password', $patient) }}">
                        @csrf
                        <label><input type="checkbox" name="confirm_identity" value="1" required> Tôi đã đối chiếu CCCD của bệnh nhân với hồ sơ {{ $patient->patient_code }}.</label>
                        <button type="submit" class="portal-btn portal-btn--secondary">Cấp mật khẩu tạm</button>
                    </form>
                @endif
            @endforeach
        @endif
    </section>
    <form class="{{ $cardClass }} {{ $isAdmin ? 'grid min-w-0 gap-2' : 'portal-form' }}" method="post" action="{{ route($walkInRoute.'.store') }}" data-walk-in-form>@csrf
        <input type="hidden" name="request_token" value="{{ old('request_token', (string) \Illuminate\Support\Str::uuid()) }}">
        <h2>2. Xác định bệnh nhân</h2>
        <label><input type="radio" name="patient_mode" value="existing" @checked($patientMode === 'existing')> Chọn hồ sơ đã có</label>
        <label for="patient_id">Hồ sơ tìm được</label>
        <select id="patient_id" name="patient_id" data-lookup-native>
            <option value="">Chọn hồ sơ từ kết quả tìm kiếm</option>
            @foreach ($patients as $patient)
                @php($profile = $profiles[$patient->id])
                <option value="{{ $patient->id }}" @selected($selectedPatientId === (string) $patient->id)
                    data-full-name="{{ $profile['full_name'] }}" data-birth-date="{{ $profile['date_of_birth'] }}"
                    data-gender="{{ $profile['gender'] }}" data-citizen-id="{{ $profile['citizen_id_number'] }}"
                    data-has-account="{{ $patient->user_id !== null ? '1' : '0' }}" data-phone="{{ $patient->user?->phone }}"
                    data-current-address="{{ $profile['current_address'] }}"
                >{{ $patient->patient_code }} · {{ $patient->full_name }} · {{ $patient->date_of_birth?->format('d/m/Y') ?? 'Chưa có ngày sinh' }}</option>
            @endforeach
        </select>
        <label><input type="radio" name="patient_mode" value="new" @checked($patientMode === 'new')> Tạo hồ sơ bệnh nhân mới</label>
        <p>Hồ sơ chưa có tài khoản sẽ được cấp tài khoản bằng số điện thoại của bệnh nhân khi xác nhận tiếp nhận.</p>
        <label for="full_name">Họ và tên</label><input id="full_name" name="full_name" maxlength="120" data-patient-field="fullName" value="{{ $patientValue('full_name') }}" @required($patientMode === 'new') @readonly($patientMode === 'existing')>
        <label for="date_of_birth">Ngày sinh</label><input id="date_of_birth" name="date_of_birth" type="date" max="{{ today()->toDateString() }}" data-patient-field="birthDate" value="{{ $patientValue('date_of_birth') }}" @required($patientMode === 'new' || ($patientMode === 'existing' && blank($patientValue('date_of_birth')))) @readonly($patientMode === 'existing' && filled($patientValue('date_of_birth')))>
        <label for="gender">Giới tính</label><select id="gender" name="gender" data-patient-field="gender" @required($patientMode === 'new' || ($patientMode === 'existing' && blank($patientValue('gender')))) @disabled($patientMode === 'existing' && filled($patientValue('gender')))><option value="">Chọn giới tính</option>@foreach($genders as $gender)<option value="{{ $gender }}" @selected($patientValue('gender') === $gender)>{{ $gender }}</option>@endforeach</select>
        <label for="citizen_id_number">Số CCCD</label><input id="citizen_id_number" name="citizen_id_number" inputmode="numeric" maxlength="12" pattern="[0-9]{12}" data-patient-field="citizenId" value="{{ $needsCitizenId && is_string(old('citizen_id_number')) ? old('citizen_id_number') : $patientValue('citizen_id_number') }}" @required($needsCitizenId) @readonly(! $needsCitizenId)>
        <label for="phone">Số điện thoại</label><input id="phone" name="phone" type="tel" inputmode="numeric" maxlength="10" pattern="0[0-9]{9}" value="{{ $phoneValue }}" @required($needsAccount) @readonly(! $needsAccount)>
        <label for="current_address">Địa chỉ</label><input id="current_address" name="current_address" maxlength="255" data-patient-field="currentAddress" value="{{ $patientValue('current_address') }}" @readonly($patientMode === 'existing')>
        <label><input type="checkbox" name="confirm_search" value="1" @checked($patientMode === 'new' && old('confirm_search'))> Tôi đã tìm hồ sơ trước khi tạo bệnh nhân mới.</label>
        <h2>3. Chọn phòng khám</h2>
        <label for="reason_for_visit">Lý do khám</label><textarea id="reason_for_visit" name="reason_for_visit" maxlength="1000" rows="2">{{ is_string(old('reason_for_visit')) ? old('reason_for_visit') : '' }}</textarea>
        <p>Lọc các phòng đang có bác sĩ tiếp nhận. Số lượt trong ngày gồm bệnh nhân đang chờ khám, đang khám, chờ dịch vụ hoặc quay lại.</p>
        <div class="walk-in-picker-filters" aria-label="Bộ lọc phòng khám">
            <label for="walk-in-room-filter">Phòng khám
                <select id="walk-in-room-filter" data-room-filter="room"><option value="">Tất cả phòng khám</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->name }}</option>@endforeach</select>
            </label>
            <label for="walk-in-specialty-filter">Chuyên khoa
                <select id="walk-in-specialty-filter" data-room-filter="specialty"><option value="">Tất cả chuyên khoa</option>@foreach($specialties as $specialty)<option value="{{ $specialty->id }}">{{ $specialty->name }}</option>@endforeach</select>
            </label>
            <label for="walk-in-doctor-filter">Bác sĩ
                <select id="walk-in-doctor-filter" data-room-filter="doctor"><option value="">Tất cả bác sĩ</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>@endforeach</select>
            </label>
        </div>
        <div class="walk-in-room-options" data-room-options>
            @foreach($scheduleOptions as $schedule)
                @php($currentRoomLoad = (int) ($roomVisitCounts[$schedule->examination_room_id] ?? 0))
                <label class="walk-in-room-option {{ (string) old('schedule_id') === (string) $schedule->id ? 'is-selected' : '' }}"
                    data-room-option data-room-id="{{ $schedule->examination_room_id }}" data-specialty-id="{{ $schedule->specialty_id }}" data-doctor-id="{{ $schedule->doctor_id }}">
                    <input type="radio" name="schedule_id" value="{{ $schedule->id }}" required @checked((string) old('schedule_id') === (string) $schedule->id)>
                    <span class="walk-in-room-option__content">
                        <span class="walk-in-room-option__heading"><strong>{{ $schedule->examinationRoom?->name ?? 'Phòng khám' }}</strong><span class="walk-in-room-option__load">{{ $currentRoomLoad }} lượt đang xử lý</span></span>
                        <span class="walk-in-room-option__details"><span>Chuyên khoa: <strong>{{ $schedule->specialty?->name ?? '—' }}</strong></span><span>Bác sĩ: <strong>{{ $schedule->doctor?->full_name ?? '—' }}</strong></span></span>
                        <span class="walk-in-room-option__availability">Đang tiếp nhận bệnh nhân</span>
                    </span>
                </label>
            @endforeach
        </div>
        <p class="walk-in-room-empty" data-no-room-match hidden>Không tìm thấy phòng khám phù hợp với bộ lọc.</p>
        @if($schedules->isEmpty())<p>Hiện không có phòng khám nào đang tiếp nhận. Vui lòng kiểm tra lịch làm việc hiện tại.</p>@endif
        <label><input type="checkbox" name="confirm_reception" value="1" required @checked(old('confirm_reception'))> Tôi đã đối chiếu giấy tờ, phòng khám và số điện thoại của bệnh nhân nếu cấp tài khoản mới.</label>
        <button type="submit" class="{{ $primaryButtonClass }} {{ $isAdmin ? 'justify-self-start' : '' }}" data-walk-in-submit data-pending-label="Đang tiếp nhận…" @disabled($schedules->isEmpty())>Xác nhận tiếp nhận</button>
    </form>
    @endif
</section>
@endsection
