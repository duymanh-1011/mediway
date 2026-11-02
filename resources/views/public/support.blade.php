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
    <div class="public-guide__section public-feature"><img src="{{ asset('images/public/records.png') }}" alt="Tài liệu khám bệnh được sắp xếp trên bàn" loading="lazy"><div><p class="portal-eyebrow">TÌM CÂU TRẢ LỜI</p><h2>Bắt đầu từ thông tin bạn đang cần</h2><p>Nếu gặp khó khăn khi sử dụng cổng, hãy kiểm tra bạn đã đăng nhập đúng tài khoản và mở đúng mục liên quan. Với lịch hẹn, xem thêm trạng thái và thông báo trong trang chi tiết; với hồ sơ sau khám, dữ liệu chỉ xuất hiện khi đã được cập nhật phù hợp.</p><p>Khi trao đổi với cơ sở khám, hãy chuẩn bị thông tin cần thiết như thời điểm đặt hẹn hoặc lần khám liên quan. Không cung cấp mật khẩu hay mã xác thực cho người khác.</p></div></div>
    <div class="public-guide__section"><div class="public-guide__intro"><p class="portal-eyebrow">HƯỚNG DẪN TỪNG BƯỚC</p><h2>Xử lý một số tình huống thường gặp</h2></div><div class="public-guide__grid public-guide__grid--3">
        <article class="public-guide__card"><h3>Không tìm thấy lịch hẹn</h3><p>Kiểm tra bạn đã đăng nhập đúng tài khoản, sau đó mở danh sách tất cả lịch hẹn. Xem lại trạng thái xác nhận và ngày khám; nếu vẫn chưa rõ, ghi lại thông tin để trao đổi với cơ sở khám.</p></article>
        <article class="public-guide__card"><h3>Chưa hoàn thiện hồ sơ</h3><p>Mở Hồ sơ cá nhân và kiểm tra thông báo về các trường còn thiếu. Cập nhật những thông tin được phép, lưu lại và quay về thao tác đang thực hiện.</p></article>
        <article class="public-guide__card"><h3>Cần giải thích kết quả</h3><p>Ghi lại tên kết quả, đơn thuốc hoặc lần khám liên quan và hỏi bác sĩ. Nhân viên y tế là người phù hợp để giải thích ý nghĩa và hướng dẫn chăm sóc tiếp theo.</p></article>
    </div></div>
    <div class="public-guide__section"><div class="public-guide__intro"><p class="portal-eyebrow">THÔNG TIN LIÊN HỆ</p><h2>Liên hệ hỗ trợ</h2><p>Thông tin dưới đây chỉ là dữ liệu minh họa cho bản demo, chưa phải kênh hỗ trợ thực tế.</p></div>
        <div class="public-guide__grid public-guide__grid--3">
            <article class="public-guide__card"><h3>Điện thoại</h3><p>Chưa cấu hình số điện thoại chính thức.</p></article>
            <article class="public-guide__card"><h3>Email minh họa</h3><p>hotro@mediway.example</p></article>
            <article class="public-guide__card"><h3>Địa chỉ</h3><p>Chưa cấu hình địa chỉ chính thức.</p></article>
        </div>
    </div>
    <div class="public-guide__section"><div class="public-guide__intro"><p class="portal-eyebrow">CÂU HỎI THƯỜNG GẶP</p><h2>Giải đáp nhanh</h2></div><div class="public-guide__faq">
        <details>
            <summary>Tôi cần chuẩn bị gì trước buổi khám?</summary>
            <p>Trước ngày khám, hãy mở chi tiết lịch hẹn để kiểm tra ngày, giờ và địa điểm. Chuẩn bị giấy tờ tùy thân, thông tin bảo hiểm nếu có, kết quả khám trước đây và danh sách thuốc hoặc thực phẩm bổ sung đang dùng.</p>
            <p>Bạn cũng có thể ghi lại triệu chứng, thời điểm xuất hiện và những câu hỏi muốn trao đổi. Nếu cơ sở khám có hướng dẫn chuẩn bị riêng, hãy làm theo hướng dẫn đó.</p>
            <x-portal.button variant="secondary" :href="route('public.care-process')" aria-label="Xem thêm về chuẩn bị trước buổi khám">Xem thêm →</x-portal.button>
        </details>
        <details>
            <summary>Tại sao không chọn được khung giờ mong muốn?</summary>
            <p>Danh sách khung giờ phụ thuộc vào lịch bác sĩ còn nhận tại thời điểm bạn tra cứu. Một khung giờ có thể không xuất hiện hoặc không còn được chọn khi lịch làm việc thay đổi hay không còn chỗ.</p>
            <p>Hãy thử chọn ngày khác, xem lịch của bác sĩ phù hợp khác hoặc kiểm tra lại sau. Sau khi chọn được giờ, hãy hoàn tất xác nhận và kiểm tra thông báo thành công trước khi rời trang.</p>
            <x-portal.button variant="secondary" :href="route('patient.doctors.index')" aria-label="Xem thêm lịch bác sĩ">Xem thêm →</x-portal.button>
        </details>
        <details>
            <summary>Làm thế nào để bảo vệ tài khoản?</summary>
            <p>Chọn mật khẩu khó đoán, không dùng chung với người khác và không chia sẻ mật khẩu hay mã xác thực. Kiểm tra kỹ địa chỉ trang trước khi nhập thông tin đăng nhập; không gửi thông tin đăng nhập qua tin nhắn hoặc email.</p>
            <p>Đăng xuất sau khi dùng thiết bị chung và kiểm tra những thay đổi trong hồ sơ. Nếu nghi ngờ tài khoản bị truy cập trái phép, hãy đổi mật khẩu khi có thể và trao đổi với cơ sở khám để được hướng dẫn.</p>
        </details>
        <details>
            <summary>Làm sao để đặt lịch khám?</summary>
            <p>Đăng nhập, tìm bác sĩ hoặc chuyên khoa phù hợp, sau đó chọn ngày và khung giờ còn nhận lịch. Kiểm tra thông tin cá nhân; nếu hệ thống yêu cầu bổ sung hồ sơ, hãy hoàn thiện các trường cần thiết trước khi tiếp tục.</p>
            <p>Đọc lại thông tin bác sĩ và thời gian khám rồi xác nhận. Lịch chỉ được ghi nhận khi hệ thống thông báo thành công; bạn có thể mở danh sách lịch khám để kiểm tra lại trạng thái và chi tiết.</p>
            <x-portal.button variant="secondary" :href="route('public.care-process')" aria-label="Xem thêm về quy trình đặt lịch khám">Xem thêm →</x-portal.button>
        </details>
        <details>
            <summary>Tôi xem hoặc thay đổi lịch hẹn ở đâu?</summary>
            <p>Đăng nhập và mở Lịch khám, sau đó chọn danh sách tất cả lịch hẹn và mở lịch bạn muốn kiểm tra. Trang chi tiết cho biết thông tin và trạng thái hiện tại của lịch hẹn.</p>
            <p>Nếu cần thay đổi kế hoạch, hãy kiểm tra các lựa chọn hủy hoặc đổi lịch được hiển thị. Các thao tác này phụ thuộc trạng thái và điều kiện của từng lịch; đọc kỹ thông tin trước khi xác nhận và kiểm tra lại kết quả sau khi thực hiện.</p>
            <x-portal.button variant="secondary" :href="route('patient.appointments')" aria-label="Xem thêm lịch hẹn của bạn">Xem thêm →</x-portal.button>
        </details>
        <details>
            <summary>Tôi cần làm gì nếu quên mật khẩu?</summary>
            <p>Từ màn hình đăng nhập, chọn chức năng khôi phục mật khẩu và làm theo hướng dẫn hiển thị. Chuẩn bị số điện thoại đăng nhập và kiểm tra thông báo ở từng bước; khả năng xác minh phụ thuộc vào cấu hình hiện tại của Mediway.</p>
            <p>Nếu chức năng chưa khả dụng hoặc bạn không thể xác minh thông tin, hãy trao đổi với cơ sở khám để được hướng dẫn. Không gửi mật khẩu hoặc mã xác thực cho bất kỳ ai.</p>
            @guest<x-portal.button variant="secondary" :href="route('patient.password.request')" aria-label="Xem thêm về khôi phục mật khẩu">Xem thêm →</x-portal.button>@endguest
        </details>
        <details>
            <summary>Vì sao chưa thấy kết quả hoặc đơn thuốc?</summary>
            <p>Kết quả và đơn thuốc xuất hiện khi dữ liệu tương ứng đã có trong hồ sơ của bạn. Hãy kiểm tra bạn đã đăng nhập đúng tài khoản và xem thông tin của lần khám phù hợp.</p>
            <p>Việc chưa thấy dữ liệu không đồng nghĩa với một kết luận về tình trạng sức khỏe. Nếu cần biết thời điểm có kết quả hoặc cần giải thích nội dung, hãy trao đổi trực tiếp với bác sĩ hoặc cơ sở khám.</p>
            <x-portal.button variant="secondary" :href="route('patient.results')" aria-label="Xem thêm kết quả khám">Xem thêm →</x-portal.button>
        </details>
        <details>
            <summary>Làm sao sửa thông tin định danh hoặc đổi số điện thoại?</summary>
            <p>Mở Hồ sơ cá nhân để kiểm tra thông tin hiện có và chỉnh sửa những trường được phép. Một số thông tin định danh được quản lý riêng hoặc chỉ có thể nhập khi chưa có dữ liệu, vì vậy hãy đọc hướng dẫn hiển thị trước khi lưu.</p>
            <p>Với số điện thoại đăng nhập, chọn chức năng Đổi số điện thoại để xem trạng thái và hướng dẫn hiện tại. Nếu cần điều chỉnh thông tin không thể tự sửa, hãy liên hệ cơ sở khám để được hỗ trợ.</p>
            <x-portal.button variant="secondary" :href="route('patient.profile.show')" aria-label="Xem thêm thông tin hồ sơ cá nhân">Xem thêm →</x-portal.button>
        </details>
    </div></div>
</div></section>
@endsection
