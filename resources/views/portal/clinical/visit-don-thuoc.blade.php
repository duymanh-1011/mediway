@if ($prescription = $record->prescription)
    <section class="patient-record portal-visit-panel">
        <div class="patient-record__header">
            <div><span class="patient-record__label">ĐƠN THUỐC</span><h2>{{ $prescription->prescription_code }}</h2></div>
            @if ($prescription->status !== 'ACTIVE')<span class="patient-record__badge patient-record__badge--pending">{{ \App\Models\AdminDisplay::label($prescription->status) }}</span>@endif
        </div>
        <dl class="portal-visit-facts">
            <div><dt>Bác sĩ kê đơn</dt><dd>{{ $prescription->doctor?->full_name ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Ngày kê</dt><dd>{{ $prescription->prescribed_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt>Số loại thuốc</dt><dd>{{ $prescription->items->count() }}</dd></div>
        </dl>
        <ol class="portal-visit-medicines">
            @foreach ($prescription->items as $item)
                <li>
                    <strong>{{ $item->medicine?->name ?? 'Thuốc' }} {{ $item->medicine?->strength }}</strong>
                    @if ($item->quantity !== null)<span> · {{ str_replace('.', ',', (string) (float) $item->quantity) }} {{ $item->usage_unit }}</span>@endif
                    @if ($item->medicine?->active_ingredient)<p>Hoạt chất: {{ $item->medicine->active_ingredient }}</p>@endif
                    @if ($instruction = collect([$item->dose_per_use, $item->administration_time, $item->usage_method, $item->usage_instructions])->filter()->unique()->join(' · '))<p>{{ $instruction }}</p>@endif
                </li>
            @endforeach
        </ol>
        <div class="portal-visit-actions"><x-portal.button :href="route('patient.prescriptions.show', $prescription->id)">Xem chi tiết đơn thuốc</x-portal.button></div>
    </section>
@else
    <x-portal.empty-state title="Chưa có đơn thuốc" description="Đơn thuốc của lượt khám sẽ xuất hiện tại đây khi được ghi nhận." />
@endif
