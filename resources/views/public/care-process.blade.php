@extends('portal.layouts.app')
@section('title', 'Quy trình khám - Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell"><p class="portal-eyebrow">QUY TRÌNH KHÁM</p><h1>Chuẩn bị cho buổi khám từng bước</h1><p class="portal-lead">Từ tài khoản bệnh nhân đến lịch hẹn và thông tin sau khám, bạn có thể theo dõi các bước cần thiết tại đây.</p><div class="public-guide__actions"><x-portal.button :href="route('patient.doctors.index')">Tìm bác sĩ và đặt lịch</x-portal.button><x-portal.button variant="secondary" :href="route('public.support')">Câu hỏi thường gặp</x-portal.button></div></div></section>
<section class="public-guide"><div class="portal-shell">
    <div class="public-guide__intro"><p class="portal-eyebrow">HƯỚNG DẪN</p><h2>Hành trình khám của bạn</h2><p>Các bước bên dưới mô tả những chức năng đang có trên cổng bệnh nhân.</p></div>
    <div class="public-guide__grid">
        <article class="public-guide__card"><span class="public-guide__number">01</span><h3>Đăng nhập và kiểm tra hồ sơ</h3><p>Tạo tài khoản hoặc đăng nhập. Mở Hồ sơ cá nhân để kiểm tra các thông tin cần thiết trước khi xác nhận lịch hẹn.</p></article>
        <article class="public-guide__card"><span class="public-guide__number">02</span><h3>Chọn bác sĩ và lịch hẹn</h3><p>Tra cứu bác sĩ hoặc chuyên khoa, chọn khung giờ còn nhận lịch và xem lại thông tin trước khi xác nhận đặt lịch.</p></article>
        <article class="public-guide__card"><span class="public-guide__number">03</span><h3>Theo dõi và đến khám</h3><p>Mở Lịch khám để xem lịch sắp tới hoặc danh sách lịch hẹn. Kiểm tra ngày, giờ và phòng khám, chuẩn bị giấy tờ cần thiết và làm theo hướng dẫn tiếp nhận.</p></article>
        <article class="public-guide__card"><span class="public-guide__number">04</span><h3>Xem thông tin sau khám</h3><p>Khi dữ liệu tương ứng có trong tài khoản, bạn có thể xem hồ sơ sức khỏe, kết quả khám, đơn thuốc hoặc khoản cần thanh toán.</p></article>
    </div>
    <div class="public-guide__section"><div class="public-guide__note">Việc hủy hoặc đổi lịch phụ thuộc điều kiện của từng lịch hẹn. Mở chi tiết lịch hẹn để kiểm tra thao tác được phép.</div></div>
</div></section>
@endsection
