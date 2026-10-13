@extends('admin.layouts.app')

@section('content')
<div class="admin-list-page">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Hàng đợi trước phòng khám</h1>
            <p class="text-sm text-slate-500">Điều phối lượt chờ khám của bệnh nhân theo thời gian thực</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.queues.index') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-48">
            <label for="queue-room" class="mb-1 block text-xs font-semibold text-slate-600">Phòng khám</label>
            <select id="queue-room" name="room_id" class="w-full text-sm">
                <option value="">-- Tất cả phòng khám --</option>
                @foreach($rooms as $room)<option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="queue-date" class="mb-1 block text-xs font-semibold text-slate-600">Ngày khám</label>
            <input id="queue-date" type="date" name="date" value="{{ $date }}">
        </div>
        <button type="submit" class="admin-button admin-button--primary">Lọc</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="admin-table-scroll">
        <table class="admin-table w-full text-left">
            <thead>
                <tr class="bg-slate-100 text-slate-600 text-xs uppercase font-semibold border-b">
                    <th class="p-4">STT</th>
                    <th class="p-4">Mã LK</th>
                    <th class="p-4">Bệnh nhân</th>
                    <th class="p-4">Phòng / Bác sĩ</th>
                    <th class="p-4">Loại hàng đợi</th>
                    <th class="p-4">Trạng thái</th>
                    <th class="p-4 text-right">Điều phối</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($queues as $q)
                <tr class="hover:bg-slate-50">
                    <td class="font-bold text-lg text-indigo-600">#{{ $q->queue_number }}</td>
                    <td class="font-semibold">{{ $q->visit_code }}</td>
                    <td>{{ $q->patient_name }}</td>
                    <td>
                        <div>{{ $q->room_name }}</div>
                        <div class="text-xs text-slate-400">{{ $q->doctor_name }}</div>
                    </td>
                    <td class="text-xs font-medium text-slate-500">{{ $q->queue_type }}</td>
                    <td>
                        @php
                            $queueStatusClass = match ($q->status_name) {
                                'WAITING' => 'admin-badge--warning',
                                'CALLED', 'IN_SERVICE' => 'admin-badge--info',
                                'COMPLETED' => 'admin-badge--success',
                                'CANCELLED' => 'admin-badge--danger',
                                default => '',
                            };
                        @endphp
                        <span class="admin-badge {{ $queueStatusClass }}">{{ $q->status_name }}</span>
                    </td>
                    <td class="admin-table-actions">
                        <div class="flex flex-wrap justify-end gap-2">
                        <form action="{{ route('admin.queues.call', $q->id) }}" method="POST" class="inline">
                            @csrf <button type="submit" class="admin-button admin-button--secondary">Gọi</button>
                        </form>
                        <form action="{{ route('admin.queues.startService', $q->id) }}" method="POST" class="inline">
                            @csrf <button type="submit" class="admin-button admin-button--primary">Khám</button>
                        </form>
                        <form action="{{ route('admin.queues.complete', $q->id) }}" method="POST" class="inline">
                            @csrf <button type="submit" class="admin-button admin-button--success">Hoàn tất</button>
                        </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-10 text-center text-slate-500">Không có hàng đợi nào.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="admin-pagination border-t p-4">{{ $queues->links() }}</div>
    </div>
</div>
@endsection
