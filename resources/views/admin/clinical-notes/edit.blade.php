@extends('admin.layouts.app')
@section('admin-page', 'clinical-notes-edit')

@section('title', 'Sửa nội dung khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Nội dung khám</span>
    <span aria-hidden="true">/</span>
    <span>@include('admin.layouts.partials.record-code', ['code' => $clinicalNote->medicalVisit?->visit_code])</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="admin-page-header">
        <div>

        <h1 class="page-title">
            Sửa nội dung khám
        </h1>

        <p class="page-description">
            Lượt khám:
            @include('admin.layouts.partials.record-code', ['code' => $clinicalNote->medicalVisit?->visit_code])
        </p>

        </div>
        <a href="{{ route('admin.clinical-notes.show', $clinicalNote) }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <strong>Bệnh nhân:</strong>
        {{ $clinicalNote->medicalVisit?->patient?->full_name ?: '—' }}

        <br>

        <strong>Bác sĩ:</strong>
        {{ $clinicalNote->medicalVisit?->doctor?->full_name ?: '—' }}

    </div>


    <div class="card">

        <form novalidate
            method="POST"
            action="{{ route(
                'admin.clinical-notes.update',
                $clinicalNote
            ) }}"
        >

            @csrf
            @method('PUT')


            @php
                $textareas = [
                    'reason_for_visit' =>
                        'Lý do khám',

                    'current_symptoms' =>
                        'Triệu chứng hiện tại',

                    'present_illness_history' =>
                        'Bệnh sử hiện tại',

                    'relevant_medical_history' =>
                        'Tiền sử liên quan',

                    'physical_examination' =>
                        'Khám lâm sàng',

                    'initial_assessment' =>
                        'Nhận định ban đầu',

                    'notes' =>
                        'Ghi chú',
                ];
            @endphp


            @foreach ($textareas as $field => $label)

                <div class="admin-u-2f23af6b">

                    <label class="admin-u-63101fce"
                        for="{{ $field }}"

                    >
                        {{ $label }}
                    </label>

                    <textarea class="admin-u-fb28156a"
                        id="{{ $field }}"
                        name="{{ $field }}"
                        rows="4"

                    >{{ old(
                        $field,
                        $clinicalNote->{$field}
                    ) }}</textarea>

                    @error($field)

                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>

                    @enderror

                </div>

            @endforeach


            <div class="admin-u-2f23af6b">

                <label class="admin-u-63101fce"
                    for="recorded_at"

                >
                    Thời điểm ghi nhận
                </label>

                <input class="admin-u-419e8d7e"
                    type="datetime-local"
                    id="recorded_at"
                    name="recorded_at"
                    value="{{ old(
                        'recorded_at',
                        $clinicalNote->recorded_at
                            ? $clinicalNote->recorded_at->format(
                                'Y-m-d\TH:i'
                            )
                            : ''
                    ) }}"

                >

                @error('recorded_at')

                    <div class="admin-u-6aa3ac13">
                        {{ \App\Models\AdminDisplay::text($message) }}
                    </div>

                @enderror

            </div>


            <div class="admin-u-5786f359">

                <a
                    href="{{ route(
                        'admin.clinical-notes.show',
                        $clinicalNote
                    ) }}"
                    class="admin-btn admin-btn-secondary"
                >Hủy</a>

                <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>

            </div>

        </form>

    </div>

@endsection
