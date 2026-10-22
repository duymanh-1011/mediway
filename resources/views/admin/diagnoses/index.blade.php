@extends('admin.layouts.app')
@section('admin-page', 'diagnoses-index')

@section('title', 'Chẩn đoán')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chẩn đoán</strong>
@endsection

@section('content')

    <div class="admin-u-ed1667a9">

        <h1 class="page-title">
            Chẩn đoán
        </h1>

        <p class="page-description">
            Tra cứu và quản lý chẩn đoán
            trong các lượt khám.
        </p>

    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc chẩn đoán',
        'description' => 'Lọc theo từ khóa, bệnh, loại chẩn đoán, mức độ và khoảng thời gian.',
    ])
        <form
            method="GET"
            action="{{ route('admin.diagnoses.index') }}"
         class="admin-filter-form">

            <div class="admin-u-c6c96973 admin-filter-layout">

                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Tìm kiếm
                    </label>

                    <input class="admin-u-19b29622"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Lượt khám, bệnh nhân, bệnh..."

                    >
@error('search')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Bệnh
                    </label>

                    <select class="admin-u-181f32ca"
                        name="disease_id"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($diseases as $disease)

                            <option
                                value="{{ $disease->id }}"
                                @selected(
                                    (string) request('disease_id')
                                    ===
                                    (string) $disease->id
                                )
                            >
                                {{ $disease->name }}
                            </option>

                        @endforeach

                    </select>
@error('disease_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Loại chẩn đoán
                    </label>

                    <select class="admin-u-181f32ca"
                        name="diagnosis_type"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($diagnosisTypes as $type)

                            <option
                                value="{{ $type }}"
                                @selected(
                                    request('diagnosis_type')
                                    ===
                                    $type
                                )
                            >
                                {{ \App\Models\AdminDisplay::label($type) }}
                            </option>

                        @endforeach

                    </select>
@error('diagnosis_type')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Mức độ
                    </label>

                    <select class="admin-u-181f32ca"
                        name="severity"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($severityLevels as $severity)

                            <option
                                value="{{ $severity }}"
                                @selected(
                                    request('severity')
                                    ===
                                    $severity
                                )
                            >
                                {{ \App\Models\AdminDisplay::label($severity) }}
                            </option>

                        @endforeach

                    </select>
@error('severity')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Từ ngày
                    </label>

                    <input class="admin-u-19b29622"
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"

                    >
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Đến ngày
                    </label>

                    <input class="admin-u-19b29622"
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"

                    >
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror

                </div>

            </div>


            <div class="admin-u-0c24989a admin-filter-layout">





            </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.diagnoses.index'), 'searchOnly' => false])
        </form>
    @endcomponent


    <div class="card">

        @if ($diagnoses->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bệnh</th>
                        <th>Loại</th>
                        <th>Mức độ</th>
                        <th>Chẩn đoán lúc</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>


                    <tbody>

                    @foreach ($diagnoses as $diagnosis)

                        <tr>

                            @include('admin.layouts.partials.row-number', ['rows' => $diagnoses, 'index' => $loop->index])
                            <td>
                                <strong>
                                    {{ $diagnosis->medicalVisit?->visit_code ?: '—' }}
                                </strong>
                            </td>

                            <td>

                                {{ $diagnosis->medicalVisit?->patient?->full_name ?: '—' }}

                                @if ($diagnosis->medicalVisit?->patient)

                                    <div class="admin-u-4095d47e">
                                        {{ $diagnosis->medicalVisit->patient->patient_code }}
                                    </div>

                                @endif

                            </td>

                            <td>
                                {{ $diagnosis->disease?->name ?: '—' }}
                            </td>

                            <td>
                                {{ \App\Models\AdminDisplay::label($diagnosis->diagnosis_type ?: '—') }}
                            </td>

                            <td>
                                {{ \App\Models\AdminDisplay::label($diagnosis->severity ?: '—') }}
                            </td>

                            <td>
                                {{ $diagnosis->diagnosed_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td>

                                <div class="admin-u-c9035c8e">

                                    <a
                                        href="{{ route(
                                            'admin.diagnoses.show',
                                            $diagnosis
                                        ) }}"
                                        class="admin-btn admin-btn-detail btn btn-secondary"
                                    >Chi tiết</a>


                                    @if ($diagnosis->medicalVisit?->canRecordClinicalData())

                                        <a
                                            href="{{ route(
                                                'admin.diagnoses.edit',
                                                $diagnosis
                                            ) }}"
                                            class="admin-btn btn btn-primary admin-btn-primary"
                                        >Sửa</a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div class="admin-u-6840f0a6">
                {{ $diagnoses->links() }}
            </div>

        @else

            <div class="admin-u-caa0d3af">
                Không tìm thấy chẩn đoán phù hợp.
            </div>

        @endif

    </div>

@endsection
