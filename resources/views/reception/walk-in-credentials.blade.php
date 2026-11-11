@extends('employee.layout')

@section('title', ! empty($credentials['reissued']) ? 'Mật khẩu tạm của bệnh nhân' : 'Tài khoản bệnh nhân vừa tạo')

@section('content')
    @vite('resources/js/print-document.js')

    <section class="employee-card walk-in-credentials">
        <div class="walk-in-credentials__screen-only">
            <h1>@yield('title')</h1>
            @if (! empty($credentials['reissued']))
                <p>Tài khoản này gắn với hồ sơ bệnh nhân của lượt khám {{ $visit->visit_code }}.</p>
            @else
                <p>Lượt khám {{ $visit->visit_code }} đã được tiếp nhận và cấp số. Bệnh nhân cần thanh toán phí khám trước khi được gọi.</p>
            @endif
        </div>
        <h1 class="walk-in-credentials__print-only">Mediway · Thông tin đăng nhập</h1>
        <dl class="employee-facts">
            <div><dt>Số điện thoại đăng nhập</dt><dd>{{ $credentials['phone'] }}</dd></div>
            <div><dt>Mật khẩu tạm</dt><dd><code data-walk-in-password>{{ $credentials['password'] }}</code></dd></div>
        </dl>
        <p class="walk-in-credentials__print-only">Dùng số điện thoại và mật khẩu tạm để đăng nhập Mediway. Bạn cần đặt mật khẩu mới trong lần đăng nhập đầu tiên.</p>
        <div class="walk-in-credentials__screen-only">
            <p>Giao mật khẩu trực tiếp cho bệnh nhân. Mật khẩu chỉ hiện lần này. Bệnh nhân phải đặt mật khẩu mới khi đăng nhập lần đầu.</p>
            <div class="walk-in-credentials__actions">
                <button class="portal-btn portal-btn--secondary" type="button" data-print-document>In thông tin đăng nhập</button>
                <a class="portal-btn portal-btn--secondary" href="{{ route('employee.reception.slip', $visit) }}" target="_blank" rel="noopener">Xem phiếu khám</a>
                <a class="portal-btn portal-btn--primary" href="{{ route('employee.walk-in.show', $visit) }}">Xem chi tiết lượt khám</a>
            </div>
        </div>
    </section>
@endsection
