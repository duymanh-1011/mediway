@extends('admin.layouts.app')

@section('title', 'Lượt khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard / Vận hành khám / Lượt khám
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Lượt khám
        </h1>

        <p class="page-description">
            Theo dõi toàn bộ lượt khám,
            bệnh nhân, bác sĩ, phòng khám
            và trạng thái xử lý.
        </p>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <form
            method="GET"
            action="{{ route('admin.visits.index') }}"
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
                        placeholder="Mã lượt khám, BN, BS, lịch hẹn..."
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
                        Trạng thái
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
                                    (string) request('visit_status_id')
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
                                {{ $doctor->doctor_code }}
                                -
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
                        Chuyên khoa
                    </label>

                    <select
                        name="specialty_id"
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

                        @foreach ($specialties as $specialty)

                            <option
                                value="{{ $specialty->id }}"
                                @selected(
                                    (string) request('specialty_id')
                                    ===
                                    (string) $specialty->id
                                )
                            >
                                {{ $specialty->name }}
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
                        Phòng khám
                    </label>

                    <select
                        name="examination_room_id"
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

                        @foreach ($examinationRooms as $room)

                            <option
                                value="{{ $room->id }}"
                                @selected(
                                    (string) request(
                                        'examination_room_id'
                                    )
                                    ===
                                    (string) $room->id
                                )
                            >
                                {{ $room->name }}
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
                        Nguồn tiếp nhận
                    </label>

                    <select
                        name="reception_source"
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

                        @foreach ($receptionSources as $source)

                            <option
                                value="{{ $source }}"
                                @selected(
                                    request('reception_source')
                                    ===
                                    $source
                                )
                            >
                                {{ $source }}
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
                    Lọc dữ liệu
                </button>

                <a
                    href="{{ route('admin.visits.index') }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>

    </div>


    <div class="card">

        @if ($medicalVisits->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th>Mã lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Chuyên khoa</th>
                        <th>Phòng</th>
                        <th>Check-in</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>


                    <tbody>

                    @foreach ($medicalVisits as $visit)

                        <tr>

                            <td>
                                <strong>
                                    {{ $visit->visit_code }}
                                </strong>
                            </td>

                            <td>
                                @if ($visit->patient)

                                    <strong>
                                        {{ $visit->patient->full_name }}
                                    </strong>

                                    <div style="
                                        margin-top: 3px;
                                        color: #64748b;
                                        font-size: 12px;
                                    ">
                                        {{ $visit->patient->patient_code }}
                                    </div>

                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                {{ $visit->doctor?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ $visit->specialty?->name ?: '—' }}
                            </td>

                            <td>
                                {{ $visit->examinationRoom?->name ?: '—' }}
                            </td>

                            <td>
                                {{ $visit->checked_in_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td>

                                @if ($visit->status)

                                    <span class="badge">
                                        {{ $visit->status->name }}
                                    </span>

                                @else

                                    <span class="badge badge-danger">
                                        Chưa xác định
                                    </span>

                                @endif

                            </td>

                            <td>

                                <a
                                    href="{{ route(
                                        'admin.visits.show',
                                        $visit
                                    ) }}"
                                    class="btn btn-secondary"
                                >
                                    Chi tiết
                                </a>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div style="margin-top: 18px;">
                {{ $medicalVisits->links() }}
            </div>

        @else

            <div style="
                padding: 32px;
                text-align: center;
                color: #64748b;
            ">
                Không tìm thấy lượt khám phù hợp.
            </div>

        @endif

    </div>

@endsection
