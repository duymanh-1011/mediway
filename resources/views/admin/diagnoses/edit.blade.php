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

    <div class="admin-page-header">
        <div>

        <h1 class="page-title">
            Sửa chẩn đoán
        </h1>

        <p class="page-description">
            Lượt khám:
            @include('admin.layouts.partials.record-code', ['code' => $diagnosis->medicalVisit?->visit_code])
        </p>

        </div>
        <a href="{{ route('admin.diagnoses.show', $diagnosis) }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
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

                    <select class="admin-u-419e8d7e" id="diagnosis_type" name="diagnosis_type" required>
                        <option value="">-- Chọn loại chẩn đoán --</option>
                        @foreach (['PRIMARY', 'SECONDARY', 'PRELIMINARY', 'CONFIRMED'] as $type)
                            <option value="{{ $type }}" @selected(old('diagnosis_type', $diagnosis->diagnosis_type) === $type)>{{ \App\Models\AdminDisplay::label($type) }}</option>
                        @endforeach
                    </select>

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

                    <input class="admin-u-419e8d7e" type="text" id="severity" name="severity" list="severity-options" value="{{ \App\Models\AdminDisplay::label(old('severity', $diagnosis->severity)) === '—' ? '' : \App\Models\AdminDisplay::label(old('severity', $diagnosis->severity)) }}">
                    <datalist id="severity-options">
                        @foreach (collect(['MILD', 'MODERATE', 'SEVERE'])->merge($severityLevels)->push($diagnosis->severity)->filter()->unique() as $severity)
                            <option value="{{ \App\Models\AdminDisplay::label($severity) }}"></option>
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

                    <a
                        href="{{ route(
                            'admin.diagnoses.show',
                            $diagnosis
                        ) }}"
                        class="admin-btn admin-btn-secondary"
                    >Hủy</a>

                    <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>

                </div>

            </div>

        </form>

    </div>

@endsection
