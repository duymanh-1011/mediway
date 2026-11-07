@extends('employee.layout')
@section('title', 'Tổng quan')
@section('content')
@php
    $employeeType = \App\Support\EmployeeAccess::typeCode($employee->detail->employee_type);
    $employeeTypeName = \App\Support\EmployeeAccess::TYPES[$employeeType];
    $groups = [
        'RECEPTIONIST' => ['Tiếp đón và lịch hẹn', 'Hỗ trợ tiếp nhận và theo dõi lịch hẹn.', 'employee.reception.index', 'reception'],
        'SERVICE_TECHNICIAN' => ['Thực hiện dịch vụ', 'Nhận chỉ định và ghi nhận kết quả kỹ thuật.', 'employee.services.index', 'services'],
        'CASHIER' => ['Thu ngân và thanh toán', 'Theo dõi khoản thu và giao dịch.', 'employee.payments.index', 'payment'],
    ];
    [$currentTitle, $currentDescription, $currentRoute, $currentIcon] = $groups[$employeeType];
    $currentAvailable = \Illuminate\Support\Facades\Route::has($currentRoute);
@endphp
<section class="employee-welcome">
    <div class="employee-welcome__copy">
        <p class="portal-eyebrow">KHU VỰC NHÂN VIÊN</p>
        <h1>Xin chào, {{ $employee->full_name }}</h1>
        <p>Chúc bạn một ngày làm việc hiệu quả tại Mediway.</p>
        <span class="employee-welcome__identity"><x-employee-icon name="shield" /> {{ $employeeTypeName }} <span aria-hidden="true">·</span> {{ $employee->employee_code }}</span>
    </div>
    <div class="employee-welcome__art" aria-hidden="true"><div class="employee-welcome__ring"></div><span><x-employee-icon name="reception" /></span><i></i></div>
</section>

<section class="employee-section" aria-labelledby="employee-overview-title">
    <div class="employee-section__heading"><div><p class="portal-eyebrow">KHÔNG GIAN LÀM VIỆC</p><h2 id="employee-overview-title">Tổng quan của bạn</h2><p>Thông tin tài khoản và công việc theo vị trí của bạn.</p></div></div>
    <div class="employee-grid">
        <section class="employee-card employee-overview-card">
            <div class="employee-card__heading"><span class="employee-card__icon"><x-employee-icon name="user" /></span><div><h3>Thông tin nhân viên</h3><p>Thông tin hồ sơ của bạn tại Mediway</p></div></div>
            <dl class="employee-facts"><div><dt>Mã nhân viên</dt><dd>{{ $employee->employee_code }}</dd></div><div><dt>Vị trí công việc</dt><dd>{{ $employeeTypeName }}</dd></div><div><dt>Trạng thái</dt><dd><span class="employee-status"><span aria-hidden="true"></span>Đang hoạt động</span></dd></div><div><dt>Bộ phận</dt><dd>{{ $employee->detail->department ?: 'Chưa cập nhật' }}</dd></div></dl>
            <a class="employee-card__link" href="{{ route('employee.profile') }}">Xem hồ sơ cá nhân <x-employee-icon name="arrow" /></a>
        </section>
        <section class="employee-card employee-overview-card">
            <div class="employee-card__heading"><span class="employee-card__icon"><x-employee-icon name="calendar" /></span><div><h3>Công việc của bạn</h3><p>Chức năng theo vị trí hiện tại</p></div></div>
            <div class="employee-work"><span class="employee-work__icon"><x-employee-icon :name="$currentIcon" /></span><div><h4>{{ $currentTitle }}</h4><p>{{ $currentDescription }}</p>@if($currentAvailable)<a class="employee-card__link" href="{{ route($currentRoute) }}">Mở chức năng <x-employee-icon name="arrow" /></a>@else<span class="employee-work__status">Chưa tích hợp</span>@endif</div></div>
            @if($work)<div class="employee-work__summary"><span>{{ $work['label'] }}</span><strong>{{ $work['count'] }}</strong></div>@endif
        </section>
    </div>
</section>

<section class="employee-section" aria-labelledby="employee-functions-title">
    <div class="employee-section__heading"><div><p class="portal-eyebrow">CHỨC NĂNG</p><h2 id="employee-functions-title">Các nhóm công việc</h2><p>Truy cập chức năng phù hợp với vị trí của bạn.</p></div></div>
    <div class="employee-services__grid">
        @foreach ($groups as $type => [$label, $description, $routeName, $icon])
            @php($available = $employeeType === $type && \Illuminate\Support\Facades\Route::has($routeName))
            @if($available)<a class="employee-service" href="{{ route($routeName) }}">@else<div class="employee-service">@endif
                <span class="employee-service__icon"><x-employee-icon :name="$icon" /></span><h3>{{ $label }}</h3><p>{{ $description }}</p><span class="employee-service__status">{{ $available ? 'Mở chức năng' : ($employeeType === $type ? 'Chưa tích hợp' : 'Không thuộc quyền của bạn') }} @if($available)<x-employee-icon name="arrow" />@endif</span>
            @if($available)</a>@else</div>@endif
        @endforeach
    </div>
</section>
@endsection
