<section class="patient-record portal-visit-panel">
    <h2>Dịch vụ của lượt khám</h2>
    <form method="get" action="{{ route('patient.results.visit', $record->id) }}" class="portal-visit-filter">
        <input type="hidden" name="tab" value="dich-vu">
        <label>Nhóm dịch vụ
            <select name="nhom"><option value="">Tất cả nhóm</option>@foreach ($groups as $key => $group)<option value="{{ $key }}" @selected($groupFilter === $key)>{{ $group->first()['groupName'] }}</option>@endforeach</select>
        </label>
        <label>Trạng thái
            <select name="trang-thai"><option value="">Tất cả trạng thái</option>@foreach (['COMPLETED' => 'Hoàn thành', 'PROCESSING' => 'Đang thực hiện', 'PENDING' => 'Chờ thực hiện', 'CANCELLED' => 'Đã hủy'] as $key => $label)<option value="{{ $key }}" @selected($statusFilter === $key)>{{ $label }}</option>@endforeach</select>
        </label>
        <label>Sắp xếp
            <select name="sap-xep"><option value="theo-nhom">Theo nhóm</option><option value="moi-nhat" @selected($sort === 'moi-nhat')>Mới nhất</option></select>
        </label>
        <x-portal.button type="submit">Áp dụng</x-portal.button>
    </form>
</section>

@if ($visible->isEmpty())
    <x-portal.empty-state title="Chưa có dịch vụ phù hợp" description="Các dịch vụ của lượt khám sẽ xuất hiện tại đây khi được ghi nhận." />
@elseif ($sort === 'moi-nhat')
    <div class="portal-visit-rows">
        @foreach ($visible as $entry)
            @include('portal.clinical.visit-service')
        @endforeach
    </div>
@else
    @foreach ($visible->groupBy('group') as $key => $group)
        <details class="portal-visit-group" @if ($groupFilter === $key || $group->contains('key', $opened)) open @endif>
            <summary><span>{{ $group->first()['groupName'] }}</span><span class="medical-count">{{ $group->count() }} dịch vụ</span></summary>
            <div class="portal-visit-rows">
                @foreach ($group as $entry)
                    @include('portal.clinical.visit-service')
                @endforeach
            </div>
        </details>
    @endforeach
@endif
