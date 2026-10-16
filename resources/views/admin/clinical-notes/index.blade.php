@extends('admin.layouts.app')

@section('title', 'Nội dung khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard / Vận hành khám / Nội dung khám
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Nội dung khám
        </h1>

        <p class="page-description">
            Tra cứu nội dung lâm sàng
            được ghi nhận trong từng lượt khám.
        </p>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <form
            method="GET"
            action="{{ route('admin.clinical-notes.index') }}"
        >

            <div style="
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(190px, 1fr));
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
                        placeholder="Mã lượt khám, bệnh nhân, triệu chứng..."
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
                        Bác sĩ
                    </label>

                    <select
                        name="doctor_id"
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

                        @foreach ($doctors as $doctor)

                            <option
                                value="{{ $doctor->id }}"
                                @selected(
                                    (string) request('doctor_id')
                                    ===
                                    (string) $doctor->id
                                )
                            >
                                {{ $doctor->full_name }}
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
                        Trạng thái lượt khám
                    </label>

                    <select
                        name="visit_status_id"
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

                        @foreach ($visitStatuses as $status)

                            <option
                                value="{{ $status->id }}"
                                @selected(
                                    (string) request(
                                        'visit_status_id'
                                    )
                                    ===
                                    (string) $status->id
                                )
                            >
                                {{ $status->name }}
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
                    href="{{ route(
                        'admin.clinical-notes.index'
                    ) }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>

    </div>


    <div class="card">

        @if ($clinicalNotes->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th>Lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Lý do khám</th>
                        <th>Nhận định ban đầu</th>
                        <th>Ghi nhận lúc</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>


                    <tbody>

                    @foreach ($clinicalNotes as $note)

                        <tr>

                            <td>
                                <strong>
                                    {{ $note->medicalVisit?->visit_code ?: '—' }}
                                </strong>
                            </td>

                            <td>

                                {{ $note->medicalVisit?->patient?->full_name ?: '—' }}

                                @if ($note->medicalVisit?->patient)

                                    <div style="
                                        font-size: 12px;
                                        color: #64748b;
                                        margin-top: 3px;
                                    ">
                                        {{ $note->medicalVisit->patient->patient_code }}
                                    </div>

                                @endif

                            </td>

                            <td>
                                {{ $note->medicalVisit?->doctor?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $note->reason_for_visit ?: '—',
                                    60
                                ) }}
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $note->initial_assessment ?: '—',
                                    60
                                ) }}
                            </td>

                            <td>
                                {{ $note->recorded_at?->format(
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
                                            'admin.clinical-notes.show',
                                            $note
                                        ) }}"
                                        class="btn btn-secondary"
                                    >
                                        Chi tiết
                                    </a>

                                    @if (
                                        $note->medicalVisit?->status?->name
                                        !==
                                        'COMPLETED'
                                    )

                                        <a
                                            href="{{ route(
                                                'admin.clinical-notes.edit',
                                                $note
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
                {{ $clinicalNotes->links() }}
            </div>

        @else

            <div style="
                padding: 30px;
                text-align: center;
                color: #64748b;
            ">
                Không tìm thấy nội dung khám phù hợp.
            </div>

        @endif

    </div>

@endsection
