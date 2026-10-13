@extends('admin.layouts.app')

@section('content')
<div class="admin-visit-detail">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-800">{{ $visit->patient_name }}</h1>
                <span class="admin-badge admin-badge--info">{{ $visit->visit_code }}</span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Mã BN: {{ $visit->patient_code }} | Phòng khám: {{ $visit->room_name }} | Bác sĩ: {{ $visit->doctor_name }}</p>
        </div>
        <a href="{{ route('admin.visits.index') }}" class="admin-button admin-button--secondary">Quay lại danh sách</a>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <!-- Thông tin nhanh -->
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-800 text-base mb-3 border-b pb-2">Thông tin tiếp nhận</h3>
                <div class="space-y-2 text-sm text-slate-600">
                    <div><span class="text-slate-400">CCCD:</span> {{ $visit->citizen_id_number ?? 'N/A' }}</div>
                    <div><span class="text-slate-400">Nhóm máu:</span> <span class="font-bold text-rose-600">{{ $visit->blood_type ?? 'N/A' }}</span></div>
                    <div><span class="text-slate-400">Nguồn:</span> {{ $visit->reception_source }}</div>
                    <div><span class="text-slate-400">Trạng thái:</span> <span class="font-semibold text-indigo-600">{{ $visit->status_name }}</span></div>
                </div>
            </div>
        </div>

        <!-- Các Form nghiệp vụ khám -->
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <!-- Lâm sàng -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-800 text-lg mb-4 text-indigo-600">1. Khám Lâm Sàng</h3>
                <form action="{{ route('admin.clinical-notes.storeOrUpdate', $visit->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="reason-for-visit" class="block text-xs font-semibold text-slate-600 mb-1">Lý do khám</label>
                            <textarea id="reason-for-visit" name="reason_for_visit" class="w-full" rows="2">{{ $clinicalNote->reason_for_visit ?? '' }}</textarea>
                        </div>
                        <div>
                            <label for="current-symptoms" class="block text-xs font-semibold text-slate-600 mb-1">Triệu chứng hiện tại</label>
                            <textarea id="current-symptoms" name="current_symptoms" class="w-full" rows="2">{{ $clinicalNote->current_symptoms ?? '' }}</textarea>
                        </div>
                    </div>
                    <div>
                        <label for="physical-examination" class="block text-xs font-semibold text-slate-600 mb-1">Khám lâm sàng</label>
                        <textarea id="physical-examination" name="physical_examination" class="w-full" rows="2">{{ $clinicalNote->physical_examination ?? '' }}</textarea>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="admin-button admin-button--primary">Lưu lâm sàng</button></div>
                </form>
            </div>

            <!-- Chẩn đoán -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-800 text-lg mb-4 text-indigo-600">2. Chẩn đoán bệnh</h3>
                <div class="mb-4 space-y-2">
                    @foreach($diagnoses as $diag)
                    <div class="flex justify-between items-center p-3 bg-slate-50 border rounded-xl text-sm">
                        <div>
                            <span class="font-bold">{{ $diag->disease_code }} - {{ $diag->disease_name }}</span>
                            <span class="ml-2 px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-xs">{{ $diag->diagnosis_type }}</span>
                        </div>
                        <form action="{{ route('admin.diagnoses.destroy', $diag->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-button admin-button--danger">Xóa chẩn đoán</button>
                        </form>
                    </div>
                    @endforeach
                </div>
                <form action="{{ route('admin.diagnoses.store', $visit->id) }}" method="POST" class="grid grid-cols-12 gap-3 items-end">
                    @csrf
                    <div class="col-span-6">
                        <select name="disease_id" class="w-full">
                            <option value="">-- Chọn bệnh --</option>
                            @foreach($diseases as $dis)<option value="{{ $dis->id }}">{{ $dis->disease_code }} - {{ $dis->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-span-4">
                        <select name="diagnosis_type" class="w-full">
                            <option value="PRIMARY">PRIMARY (Chính)</option>
                            <option value="SECONDARY">SECONDARY (Phụ)</option>
                            <option value="PRELIMINARY">PRELIMINARY (Sơ bộ)</option>
                        </select>
                    </div>
                    <div class="col-span-2"><button type="submit" class="admin-button admin-button--primary w-full">Thêm chẩn đoán</button></div>
                </form>
            </div>

            <!-- Kết luận -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-800 text-lg mb-4 text-emerald-600">3. Kết luận & Hoàn tất</h3>
                <form action="{{ route('admin.conclusions.storeOrUpdate', $visit->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="doctor_id" value="{{ $visit->doctor_id }}">
                    <div>
                        <label for="professional-conclusion" class="block text-xs font-semibold text-slate-600 mb-1">Kết luận chuyên môn *</label>
                        <textarea id="professional-conclusion" name="professional_conclusion" required class="w-full" rows="2">{{ $conclusion->professional_conclusion ?? '' }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="has-follow-up" class="block text-xs font-semibold text-slate-600 mb-1">Tái khám?</label>
                            <select id="has-follow-up" name="has_follow_up" class="w-full">
                                <option value="0">Không</option>
                                <option value="1" {{ isset($conclusion) && $conclusion->has_follow_up ? 'selected' : '' }}>Có</option>
                            </select>
                        </div>
                        <div>
                            <label for="follow-up-date" class="block text-xs font-semibold text-slate-600 mb-1">Ngày tái khám</label>
                            <input id="follow-up-date" type="date" name="follow_up_date" value="{{ $conclusion->follow_up_date ?? '' }}" class="w-full">
                        </div>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="admin-button admin-button--success">Lưu kết luận & hoàn tất</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
