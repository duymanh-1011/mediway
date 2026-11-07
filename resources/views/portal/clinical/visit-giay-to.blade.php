<section class="patient-record portal-visit-panel">
    <h2>Giấy tờ của lượt khám</h2>
    <div class="portal-visit-rows">
        @forelse ($payments->filter(fn ($payment) => $payment['can_print']) as $payment)
            <a class="portal-visit-row" href="{{ route('patient.results.receipt', [$record->id, $payment['transaction']->id]) }}">
                <span><strong>{{ match ($payment['payable']->payment_type) { 'EXAMINATION_FEE' => 'Phiếu thu phí khám', 'SERVICE_FEE' => 'Phiếu thu dịch vụ', 'MEDICINE_FEE' => 'Phiếu thu tiền thuốc', default => 'Phiếu thu' } }}</strong><small>{{ $payment['transaction']->transaction_code }} @if ($payment['date']) · {{ $payment['date']->format('d/m/Y H:i') }} @endif</small></span>
                <span class="medical-count">Xem phiếu thu</span>
            </a>
        @empty
            <p>Chưa có phiếu thu cho lượt khám này.</p>
        @endforelse
    </div>
    <div class="portal-visit-section">
        <h3>Tệp kết quả và đơn thuốc</h3>
        @php
            $documents = collect();
            foreach ($entries as $entry) {
                if (! $entry['result']) continue;
                foreach (['pdf' => 'Phiếu kết quả PDF', 'image' => 'Ảnh kết quả'] as $type => $label) {
                    if ($entry['result']->hasDocument($type)) {
                        $documents->push(['label' => $label, 'source' => $entry['name'], 'url' => route('patient.results.document', [$entry['result']->id, 'result_'.$type])]);
                    }
                }
            }
            if ($record->prescription?->hasPdf()) {
                $documents->push(['label' => 'Đơn thuốc PDF', 'source' => $record->prescription->prescription_code, 'url' => route('patient.prescriptions.document', $record->prescription->id)]);
            }
        @endphp
        <div class="portal-visit-rows">
            @forelse ($documents as $document)
                <a class="portal-visit-row" href="{{ $document['url'] }}"><span><strong>{{ $document['label'] }}</strong><small>{{ $document['source'] }}</small></span><span aria-hidden="true">↗</span></a>
            @empty
                <p>Lượt khám này chưa có tệp đính kèm.</p>
            @endforelse
        </div>
    </div>
</section>
