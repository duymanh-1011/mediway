@extends('portal.layouts.app')
@section('title', 'Về Mediway - Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell"><p class="portal-eyebrow">VỀ MEDIWAY</p><h1>Một nơi thuận tiện để theo dõi hành trình khám</h1><p class="portal-lead">Mediway kết nối các bước từ tìm bác sĩ, đặt lịch đến xem thông tin trong tài khoản bệnh nhân.</p><div class="public-guide__actions"><x-portal.button :href="route('public.care-process')">Tìm hiểu quy trình khám</x-portal.button><x-portal.button variant="secondary" :href="route('public.support')">Xem hỗ trợ</x-portal.button></div></div></section>
<section class="public-guide"><div class="portal-shell">
    <div class="public-guide__intro"><p class="portal-eyebrow">DÀNH CHO NGƯỜI BỆNH</p><h2>Chủ động hơn trong từng bước</h2><p>Thông tin được tổ chức tại một nơi để bạn dễ tìm và kiểm tra khi cần.</p></div>
    <div class="public-guide__grid public-guide__grid--3">
        <article class="public-guide__card"><span class="public-guide__number" aria-hidden="true">01</span><h3>Tìm lịch phù hợp</h3><p>Tra cứu bác sĩ, chuyên khoa và các khung giờ đang nhận lịch trước khi xác nhận đặt khám.</p></article>
        <article class="public-guide__card"><span class="public-guide__number" aria-hidden="true">02</span><h3>Theo dõi thông tin</h3><p>Xem lịch hẹn, hồ sơ cá nhân, kết quả và đơn thuốc khi dữ liệu tương ứng có trong tài khoản.</p></article>
        <article class="public-guide__card"><span class="public-guide__number" aria-hidden="true">03</span><h3>Giữ thông tin riêng tư</h3><p>Thông tin cá nhân và hồ sơ khám được truy cập qua tài khoản bệnh nhân đã đăng nhập.</p></article>
    </div>
    <div class="public-guide__section"><div class="public-guide__note">Thông tin trên cổng hỗ trợ bạn theo dõi quá trình khám. Hãy trao đổi trực tiếp với nhân viên y tế khi cần tư vấn chuyên môn.</div></div>
</div></section>
@endsection
