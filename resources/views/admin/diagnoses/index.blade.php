@extends('admin.layouts.app')

@section('title', 'Chẩn đoán')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard / Vận hành khám / Chẩn đoán
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Chẩn đoán
        </h1>

        <p class="page-description">
            Tra cứu và quản lý chẩn đoán
            trong các lượt khám.
        </p>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <form
            method="GET"
            action="{{ route('admin.diagnoses.index') }}"
        >

            <div style="
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(180px, 1fr));
                gap: 14px;
                align-items: end;
            ">

                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Tìm kiếm
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Visit, bệnh nhân, bệnh..."
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Bệnh
                    </label>

                    <select
                        name="disease_id"
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: white;
                        "
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

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Loại chẩn đoán
                    </label>

                    <select
                        name="diagnosis_type"
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: white;
                        "
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
                                {{ $type }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Mức độ
                    </label>

                    <select
                        name="severity"
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: white;
                        "
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
                                {{ $severity }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Từ ngày
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Đến ngày
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                </div>

            </div>


            <div style="
                display: flex;
                gap: 8px;
                margin-top: 16px;
            ">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lọc
                </button>

                <a
                    href="{{ route('admin.diagnoses.index') }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>

    </div>


    <div class="card">

        @if ($diagnoses->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
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

                            <td>
                                <strong>
                                    {{ $diagnosis->medicalVisit?->visit_code ?: '—' }}
                                </strong>
                            </td>

                            <td>

                                {{ $diagnosis->medicalVisit?->patient?->full_name ?: '—' }}

                                @if ($diagnosis->medicalVisit?->patient)

                                    <div style="
                                        margin-top: 3px;
                                        font-size: 12px;
                                        color: #64748b;
                                    ">
                                        {{ $diagnosis->medicalVisit->patient->patient_code }}
                                    </div>

                                @endif

                            </td>

                            <td>
                                {{ $diagnosis->disease?->name ?: '—' }}
                            </td>

                            <td>
                                {{ $diagnosis->diagnosis_type ?: '—' }}
                            </td>

                            <td>
                                {{ $diagnosis->severity ?: '—' }}
                            </td>

                            <td>
                                {{ $diagnosis->diagnosed_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td>

                                <div style="
                                    display: flex;
                                    flex-wrap: wrap;
                                    gap: 6px;
                                ">

                                    <a
                                        href="{{ route(
                                            'admin.diagnoses.show',
                                            $diagnosis
                                        ) }}"
                                        class="btn btn-secondary"
                                    >
                                        Chi tiết
                                    </a>


                                    @if (
                                        $diagnosis->medicalVisit?->status?->name
                                        !==
                                        'COMPLETED'
                                    )

                                        <a
                                            href="{{ route(
                                                'admin.diagnoses.edit',
                                                $diagnosis
                                            ) }}"
                                            class="btn btn-primary"
                                        >
                                            Sửa
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div style="margin-top: 18px;">
                {{ $diagnoses->links() }}
            </div>

        @else

            <div style="
                padding: 30px;
                text-align: center;
                color: #64748b;
            ">
                Không tìm thấy chẩn đoán phù hợp.
            </div>

        @endif

    </div>

@endsection
