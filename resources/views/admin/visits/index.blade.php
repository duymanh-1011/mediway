@extends('admin.layouts.app')
@section('admin-page', 'visits-index')

@section('title', 'Lượt khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lượt khám</strong>
@endsection

@section('content')

    <div class="admin-u-ed1667a9">

        <h1 class="page-title">
            Lượt khám
        </h1>

        <p class="page-description">
            Theo dõi toàn bộ lượt khám,
            bệnh nhân, bác sĩ, phòng khám
            và trạng thái xử lý.
        </p>

    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc lượt khám',
        'description' => 'Lọc theo từ khóa, trạng thái, bác sĩ, chuyên khoa, phòng khám, nguồn tiếp nhận và khoảng thời gian.',
    ])
        <form
            method="GET"
            action="{{ route('admin.visits.index') }}"
         class="admin-filter-form">

            <div class="admin-u-e6828601 admin-filter-layout">

                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Tìm kiếm
                    </label>

                    <input class="admin-u-19b29622"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Mã lượt khám, BN, BS, lịch hẹn..."

                    >
@error('search')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Trạng thái
                    </label>

                    <select class="admin-u-181f32ca"
                        name="visit_status_id"

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
                                {{ \App\Models\AdminDisplay::label($status->name) }}
                            </option>

                        @endforeach

                    </select>
@error('visit_status_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Bác sĩ
                    </label>

                    <select class="admin-u-181f32ca"
                        name="doctor_id"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($doctors as $doctor)

                            <option
                    value="{{ $doctor->id }}"
                    data-specialty-id="{{ $doctor->specialty_id }}"
                    data-is-general="{{ $doctor->specialty?->is_general ? '1' : '0' }}"
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
@error('doctor_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Chuyên khoa
                    </label>

                    <select class="admin-u-181f32ca"
                        name="specialty_id"

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
@error('specialty_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Phòng khám
                    </label>

                    <select class="admin-u-181f32ca"
                        name="examination_room_id"

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
@error('examination_room_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Nguồn tiếp nhận
                    </label>

                    <select class="admin-u-181f32ca"
                        name="reception_source"

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
                                {{ \App\Models\AdminDisplay::label($source) }}
                            </option>

                        @endforeach

                    </select>
@error('reception_source')<div class="field-error">{{ $message }}</div>@enderror

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


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.visits.index'), 'searchOnly' => false])
        </form>
    @endcomponent


    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách lượt khám'])


        @if ($medicalVisits->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Chuyên khoa</th>
                        <th>Phòng</th>
                        <th>Tiếp nhận</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>


                    <tbody>

                    @foreach ($medicalVisits as $visit)

                        <tr>

                            @include('admin.layouts.partials.row-number', ['rows' => $medicalVisits, 'index' => $loop->index])
                            <td>
                                <strong>
                                    @include('admin.layouts.partials.record-code', ['code' => $visit->visit_code])
                                </strong>
                            </td>

                            <td>
                                @if ($visit->patient)

                                    <strong>
                                        {{ $visit->patient->full_name }}
                                    </strong>

                                    <div class="admin-u-ade7448d">
                                        @include('admin.layouts.partials.record-code', ['code' => $visit->patient->patient_code])
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

                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $visit->status->name])

                                @else

                                    <span class="badge badge-danger">
                                        Chưa xác định
                                    </span>

                                @endif

                            </td>

                            <td><div class="admin-actions admin-table-actions">

                                <a
                                    href="{{ route(
                                        'admin.visits.show',
                                        $visit
                                    ) }}"
                                    class="admin-btn admin-btn-detail"
                                >Chi tiết</a>

                            </div></td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div class="admin-u-6840f0a6">
                {{ $medicalVisits->links() }}
            </div>

        @else

            <div class="admin-u-f98eb126">
                Không tìm thấy lượt khám phù hợp.
            </div>

        @endif

    </div>

@endsection
