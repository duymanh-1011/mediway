@extends('portal.layouts.app')
@section('title', 'Kết quả khám bệnh - Mediway')
@section('content')
<section class="medical-page"><div class="portal-shell medical-shell">
    @include('portal.clinical._navigation')
    <header class="medical-hero"><div><p class="medical-hero__eyebrow">HỒ SƠ CỦA BẠN</p><h1>Kết quả khám bệnh</h1><p>Theo dõi từng lượt khám, tiến độ dịch vụ và kết quả đã được ghi nhận.</p></div><div class="medical-hero__symbol" aria-hidden="true">✳</div></header>
    <div class="medical-content patient-records">
        <div class="medical-section-head"><div><span class="medical-section-head__eyebrow">HÀNH TRÌNH KHÁM</span><h2>Các lượt khám của bạn</h2><p>Chọn một lượt khám để xem thông tin chi tiết.</p></div><span class="medical-count">{{ $visits->total() }} lượt khám</span></div>
        <div class="patient-records__list">
        @forelse ($visits as $visit)
            @php
                $items = $visit->serviceOrders->flatMap->items;
                $finished = $items->filter(fn ($item) => $item->serviceStatus?->name === 'COMPLETED')->count();
                $processing = $items->filter(fn ($item) => $item->serviceStatus?->name === 'PROCESSING')->count();
                $pending = $items->filter(fn ($item) => $item->serviceStatus?->name === 'PENDING')->count();
                $cancelled = $items->filter(fn ($item) => $item->serviceStatus?->name === 'CANCELLED')->count();
                $unknown = $items->count() - $finished - $processing - $pending - $cancelled;
                $groups = $items->flatMap(function ($item) {
                    $serviceGroups = $item->medicalService?->serviceGroups ?? collect();
                    return $serviceGroups->isEmpty()
                        ? collect([['key' => 'ungrouped', 'name' => 'Dịch vụ khác', 'item' => $item]])
                        : $serviceGroups->map(fn ($group) => ['key' => 'group-'.$group->id, 'name' => $group->name, 'item' => $item]);
                })->groupBy('key');
                $visitDocuments = collect();
                foreach ($items as $item) {
                    if (! $item->result) continue;
                    $serviceName = $item->medicalService?->name ?? 'Dịch vụ';
                    if ($item->result->hasDocument('pdf')) {
                        $visitDocuments->push(['label' => 'Phiếu kết quả PDF', 'source' => $serviceName, 'url' => route('patient.results.document', [$item->result->id, 'result_pdf'])]);
                    }
                    if ($item->result->hasDocument('image')) {
                        $visitDocuments->push(['label' => 'Ảnh kết quả', 'source' => $serviceName, 'url' => route('patient.results.document', [$item->result->id, 'result_image'])]);
                    }
                }
                if ($visit->prescription?->hasPdf()) {
                    $visitDocuments->push(['label' => 'Đơn thuốc PDF', 'source' => $visit->prescription->prescription_code, 'url' => route('patient.prescriptions.document', $visit->prescription->id)]);
                }
            @endphp
            <article class="patient-record clinical-visit-card" data-visit-id="{{ $visit->id }}">
                <div class="patient-record__header"><div><span class="patient-record__label">LƯỢT KHÁM · {{ $visit->visit_code }}</span><h3>{{ $visit->specialty?->name ?? 'Lượt khám' }}</h3></div><span class="patient-record__badge">{{ $visit->status?->description ?? 'Chưa cập nhật trạng thái' }}</span></div>
                <div class="patient-record__meta"><span>Ngày tiếp nhận <strong>{{ $visit->checked_in_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</strong></span><span>Ngày kết thúc <strong>{{ $visit->examination_ended_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</strong></span><span>Bác sĩ <strong>{{ $visit->doctor?->full_name ?? 'Chưa cập nhật' }}</strong></span></div>
                @if ($visit->diagnoses->isNotEmpty())<div class="patient-record__content"><h4>Chẩn đoán</h4>@foreach ($visit->diagnoses as $diagnosis) @php($diseaseName = trim((string) $diagnosis->disease?->name)) @php($description = trim((string) $diagnosis->diagnosis_description)) <p>{{ $diseaseName ?: 'Chẩn đoán' }}@if ($description !== '' && mb_strtolower($description, 'UTF-8') !== mb_strtolower($diseaseName, 'UTF-8')) — {{ $description }}@endif</p>@endforeach</div>@endif
                <div class="clinical-progress"><div><strong>Tiến độ dịch vụ</strong><span>{{ $finished }}/{{ $items->count() }} hoàn thành</span></div><div class="clinical-progress__bar" aria-hidden="true"><span style="width: {{ $items->count() ? round($finished / $items->count() * 100) : 0 }}%"></span></div><div class="clinical-progress__counts"><span>Hoàn thành: {{ $finished }}</span><span>Đang thực hiện: {{ $processing }}</span><span>Chờ: {{ $pending }}</span>@if ($cancelled)<span>Đã hủy: {{ $cancelled }}</span>@endif @if ($unknown)<span>Chưa xác định: {{ $unknown }}</span>@endif</div></div>
                <details class="clinical-visit-details"><summary>Xem kết quả và dịch vụ <span aria-hidden="true">⌄</span></summary><div class="clinical-visit-details__body">
                    <section><h4>Kết luận khám</h4>
                    @if ($visit->conclusion)
                        @foreach (['professional_conclusion' => 'Kết luận', 'disposition' => 'Hướng xử trí', 'treatment_plan' => 'Kế hoạch điều trị', 'instructions' => 'Hướng dẫn'] as $field => $label)
                            @if ($visit->conclusion->{$field})<div class="patient-record__content"><h4>{{ $label }}</h4><p>{{ $field === 'disposition' ? \App\Models\AdminDisplay::label($visit->conclusion->{$field}) : $visit->conclusion->{$field} }}</p></div>@endif
                        @endforeach
                        @if ($visit->conclusion->has_follow_up)<div class="patient-record__content"><h4>Tái khám</h4><p>{{ $visit->conclusion->follow_up_date?->format('d/m/Y') ?? 'Theo hướng dẫn của cơ sở y tế' }}@if ($visit->conclusion->follow_up_reason) — {{ $visit->conclusion->follow_up_reason }}@endif</p></div>@endif
                    @else<p>Chưa có kết luận khám.</p>@endif</section>
                    <section><h4>Dịch vụ và kết quả</h4>
                    @forelse ($groups as $group)
                        @php($groupItems = $group->pluck('item'))
                        <details class="clinical-service-group"><summary>{{ $group->first()['name'] }} <span>{{ $groupItems->count() }} dịch vụ · {{ $groupItems->filter(fn ($entry) => $entry->serviceStatus?->name === 'COMPLETED')->count() }} hoàn thành</span></summary><div class="clinical-service-group__body">
                    @foreach ($groupItems as $item)
                        <div class="clinical-service"><div class="clinical-service__head"><div><strong>{{ $item->medicalService?->name ?? 'Dịch vụ' }}</strong><small>{{ $item->medicalService?->serviceGroups->pluck('name')->join(', ') ?: 'Chưa phân nhóm' }}</small></div><span class="patient-record__badge">{{ $item->serviceStatus?->description ?? 'Chưa cập nhật' }}</span></div>
                            @if ($item->result)
                                <p class="clinical-service__verification">{{ $item->result->verified_by_user_id ? 'Đã xác minh' : 'Chưa xác minh' }}</p>
                                @if ($item->result->result_summary !== null && trim($item->result->result_summary) !== '')<p>{{ $item->result->result_summary }}</p>@endif
                                @if ($item->result->result_conclusion)<p><strong>Kết luận:</strong> {{ $item->result->result_conclusion }}</p>@endif
                                @if ($item->result->parameterDisplayRows())<dl class="patient-record__parameters">@foreach ($item->result->parameterDisplayRows() as $row)<div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach</dl>@endif
                            @else<p>Chưa có kết quả dịch vụ.</p>@endif
                        </div>
                    @endforeach
                        </div></details>
                    @empty<p>Chưa có dịch vụ được ghi nhận.</p>@endforelse
                    </section>
                    <section aria-labelledby="visit-documents-{{ $visit->id }}">
                        <h4 id="visit-documents-{{ $visit->id }}">Tệp đính kèm của lượt khám</h4>
                        <div class="patient-document-grid">
                            @forelse ($visitDocuments as $document)
                                <a class="patient-document" href="{{ $document['url'] }}"><span class="patient-document__icon" aria-hidden="true">▤</span><span><strong>{{ $document['label'] }}</strong><small>{{ $document['source'] }}</small></span><span aria-hidden="true">↗</span></a>
                            @empty
                                <div class="patient-records__empty patient-records__empty--compact"><p>Lượt khám này chưa có tệp đính kèm.</p></div>
                            @endforelse
                        </div>
                    </section>
                    @if ($visit->prescription)<a class="portal-btn portal-btn--secondary" href="{{ route('patient.prescriptions', ['visit' => $visit->id]) }}">Xem đơn thuốc của lượt khám</a>@endif
                </div></details>
            </article>
        @empty
            <div class="patient-records__empty"><span aria-hidden="true">✳</span><h3>Chưa có lượt khám</h3><p>Thông tin khám sẽ xuất hiện tại đây khi được ghi nhận trong hồ sơ của bạn.</p></div>
        @endforelse
        </div>
        {{ $visits->links() }}
    </div>
</div></section>
@endsection
