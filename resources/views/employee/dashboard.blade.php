@extends('employee.layout')
@section('title', 'Tổng quan')

@section('content')
    @php
        $reception = $workspace === 'reception';
        $position = $reception ? 'Nhân viên tiếp đón' : 'Nhân viên thu ngân';
        $work = $reception
            ? ['title' => 'Tiếp đón bệnh nhân', 'description' => 'Theo dõi lịch hẹn và các công việc tại quầy tiếp đón.', 'url' => route('employee.reception.index'), 'icon' => 'reception']
            : ['title' => 'Thu ngân và thanh toán', 'description' => 'Tra cứu khoản phải thu, ghi nhận thanh toán và in biên lai.', 'url' => route('employee.payments.index'), 'icon' => 'payment'];
    @endphp
    <x-workspace-overview
        :name="$employee->full_name" :position="$position" :code="$employee->employee_code" code-label="Mã nhân viên"
        profile-title="Thông tin nhân viên" :profile-url="route('employee.profile')"
        :facts="['Mã nhân viên' => $employee->employee_code, 'Vị trí công việc' => $position, 'Bộ phận' => $employee->detail?->department ?: 'Chưa cập nhật', 'Trạng thái phiên' => 'Đang đăng nhập']"
        :work="$work"
    />
@endsection
