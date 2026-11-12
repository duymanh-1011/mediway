@php
    $label = $entry['statusLabel'] ?: match ($entry['status']) {
        'COMPLETED' => 'Hoàn thành',
        'PROCESSING' => 'Đang thực hiện',
        'PENDING' => 'Chờ thực hiện',
        'CANCELLED' => 'Đã hủy',
        default => 'Chưa cập nhật',
    };
    $result = $entry['result'];
@endphp
<article class="patient-record portal-visit-panel portal-visit-service" id="dich-vu-{{ $entry['key'] }}">
    <div class="patient-record__header">
        <div><span class="patient-record__label">{{ $entry['groupName'] }}</span><h3>{{ $entry['name'] }}</h3></div>
        <span @class(['patient-record__badge', 'patient-record__badge--pending' => $entry['status'] !== 'COMPLETED'])>{{ $label }}</span>
    </div>
    <dl class="portal-visit-facts">
        @if ($entry['room'])<div><dt>Nơi thực hiện</dt><dd>{{ $entry['room'] }}</dd></div>@endif
        @if ($entry['performer'])<div><dt>Người thực hiện</dt><dd>{{ $entry['performer'] }}</dd></div>@endif
        @foreach (['ordered' => 'Tiếp nhận / chỉ định', 'started' => 'Bắt đầu', 'completed' => 'Hoàn thành'] as $key => $name)
            @if ($entry[$key])<div><dt>{{ $name }}</dt><dd>{{ $entry[$key]->format('d/m/Y H:i') }}</dd></div>@endif
        @endforeach
    </dl>
    <details class="clinical-visit-details" @if ($opened === $entry['key']) open @endif>
        <summary>Chi tiết dịch vụ <span aria-hidden="true">⌄</span></summary>
        <div class="clinical-visit-details__body">
            <dl class="portal-visit-facts">
                <div><dt>Tên dịch vụ</dt><dd>{{ $entry['name'] }}</dd></div>
                <div><dt>Số lượng</dt><dd>1 {{ $entry['unit'] ?: 'lượt' }}</dd></div>
                <div><dt>Đơn giá ghi nhận</dt><dd>{{ $entry['price'] !== null ? number_format((float) $entry['price'], 0, ',', '.').' ₫' : 'Chưa ghi nhận' }}</dd></div>
            </dl>
            @if ($result)
                <div class="portal-visit-section">
                    <h4>Kết quả dịch vụ</h4>
                    @if ($result->resulted_at)<p>Ghi nhận lúc {{ $result->resulted_at->format('d/m/Y H:i') }}</p>@endif
                    @if ($result->result_summary)<p>{{ $result->result_summary }}</p>@endif
                    @if ($result->result_conclusion)<p><strong>Kết luận</strong> {{ $result->result_conclusion }}</p>@endif
                    @if ($rows = $result->parameterDisplayRows())
                        <dl class="patient-record__parameters">@foreach ($rows as $row)<div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach</dl>
                    @endif
                    @if ($rows = $result->technicalDetailRows())
                        <h4>Thông tin bổ sung do nhân viên ghi nhận</h4>
                        <dl class="patient-record__parameters">@foreach ($rows as $row)<div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach</dl>
                    @endif
                    @if ($result->hasDocument('pdf') || $result->hasDocument('image'))
                        <a href="{{ route('patient.results.visit', ['visit' => $record->id, 'tab' => 'giay-to']) }}">Xem tệp kết quả trong mục Giấy tờ</a>
                    @endif
                    @if ($result->hasContent())
                        <div class="portal-visit-actions">
                            <x-portal.button :href="route('patient.results.service-print', $result)">Xem và in phiếu kết quả</x-portal.button>
                        </div>
                    @endif
                </div>
            @elseif ($entry['key'] !== 'kham-benh')
                <p>Chưa có kết quả dịch vụ.</p>
            @endif
        </div>
    </details>
</article>
