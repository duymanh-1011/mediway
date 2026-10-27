@extends('portal.layouts.app')
@section('title', 'Về Mediway')
@section('content')
<section class="portal-page-head"><div class="portal-shell"><p class="portal-eyebrow">VỀ MEDIWAY</p><h1>Chăm sóc sức khỏe lấy người bệnh làm trung tâm</h1><p class="portal-lead">Chúng tôi theo đuổi một trải nghiệm y tế minh bạch, dễ tiếp cận và tôn trọng từng người bệnh.</p></div></section>
<section class="portal-section"><div class="portal-shell portal-grid portal-grid--3">@foreach ([['Tận tâm','Lắng nghe nhu cầu và tôn trọng lựa chọn của người bệnh.'],['Rõ ràng','Thông tin được trình bày dễ hiểu trong từng bước chăm sóc.'],['Kết nối','Đồng hành cùng người bệnh trước, trong và sau mỗi lần khám.']] as [$title, $text])<article class="portal-card"><h2>{{ $title }}</h2><p>{{ $text }}</p></article>@endforeach</div></section>
@endsection
