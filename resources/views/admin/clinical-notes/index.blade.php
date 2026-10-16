@extends('admin.layouts.app')

@section('content')
<div class="admin-list-page">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Nội dung khám lâm sàng</h1>
            <p class="text-sm text-slate-500">Danh sách thông tin triệu chứng, lý do khám và khám lâm sàng của bệnh nhân</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.clinical-notes.index') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex-1">
            <label for="clinical-search" class="mb-1 block text-xs font-semibold text-slate-600">Tìm nội dung khám</label>
            <input id="clinical-search" type="text" name="search" value="{{ request('search') }}" placeholder="Mã lượt khám, tên bệnh nhân, lý do khám..." class="w-full">
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
                    <th class="p-4">Bác sĩ ghi nhận</th>
                    <th class="p-4">Lý do khám</th>
                    <th class="p-4">Triệu chứng hiện tại</th>
                    <th class="p-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($notes as $item)
                <tr class="hover:bg-slate-50">
                    <td class="font-semibold text-indigo-600">{{ $item->visit_code }}</td>
                    <td>{{ $item->patient_name }} <span class="text-xs text-slate-400">({{ $item->patient_code }})</span></td>
                    <td>{{ $item->doctor_name }}</td>
                    <td class="text-slate-700">{{ Str::limit($item->reason_for_visit, 30) }}</td>
                    <td class="text-slate-700">{{ Str::limit($item->current_symptoms, 30) }}</td>
                    <td class="admin-table-actions text-right">
                        <a href="{{ route('admin.visits.show', $item->medical_visit_id) }}" class="admin-button admin-button--subtle">Chi tiết lượt khám</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-slate-500">Không tìm thấy bản ghi nội dung khám nào.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="admin-pagination border-t p-4">{{ $notes->links() }}</div>
    </div>
</div>
@endsection
