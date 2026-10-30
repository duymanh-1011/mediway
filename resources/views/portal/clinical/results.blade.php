@extends('portal.layouts.app')
@section('title', 'Kết quả khám bệnh - Mediway')
@section('content')
<section class="medical-page"><div class="portal-shell medical-shell">
    @include('portal.clinical._navigation')
    <header class="medical-hero"><div><p class="medical-hero__eyebrow">HỒ SƠ CỦA BẠN</p><h1>Kết quả khám bệnh</h1><p>Xem kết quả dịch vụ và các tài liệu trong hồ sơ của bạn.</p></div><div class="medical-hero__symbol" aria-hidden="true">✳</div></header>
    <div class="medical-content patient-records">
    <div class="medical-section-head"><div><span class="medical-section-head__eyebrow">KẾT QUẢ DỊCH VỤ</span><h2>Kết quả của bạn</h2><p>Sắp xếp theo thời gian ghi nhận, mới nhất trước.</p></div><span class="medical-count">{{ $results->total() }} kết quả</span></div>
    {{-- Mỗi kết quả đi kèm thông tin dịch vụ và lượt khám liên quan. --}}
    <div class="patient-records__list">
    @forelse ($results as $result)
        @php($item = $result->serviceOrderItem)
        @php($visit = $item?->serviceOrder?->medicalVisit)
        <article class="patient-record">
            <div class="patient-record__header"><div><span class="patient-record__label">KẾT QUẢ DỊCH VỤ</span><h3>{{ $item?->medicalService?->name ?? 'Kết quả dịch vụ' }}</h3></div><span @class(['patient-record__badge', 'patient-record__badge--pending' => ! $result->verified_by_user_id])>{{ $result->verified_by_user_id ? 'Đã xác minh' : 'Chưa xác minh' }}</span></div>
            <div class="patient-record__meta"><span>Ngày có kết quả: <strong>{{ $result->resulted_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</strong></span><span>Ngày khám: <strong>{{ $visit?->checked_in_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</strong></span><span>Bác sĩ: <strong>{{ $visit?->doctor?->full_name ?? 'Chưa cập nhật' }}</strong></span></div>
            @if ($result->result_summary)<div class="patient-record__content"><h4>Nội dung kết quả</h4><p>{{ $result->result_summary }}</p></div>@endif
            @if ($result->result_conclusion)<div class="patient-record__content"><h4>Kết luận</h4><p>{{ $result->result_conclusion }}</p></div>@endif
            @if ($result->result_parameters)<dl class="patient-record__parameters">@foreach ($result->result_parameters as $label => $value)<div><dt>{{ is_string($label) ? $label : 'Thông số' }}</dt><dd>@if (is_scalar($value)){{ $value }}@endif</dd></div>@endforeach</dl>@endif
            {{-- Nút mở chỉ xuất hiện khi tệp kết quả tồn tại và hợp lệ. --}}
            <div class="patient-record__links">
                @if ($result->hasDocument('pdf'))<a href="{{ route('patient.results.document', [$result->id, 'result_pdf']) }}">Xem phiếu kết quả PDF <span aria-hidden="true">↗</span></a>@endif
                @if ($result->hasDocument('image'))<a href="{{ route('patient.results.document', [$result->id, 'result_image']) }}">Xem ảnh kết quả <span aria-hidden="true">↗</span></a>@endif
            </div>
        </article>
    @empty
        <div class="patient-records__empty"><span aria-hidden="true">✳</span><h3>Chưa có kết quả khám bệnh</h3><p>Kết quả sẽ xuất hiện tại đây khi có dữ liệu trong hồ sơ của bạn.</p></div>
    @endforelse
    </div>
    {{ $results->links() }}
    <div class="medical-section-head medical-section-head--spaced"><div><span class="medical-section-head__eyebrow">TỆP ĐÍNH KÈM</span><h2>Tài liệu trong hồ sơ</h2><p>Ảnh và tệp đã được lưu trong hồ sơ cá nhân của bạn.</p></div><span class="medical-count">{{ $documents->count() }} tài liệu</span></div>
    {{-- Tài liệu cá nhân được gộp ở đây; khi mở tệp hệ thống kiểm tra lại quyền. --}}
    <div class="patient-document-grid">
        @forelse ($documents as $document)
            <a class="patient-document" href="{{ route('patient.documents.show', $document['key']) }}"><span class="patient-document__icon" aria-hidden="true">▤</span><span><strong>{{ $document['label'] }}</strong><small>Mở tài liệu</small></span><span aria-hidden="true">↗</span></a>
        @empty
            <div class="patient-records__empty patient-records__empty--compact"><p>Hồ sơ của bạn chưa có tài liệu được tải lên.</p></div>
        @endforelse
    </div>
</div></div></section>
@endsection
