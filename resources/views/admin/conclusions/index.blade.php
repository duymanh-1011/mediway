@extends('admin.layouts.app')

@section('content')
<div class="admin-list-page">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Kết luận lượt khám</h1>
            <p class="text-sm text-slate-500">Danh sách kết luận chuyên môn, hướng xử trí và lịch hẹn tái khám</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.conclusions.index') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex-1">
            <label for="conclusion-search" class="mb-1 block text-xs font-semibold text-slate-600">Tìm kết luận</label>
            <input id="conclusion-search" type="text" name="search" value="{{ request('search') }}" placeholder="Mã lượt khám, tên bệnh nhân, nội dung kết luận..." class="w-full">
        </div>
        <button type="submit" class="admin-button admin-button--primary">Tìm kiếm</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="admin-table-scroll">
        <table class="admin-table w-full text-left">
            <thead>
                <tr class="bg-slate-100 text-slate-600 text-xs uppercase font-semibold border-b">
                    <th class="p-4">Mã LK</th>
                    <th class="p-4">Bệnh nhân</th>
                    <th class="p-4">Kết luận chuyên môn</th>
                    <th class="p-4">Tái khám</th>
                    <th class="p-4">Bác sĩ kết luận</th>
                    <th class="p-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($conclusions as $item)
                <tr class="hover:bg-slate-50">
                    <td class="font-semibold text-indigo-600">{{ $item->visit_code }}</td>
                    <td>{{ $item->patient_name }}</td>
                    <td class="text-slate-800 font-medium">{{ Str::limit($item->professional_conclusion, 40) }}</td>
                    <td>
                        @if($item->has_follow_up)
                            <span class="admin-badge admin-badge--success">Có ({{ $item->follow_up_date }})</span>
                        @else
                            <span class="admin-badge">Không</span>
                        @endif
                    </td>
                    <td>{{ $item->doctor_name }}</td>
                    <td class="admin-table-actions text-right">
                        <a href="{{ route('admin.visits.show', $item->medical_visit_id) }}" class="admin-button admin-button--subtle">Chi tiết lượt khám</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-slate-500">Không tìm thấy kết luận lượt khám nào.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="admin-pagination border-t p-4">{{ $conclusions->links() }}</div>
    </div>
</div>
@endsection
