@extends('employee.layout')
@section('title', 'Tiếp đón bệnh nhân')
@section('content')
<section class="employee-section reception-work">
    <div class="employee-section__heading"><div><p class="portal-eyebrow">KHU VỰC TIẾP ĐÓN</p><h1>Tiếp đón bệnh nhân</h1><p>Chọn công việc cần thực hiện tại quầy trong ngày {{ now()->format('d/m/Y') }}.</p></div></div>
    <div class="employee-grid">
        <section class="employee-card"><h2>Khám trực tiếp tại quầy</h2><p>Tìm hồ sơ hoặc tạo hồ sơ mới, sau đó tiếp nhận vào ca khám đang hoạt động.</p><a class="portal-btn portal-btn--primary" href="{{ route('employee.walk-in.create') }}">Tiếp nhận khám trực tiếp</a></section>
        <section class="employee-card"><h2>Lịch hẹn hôm nay</h2><p><strong>{{ $todayAppointments }}</strong> lịch hẹn · {{ $bookedAppointments }} lịch đang ở trạng thái đã đặt.</p><form method="post" action="{{ route('employee.appointments.filter') }}">@csrf<button class="portal-btn portal-btn--primary" type="submit" name="reset" value="1">Xem lịch hẹn và tiếp nhận</button></form></section>
    </div>
</section>
@endsection
