@extends('admin.layouts.app')

@section('content')
<div class="admin-list-page">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Quản lý lượt khám</h1>
            <p class="text-sm text-slate-500">Danh sách tiếp nhận lượt khám thực tế của bệnh nhân</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.visits.index') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex-1 min-w-[200px]">
            <label for="visit-search" class="mb-1 block text-xs font-semibold text-slate-600">Tìm lượt khám</label>
            <input id="visit-search" type="text" name="search" value="{{ request('search') }}" placeholder="Mã lượt khám, mã BN, tên BN..." class="w-full">
        </div>
        <div>
            <label for="visit-date" class="mb-1 block text-xs font-semibold text-slate-600">Ngày khám</label>
            <input id="visit-date" type="date" name="date" value="{{ $date }}">
        </div>
        <div>
            <label for="visit-doctor" class="mb-1 block text-xs font-semibold text-slate-600">Bác sĩ</label>
            <select id="visit-doctor" name="doctor_id">
                <option value="">-- Tất cả bác sĩ --</option>
                @foreach($doctors as $doc)
                    <option value="{{ $doc->id }}" {{ request('doctor_id') == $doc->id ? 'selected' : '' }}>{{ $doc->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="visit-status" class="mb-1 block text-xs font-semibold text-slate-600">Trạng thái</label>
            <select id="visit-status" name="visit_status_id">
                <option value="">-- Tất cả trạng thái --</option>
                @foreach($statuses as $st)
                    <option value="{{ $st->id }}" {{ request('visit_status_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="admin-button admin-button--primary">Lọc dữ liệu</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="admin-table-scroll">
        <table class="admin-table w-full text-left">
            <thead>
                <tr class="bg-slate-100 text-slate-600 text-xs uppercase font-semibold border-b">
                    <th class="p-4">Mã LK</th>
                    <th class="p-4">Bệnh nhân</th>
                    <th class="p-4">Chuyên khoa / Phòng</th>
                    <th class="p-4">Bác sĩ phụ trách</th>
                    <th class="p-4">Thời gian Check-in</th>
                    <th class="p-4">Trạng thái</th>
                    <th class="p-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                @forelse($visits as $v)
                <tr class="hover:bg-slate-50 transition">
                    <td class="font-semibold text-indigo-600">{{ $v->visit_code }}</td>
                    <td>
                        <div class="font-medium text-slate-800">{{ $v->patient_name }}</div>
                        <div class="text-xs text-slate-400">{{ $v->patient_code }}</div>
                    </td>
                    <td>
                        <div>{{ $v->specialty_name }}</div>
                        <div class="text-xs text-slate-400">{{ $v->room_name }}</div>
                    </td>
                    <td class="font-medium">{{ $v->doctor_name }}</td>
                    <td class="text-xs text-slate-500">{{ $v->checked_in_at }}</td>
                    <td>
                        @php
                            $visitStatusClass = match ($v->status_name) {
                                'WAITING', 'CHECKED_IN' => 'admin-badge--warning',
                                'IN_EXAMINATION' => 'admin-badge--info',
                                'COMPLETED' => 'admin-badge--success',
                                'CANCELLED' => 'admin-badge--danger',
                                default => '',
                            };
                        @endphp
                        <span class="admin-badge {{ $visitStatusClass }}">{{ $v->status_name }}</span>
                    </td>
                    <td class="admin-table-actions text-right">
                        <a href="{{ route('admin.visits.show', $v->id) }}" class="admin-button admin-button--subtle">Chi tiết</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-10 text-center text-slate-500">Không có lượt khám nào.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="admin-pagination border-t p-4">{{ $visits->links() }}</div>
    </div>
</div>
@endsection
