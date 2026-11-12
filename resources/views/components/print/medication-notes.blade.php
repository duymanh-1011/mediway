@props(['prescription', 'alwaysShowAdvice' => false])

@php($itemNotes = $prescription->items->filter(fn ($item) => filled($item->usage_instructions)))
@if ($alwaysShowAdvice || filled($prescription->general_instructions) || $itemNotes->isNotEmpty())
    <section class="prescription-advice" aria-labelledby="prescription-advice">
        <h2 id="prescription-advice">Lời dặn của bác sĩ</h2>
        <ul>
            @if (filled($prescription->general_instructions))
                <li><p>{{ $prescription->general_instructions }}</p></li>
            @elseif ($itemNotes->isEmpty())
                <li>Chưa có lời dặn riêng.</li>
            @endif
            @foreach ($itemNotes as $item)
                <li>
                    <strong>{{ $item->medicine?->name ?? 'Thuốc' }} {{ $item->medicine?->strength }}</strong>
                    <p>{{ $item->usage_instructions }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<section class="prescription-advice prescription-general-notes" aria-labelledby="prescription-general-notes">
    <h2 id="prescription-general-notes">Lưu ý chung</h2>
    <ul>
        <li>Dùng thuốc đúng liều, đúng thời điểm và trong thời gian ghi trên đơn.</li>
        <li>Trao đổi với bác sĩ kê đơn hoặc dược sĩ nếu chưa rõ hướng dẫn hoặc cần thay đổi cách dùng.</li>
        <li>Bảo quản thuốc theo hướng dẫn trên nhãn và để xa tầm tay trẻ em.</li>
    </ul>
</section>
