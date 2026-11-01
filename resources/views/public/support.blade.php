@extends('portal.layouts.app')
@section('title', 'Hỗ trợ - Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell"><p class="portal-eyebrow">TRUNG TÂM HỖ TRỢ</p><h1>Chúng tôi có thể giúp gì cho bạn?</h1><p class="portal-lead">Tìm hướng dẫn về tài khoản, lịch khám và thông tin trong hồ sơ bệnh nhân.</p></div></section>
<section class="public-guide"><div class="portal-shell">
    <div class="public-guide__intro"><p class="portal-eyebrow">BẮT ĐẦU TẠI ĐÂY</p><h2>Hỗ trợ theo nhu cầu</h2><p>Chọn nội dung phù hợp với việc bạn đang thực hiện.</p></div>
    <div class="public-guide__grid public-guide__grid--3">
        <article class="public-guide__card"><span class="public-guide__number" aria-hidden="true">01</span><h3>Tài khoản và hồ sơ</h3><p>Đăng nhập, kiểm tra hoặc cập nhật thông tin cá nhân trong tài khoản bệnh nhân.</p><a class="portal-text-link" href="{{ route('patient.profile.show') }}">Xem hồ sơ →</a></article>
        <article class="public-guide__card"><span class="public-guide__number" aria-hidden="true">02</span><h3>Lịch khám</h3><p>Tìm bác sĩ, chọn thời gian và theo dõi trạng thái lịch hẹn của bạn.</p><a class="portal-text-link" href="{{ route('patient.appointments') }}">Xem lịch khám →</a></article>
        <article class="public-guide__card"><span class="public-guide__number" aria-hidden="true">03</span><h3>Thông tin sau khám</h3><p>Xem kết quả, đơn thuốc và khoản thanh toán khi thông tin đã có trong tài khoản.</p><a class="portal-text-link" href="{{ route('patient.results') }}">Xem kết quả khám →</a></article>
    </div>
    <div class="public-guide__section"><div class="public-guide__intro"><p class="portal-eyebrow">THÔNG TIN LIÊN HỆ</p><h2>Liên hệ hỗ trợ</h2><p>Thông tin dưới đây chỉ là dữ liệu minh họa cho bản demo, chưa phải kênh hỗ trợ thực tế.</p></div>
        <div class="public-guide__grid public-guide__grid--3">
            <article class="public-guide__card"><h3>Điện thoại</h3><p>Chưa cấu hình số điện thoại chính thức.</p></article>
            <article class="public-guide__card"><h3>Email minh họa</h3><p>hotro@mediway.example</p></article>
            <article class="public-guide__card"><h3>Địa chỉ</h3><p>Chưa cấu hình địa chỉ chính thức.</p></article>
        </div>
    </div>
    <div class="public-guide__section"><div class="public-guide__intro"><p class="portal-eyebrow">CÂU HỎI THƯỜNG GẶP</p><h2>Giải đáp nhanh</h2></div><div class="public-guide__faq">
        <details><summary>Làm sao để đặt lịch khám?</summary><p>Đăng nhập, mở Lịch khám và chọn Đặt lịch khám. Tìm bác sĩ, chọn khung giờ, kiểm tra hồ sơ và xác nhận lịch hẹn. Xem thêm <a href="{{ route('public.care-process') }}">quy trình khám</a>.</p></details>
        <details><summary>Tôi xem hoặc thay đổi lịch hẹn ở đâu?</summary><p>Trong Lịch khám, chọn Xem tất cả lịch hẹn rồi mở lịch cần xem. Chức năng hủy hoặc đổi lịch chỉ xuất hiện khi lịch hẹn đủ điều kiện theo quy định của hệ thống.</p></details>
        <details><summary>Tôi cần làm gì nếu quên mật khẩu?</summary><p>@guest Mở <a href="{{ route('patient.password.request') }}">trang khôi phục mật khẩu</a> @else Từ màn hình đăng nhập, mở trang khôi phục mật khẩu @endguest và làm theo hướng dẫn hiển thị. Khả năng xác minh phụ thuộc cấu hình hiện tại của Mediway.</p></details>
        <details><summary>Vì sao chưa thấy kết quả hoặc đơn thuốc?</summary><p>Thông tin chỉ xuất hiện khi có dữ liệu phù hợp trong hồ sơ. Nếu cần giải thích kết quả hoặc có câu hỏi chuyên môn, hãy trao đổi với bác sĩ hoặc cơ sở khám.</p></details>
        <details><summary>Làm sao sửa thông tin định danh hoặc đổi số điện thoại?</summary><p>Mở Hồ sơ cá nhân để xem các trường được phép cập nhật. Với số điện thoại đăng nhập, chọn Đổi số điện thoại để xem trạng thái chức năng và hướng dẫn hiện tại.</p></details>
    </div></div>
</div></section>
@endsection
