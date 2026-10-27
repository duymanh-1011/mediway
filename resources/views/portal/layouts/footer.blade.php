<footer class="portal-footer">
    <div class="portal-shell portal-footer__grid">
        <div><a class="portal-brand portal-brand--footer" href="{{ route('public.home') }}">✦ Mediway</a><p>Chăm sóc sức khỏe rõ ràng, thuận tiện và lấy người bệnh làm trung tâm.</p></div>
        <div><h2>Khám phá</h2><a href="{{ route('public.about') }}">Giới thiệu</a><a href="{{ route('public.care-process') }}">Quy trình khám</a><a href="{{ route('public.news') }}">Tin tức sức khỏe</a></div>
        <div><h2>Hỗ trợ</h2><a href="{{ route('public.support') }}">Thông tin hỗ trợ</a><a href="{{ route('patient.login') }}">Đăng nhập bệnh nhân</a><a href="{{ route('patient.register') }}">Đăng ký tài khoản</a></div>
    </div>
    <div class="portal-shell portal-footer__bottom">© {{ now()->year }} Mediway. Phiên bản học thuật.</div>
</footer>
