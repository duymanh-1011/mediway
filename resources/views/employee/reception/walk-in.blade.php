@extends('employee.layout')
@section('title', 'Tiếp nhận khám trực tiếp')
@section('content')
<section class="employee-section reception-work">
    <a class="employee-card__link" href="{{ route('employee.reception.index') }}">← Quay lại tiếp đón</a>
    <div class="employee-section__heading"><div><p class="portal-eyebrow">TIẾP ĐÓN TẠI QUẦY</p><h1>Tiếp nhận khám trực tiếp</h1><p>Tìm hồ sơ trước khi tạo mới. Bệnh nhân thanh toán phí khám trước khi được cấp số thứ tự.</p></div></div>
    <section class="employee-card">
        <h2>1. Tìm hồ sơ bệnh nhân</h2>
        <p>Nhập chính xác mã bệnh nhân, số điện thoại đăng nhập hoặc số CCCD.</p>
        <form class="employee-filter" method="post" action="{{ route('employee.walk-in.search') }}">@csrf
            <div class="employee-filter__field"><label for="identifier">Thông tin tìm kiếm</label><input id="identifier" name="identifier" maxlength="30" autocomplete="off" required></div>
            <button type="submit" class="portal-btn portal-btn--secondary">Tìm hồ sơ</button>
        </form>
        @if(session('walkin_searched') && $patients->isEmpty())<p>Không tìm thấy hồ sơ đang hoạt động. Kiểm tra lại thông tin trước khi tạo mới.</p>@endif
    </section>
    <form class="employee-card portal-form" method="post" action="{{ route('employee.walk-in.store') }}">@csrf
        <input type="hidden" name="request_token" value="{{ old('request_token', (string) \Illuminate\Support\Str::uuid()) }}">
        <h2>2. Xác định bệnh nhân</h2>
        <label><input type="radio" name="patient_mode" value="existing" @checked(old('patient_mode', $patients->isNotEmpty() ? 'existing' : 'new') === 'existing')> Chọn hồ sơ đã có</label>
        <label for="patient_id">Hồ sơ tìm được</label>
        <select id="patient_id" name="patient_id"><option value="">Chọn hồ sơ từ kết quả tìm kiếm</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" @selected((string) old('patient_id') === (string) $patient->id)>{{ $patient->patient_code }} · {{ $patient->full_name }} · {{ $patient->date_of_birth?->format('d/m/Y') ?? 'Chưa có ngày sinh' }}</option>@endforeach</select>
        <label><input type="radio" name="patient_mode" value="new" @checked(old('patient_mode', $patients->isNotEmpty() ? 'existing' : 'new') === 'new')> Tạo hồ sơ bệnh nhân mới</label>
        <p>Chỉ điền các trường bên dưới khi tạo hồ sơ mới. Hồ sơ tạo tại quầy chưa có tài khoản đăng nhập. Nếu không có CCCD, hãy đối chiếu kỹ thông tin để tránh tạo trùng hồ sơ.</p>
        <label for="full_name">Họ và tên</label><input id="full_name" name="full_name" maxlength="120" value="{{ old('full_name') }}">
        <label for="date_of_birth">Ngày sinh</label><input id="date_of_birth" name="date_of_birth" type="date" max="{{ today()->toDateString() }}" value="{{ old('date_of_birth') }}">
        <label for="gender">Giới tính</label><select id="gender" name="gender"><option value="">Chọn giới tính</option>@foreach(['Nam','Nữ','Khác'] as $gender)<option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ $gender }}</option>@endforeach</select>
        <label for="citizen_id_number">CCCD (nếu có)</label><input id="citizen_id_number" name="citizen_id_number" inputmode="numeric" maxlength="12" value="{{ old('citizen_id_number') }}">
        <label><input type="checkbox" name="confirm_search" value="1" @checked(old('confirm_search'))> Tôi đã tìm hồ sơ trước khi tạo bệnh nhân mới.</label>
        <h2>3. Chọn ca khám</h2>
        <label for="schedule_id">Bác sĩ, chuyên khoa và phòng đang có ca làm việc <span aria-hidden="true">*</span></label>
        <select id="schedule_id" name="schedule_id" required><option value="">Chọn ca đang hoạt động</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}" @selected((string) old('schedule_id') === (string) $schedule->id)>{{ $schedule->doctor?->full_name ?? '—' }} · {{ $schedule->specialty?->name ?? '—' }} · {{ $schedule->examinationRoom?->name ?? '—' }} · {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</option>@endforeach</select>
        @if($schedules->isEmpty())<p>Hiện không có ca khám đang hoạt động. Vui lòng kiểm tra lại lịch làm việc.</p>@endif
        <label><input type="checkbox" name="confirm_reception" value="1" required @checked(old('confirm_reception'))> Tôi xác nhận đã kiểm tra đúng bệnh nhân và ca khám.</label>
        <button type="submit" class="portal-btn portal-btn--primary" data-pending-label="Đang tiếp nhận…" @disabled($schedules->isEmpty())>Xác nhận tiếp nhận</button>
    </form>
</section>
@endsection
