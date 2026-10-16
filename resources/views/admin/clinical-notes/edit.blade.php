@extends('admin.layouts.app')

@section('title', 'Sửa nội dung khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Nội dung khám /
        {{ $clinicalNote->medicalVisit?->visit_code }} /
        Sửa
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Sửa nội dung khám
        </h1>

        <p class="page-description">
            Lượt khám:
            {{ $clinicalNote->medicalVisit?->visit_code }}
        </p>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <strong>Bệnh nhân:</strong>
        {{ $clinicalNote->medicalVisit?->patient?->full_name ?: '—' }}

        <br>

        <strong>Bác sĩ:</strong>
        {{ $clinicalNote->medicalVisit?->doctor?->full_name ?: '—' }}

    </div>


    <div class="card">

        <form
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

                <div style="
                    margin-bottom: 18px;
                ">

                    <label
                        for="{{ $field }}"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        {{ $label }}
                    </label>

                    <textarea
                        id="{{ $field }}"
                        name="{{ $field }}"
                        rows="4"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            resize: vertical;
                        "
                    >{{ old(
                        $field,
                        $clinicalNote->{$field}
                    ) }}</textarea>

                    @error($field)

                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            @endforeach


            <div style="
                margin-bottom: 18px;
            ">

                <label
                    for="recorded_at"
                    style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    "
                >
                    Thời điểm ghi nhận
                </label>

                <input
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
                    style="
                        width: 100%;
                        padding: 10px 12px;
                        border: 1px solid #e2e8f0;
                        border-radius: 8px;
                    "
                >

                @error('recorded_at')

                    <div style="
                        margin-top: 5px;
                        color: #dc2626;
                    ">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            <div style="
                display: flex;
                gap: 8px;
            ">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu thay đổi
                </button>

                <a
                    href="{{ route(
                        'admin.clinical-notes.show',
                        $clinicalNote
                    ) }}"
                    class="btn btn-secondary"
                >
                    Hủy
                </a>

            </div>

        </form>

    </div>

@endsection
