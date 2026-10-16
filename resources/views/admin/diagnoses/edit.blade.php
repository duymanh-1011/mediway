@extends('admin.layouts.app')

@section('title', 'Sửa chẩn đoán')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Chẩn đoán /
        {{ $diagnosis->medicalVisit?->visit_code }} /
        Sửa
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Sửa chẩn đoán
        </h1>

        <p class="page-description">
            Lượt khám:
            {{ $diagnosis->medicalVisit?->visit_code }}
        </p>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <strong>Bệnh nhân:</strong>
        {{ $diagnosis->medicalVisit?->patient?->full_name ?: '—' }}

        <br>

        <strong>Bác sĩ:</strong>
        {{ $diagnosis->medicalVisit?->doctor?->full_name ?: '—' }}

    </div>


    <div class="card">

        <form
            method="POST"
            action="{{ route(
                'admin.diagnoses.update',
                $diagnosis
            ) }}"
        >

            @csrf
            @method('PUT')


            <div style="
                display: grid;
                gap: 18px;
            ">

                <div>

                    <label
                        for="disease_id"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Bệnh
                    </label>

                    <select
                        id="disease_id"
                        name="disease_id"
                        required
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: white;
                        "
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
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="diagnosis_type"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Loại chẩn đoán
                    </label>

                    <input
                        type="text"
                        id="diagnosis_type"
                        name="diagnosis_type"
                        list="diagnosis-type-options"
                        value="{{ old(
                            'diagnosis_type',
                            $diagnosis->diagnosis_type
                        ) }}"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                    <datalist id="diagnosis-type-options">

                        @foreach ($diagnosisTypes as $type)
                            <option value="{{ $type }}">
                        @endforeach

                    </datalist>

                    @error('diagnosis_type')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="severity"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Mức độ
                    </label>

                    <input
                        type="text"
                        id="severity"
                        name="severity"
                        list="severity-options"
                        value="{{ old(
                            'severity',
                            $diagnosis->severity
                        ) }}"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                    <datalist id="severity-options">

                        @foreach ($severityLevels as $severity)
                            <option value="{{ $severity }}">
                        @endforeach

                    </datalist>

                    @error('severity')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="diagnosis_description"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Mô tả chẩn đoán
                    </label>

                    <textarea
                        id="diagnosis_description"
                        name="diagnosis_description"
                        rows="5"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            resize: vertical;
                        "
                    >{{ old(
                        'diagnosis_description',
                        $diagnosis->diagnosis_description
                    ) }}</textarea>

                    @error('diagnosis_description')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="diagnosed_at"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Thời điểm chẩn đoán
                    </label>

                    <input
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
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                    @error('diagnosed_at')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="notes"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Ghi chú
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            resize: vertical;
                        "
                    >{{ old(
                        'notes',
                        $diagnosis->notes
                    ) }}</textarea>

                    @error('notes')
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
                            'admin.diagnoses.show',
                            $diagnosis
                        ) }}"
                        class="btn btn-secondary"
                    >
                        Hủy
                    </a>

                </div>

            </div>

        </form>

    </div>

@endsection
