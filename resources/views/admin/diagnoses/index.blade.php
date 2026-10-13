@extends('admin.layouts.app')

@section('content')
<div class="admin-list-page">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Chẩn đoán bệnh</h1>
            <p class="text-sm text-slate-500">Danh sách các chẩn đoán bệnh theo từng lượt khám</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.diagnoses.index') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex-1">
            <label for="diagnosis-search" class="mb-1 block text-xs font-semibold text-slate-600">Tìm chẩn đoán</label>
            <input id="diagnosis-search" type="text" name="search" value="{{ request('search') }}" placeholder="Mã lượt khám, tên bệnh nhân, tên bệnh..." class="w-full">
        </div>
        <div>
            <label for="diagnosis-type" class="mb-1 block text-xs font-semibold text-slate-600">Loại chẩn đoán</label>
            <select id="diagnosis-type" name="diagnosis_type">
                <option value="">-- Tất cả loại chẩn đoán --</option>
                <option value="PRIMARY" {{ request('diagnosis_type') == 'PRIMARY' ? 'selected' : '' }}>Chính (PRIMARY)</option>
                <option value="SECONDARY" {{ request('diagnosis_type') == 'SECONDARY' ? 'selected' : '' }}>Phụ (SECONDARY)</option>
                <option value="PRELIMINARY" {{ request('diagnosis_type') == 'PRELIMINARY' ? 'selected' : '' }}>Sơ bộ (PRELIMINARY)</option>
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
                    <th class="p-4">Tên bệnh (ICD/Danh mục)</th>
                    <th class="p-4">Loại chẩn đoán</th>
                    <th class="p-4">Bác sĩ chẩn đoán</th>
                    <th class="p-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($diagnoses as $item)
                <tr class="hover:bg-slate-50">
                    <td class="font-semibold text-indigo-600">{{ $item->visit_code }}</td>
                    <td>{{ $item->patient_name }}</td>
                    <td class="font-medium text-slate-800">{{ $item->disease_code }} - {{ $item->disease_name }}</td>
                    <td>
                        <span class="admin-badge admin-badge--info">{{ $item->diagnosis_type }}</span>
                    </td>
                    <td>{{ $item->doctor_name }}</td>
                    <td class="admin-table-actions text-right">
                        <a href="{{ route('admin.visits.show', $item->medical_visit_id) }}" class="admin-button admin-button--subtle">Chi tiết lượt khám</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-slate-500">Không tìm thấy chẩn đoán nào.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="admin-pagination border-t p-4">{{ $diagnoses->links() }}</div>
    </div>
</div>
@endsection
