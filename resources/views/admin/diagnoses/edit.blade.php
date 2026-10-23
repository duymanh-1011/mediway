@extends('admin.layouts.app')
@section('admin-page', 'diagnoses-edit')

@section('title', 'Sửa chẩn đoán')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Chẩn đoán</span>
    <span aria-hidden="true">/</span>
    <span>@include('admin.layouts.partials.record-code', ['code' => $diagnosis->medicalVisit?->visit_code])</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="admin-u-ed1667a9">

        <h1 class="page-title">
            Sửa chẩn đoán
        </h1>

        <p class="page-description">
            Lượt khám:
            @include('admin.layouts.partials.record-code', ['code' => $diagnosis->medicalVisit?->visit_code])
        </p>

    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <strong>Bệnh nhân:</strong>
        {{ $diagnosis->medicalVisit?->patient?->full_name ?: '—' }}

        <br>

        <strong>Bác sĩ:</strong>
        {{ $diagnosis->medicalVisit?->doctor?->full_name ?: '—' }}

    </div>


    <div class="card">

        <form novalidate
            method="POST"
            action="{{ route(
                'admin.diagnoses.update',
                $diagnosis
            ) }}"
        >

            @csrf
            @method('PUT')


            <div class="admin-u-6691f14f">

                <div>

                    <label class="admin-u-63101fce"
                        for="disease_id"

                    >
                        Bệnh
                    </label>

                    <select class="admin-u-184ecf5a"
                        id="disease_id"
                        name="disease_id" data-lookup="diseases"
                        required

                    >

                        <option value="">
                            -- Chọn bệnh --
                        </option>

                        @foreach ($diseases as $disease)

                            <option
                                value="{{ $disease->id }}"
                                @selected(
                                    (string) old(
                                        'disease_id',
                                        $diagnosis->disease_id
                                    )
                                    ===
                                    (string) $disease->id
                                )
                            >
                                {{ $disease->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('disease_id')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="diagnosis_type"

                    >
                        Loại chẩn đoán
                    </label>

                    <input class="admin-u-419e8d7e"
                        type="text"
                        id="diagnosis_type"
                        name="diagnosis_type"
                        list="diagnosis-type-options"
                        value="{{ old(
                            'diagnosis_type',
                            $diagnosis->diagnosis_type
                        ) }}"

                    >

                    <datalist id="diagnosis-type-options">

                        @foreach ($diagnosisTypes as $type)
                            <option value="{{ $type }}">
                        @endforeach

                    </datalist>

                    @error('diagnosis_type')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="severity"

                    >
                        Mức độ
                    </label>

                    <input class="admin-u-419e8d7e"
                        type="text"
                        id="severity"
                        name="severity"
                        list="severity-options"
                        value="{{ old(
                            'severity',
                            $diagnosis->severity
                        ) }}"

                    >

                    <datalist id="severity-options">

                        @foreach ($severityLevels as $severity)
                            <option value="{{ $severity }}">
                        @endforeach

                    </datalist>

                    @error('severity')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="diagnosis_description"

                    >
                        Mô tả chẩn đoán
                    </label>

                    <textarea class="admin-u-fb28156a"
                        id="diagnosis_description"
                        name="diagnosis_description"
                        rows="5"

                    >{{ old(
                        'diagnosis_description',
                        $diagnosis->diagnosis_description
                    ) }}</textarea>

                    @error('diagnosis_description')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="diagnosed_at"

                    >
                        Thời điểm chẩn đoán
                    </label>

                    <input class="admin-u-419e8d7e"
                        type="datetime-local"
                        id="diagnosed_at"
                        name="diagnosed_at"
                        value="{{ old(
                            'diagnosed_at',
                            $diagnosis->diagnosed_at
                                ? $diagnosis->diagnosed_at->format(
                                    'Y-m-d\TH:i'
                                )
                                : ''
                        ) }}"

                    >

                    @error('diagnosed_at')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="notes"

                    >
                        Ghi chú
                    </label>

                    <textarea class="admin-u-fb28156a"
                        id="notes"
                        name="notes"
                        rows="4"

                    >{{ old(
                        'notes',
                        $diagnosis->notes
                    ) }}</textarea>

                    @error('notes')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div class="admin-u-5786f359">

                    <button
                        type="submit"
                        class="admin-btn admin-btn-primary"
                    >Cập nhật</button>

                    <a
                        href="{{ route(
                            'admin.diagnoses.show',
                            $diagnosis
                        ) }}"
                        class="admin-btn admin-btn-secondary"
                    >Hủy</a>

                </div>

            </div>

        </form>

    </div>

@endsection
