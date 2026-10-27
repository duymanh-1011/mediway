<footer class="portal-footer">
    <div class="portal-shell">
        <div class="portal-footer__grid">
            <div class="portal-footer__about">
                <a class="portal-brand portal-brand--footer" href="{{ route('public.home') }}"><span aria-hidden="true">✦</span> Mediway</a>
                <p>Chăm sóc sức khỏe gần gũi hơn, bắt đầu từ những trải nghiệm đơn giản và thuận tiện.</p>
                <span class="portal-footer__tag">Đồng hành cùng sức khỏe của bạn</span>
            </div>
            <div><h2>Về Mediway</h2><a href="{{ route('public.about') }}">Giới thiệu</a><a href="{{ route('public.care-process') }}">Quy trình khám</a><a href="{{ route('public.news') }}">Tin tức sức khỏe</a></div>
            <div><h2>Hỗ trợ</h2><a href="{{ route('public.support') }}">Thông tin hỗ trợ</a>@guest<a href="{{ route('patient.login') }}">Đăng nhập</a><a href="{{ route('patient.register') }}">Tạo tài khoản</a>@endguest</div>
        </div>
        <div class="portal-footer__bottom"><span>© {{ now()->year }} Mediway</span><span>Chăm sóc tận tâm · Kết nối thuận tiện</span></div>
    </div>
</footer>
