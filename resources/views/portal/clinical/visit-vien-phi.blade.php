<section class="patient-record portal-visit-panel">
    <h2>Các khoản đã thanh toán</h2>
    @if ($payments->isNotEmpty())
        <div class="portal-visit-total"><span>Tổng đã thanh toán</span><strong>{{ number_format((float) $payments->sum(fn ($payment) => $payment['transaction']->amount), 0, ',', '.') }} ₫</strong></div>
    @endif
    <div class="portal-visit-rows">
        @forelse ($payments as $payment)
            @php($transaction = $payment['transaction'])
            <article class="portal-visit-payment">
                <div class="portal-visit-line">
                    <h3>{{ $payment['payable']->displayContent() }}</h3>
                    <strong class="portal-visit-amount">{{ number_format((float) $transaction->amount, 0, ',', '.') }} ₫</strong>
                </div>
                <dl class="portal-visit-facts">
                    <div><dt>Mã giao dịch</dt><dd>{{ $transaction->transaction_code }}</dd></div>
                    <div><dt>Phương thức</dt><dd>{{ \App\Models\AdminDisplay::label($transaction->payment_method) }}</dd></div>
                    <div><dt>Ngày thanh toán</dt><dd>{{ $payment['date']?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Nhân viên thu tiền</dt><dd>{{ $transaction->collector?->full_name ?? 'Chưa có thông tin' }}</dd></div>
                </dl>
                <a class="portal-back" href="{{ route('patient.results.receipt', [$record->id, $transaction->id]) }}">Xem phiếu thu</a>
            </article>
        @empty
            <x-portal.empty-state title="Chưa có giao dịch đã thanh toán" description="Các giao dịch thành công của lượt khám sẽ xuất hiện tại đây." />
        @endforelse
    </div>
    <div class="portal-visit-actions"><a href="{{ route('patient.payments.index') }}">Xem các khoản còn cần thanh toán →</a></div>
</section>
