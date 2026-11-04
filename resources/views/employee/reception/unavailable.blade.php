@extends('employee.layout')
@section('title', 'Dữ liệu tạm thời không khả dụng')
@section('content')
<section class="employee-section"><div class="employee-card"><h1>Dữ liệu tạm thời không khả dụng</h1><p>Không thể tải thông tin. Vui lòng thử lại sau hoặc liên hệ hỗ trợ nếu lỗi tiếp tục.</p><a class="portal-btn portal-btn--secondary" href="{{ route($backRoute) }}">Thử lại</a></div></section>
@endsection
