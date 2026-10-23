@if ($valueRows)
    <dl class="admin-value-list">
        @foreach ($valueRows as $valueRow)
            <div><dt>{{ $valueRow['label'] }}</dt><dd>{{ $valueRow['value'] }}</dd></div>
        @endforeach
    </dl>
@else
    <p class="admin-empty-note">Chưa có nội dung được ghi nhận.</p>
@endif
