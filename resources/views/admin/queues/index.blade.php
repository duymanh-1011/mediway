@extends('admin.layouts.app')
@section('admin-page', 'queues-index')

@section('title', 'Hàng đợi phòng khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Hàng đợi phòng khám</strong>
@endsection

@section('content')

    <div class="admin-u-ed1667a9">
        <h1 class="page-title">
            Hàng đợi phòng khám
        </h1>

        <p class="page-description">
            Theo dõi và điều phối bệnh nhân
            trong hàng đợi phòng khám.
        </p>
    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc hàng đợi phòng khám',
        'description' => 'Lọc theo từ khóa, trạng thái, loại hàng đợi, ngày, bác sĩ và phòng khám.',
    ])
        <form data-filter-fields="6"
            method="GET"
            action="{{ route('admin.queues.index') }}"
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
                        placeholder="Mã lượt khám, bệnh nhân..."

                    >
@error('search')<div class="field-error">{{ $message }}</div>@enderror
                </div>


                <div class="admin-filter-field">
                    <label class="admin-u-63101fce">
                        Trạng thái
                    </label>

                    <select class="admin-u-181f32ca"
                        name="queue_status_id"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($queueStatuses as $status)

                            <option
                                value="{{ $status->id }}"
                                @selected(
                                    (string) request('queue_status_id')
                                    ===
                                    (string) $status->id
                                )
                            >
                                {{ \App\Models\AdminDisplay::label($status->name) }}
                            </option>

                        @endforeach

                    </select>
@error('queue_status_id')<div class="field-error">{{ $message }}</div>@enderror
                </div>


                <div class="admin-filter-field">
                    <label class="admin-u-63101fce">
                        Loại hàng đợi
                    </label>

                    <select class="admin-u-181f32ca"
                        name="queue_type"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        <option
                            value="INITIAL_EXAM"
                            @selected(
                                request('queue_type')
                                ===
                                'INITIAL_EXAM'
                            )
                        >
                            Khám ban đầu
                        </option>

                        <option
                            value="RETURN_AFTER_SERVICE"
                            @selected(
                                request('queue_type')
                                ===
                                'RETURN_AFTER_SERVICE'
                            )
                        >
                            Quay lại sau dịch vụ
                        </option>

                    </select>
@error('queue_type')<div class="field-error">{{ $message }}</div>@enderror
                </div>


                <div class="admin-filter-field">
                    <label class="admin-u-63101fce">
                        Ngày
                    </label>

                    <input class="admin-u-19b29622"
                        type="date"
                        name="date"
                        value="{{ request('date') }}"

                    >
@error('date')<div class="field-error">{{ $message }}</div>@enderror
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
@error('doctor_id')<div class="field-error">{{ $message }}</div>@enderror
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

            </div>


            <div class="admin-u-0c24989a admin-filter-layout">





            </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.queues.index'), 'searchOnly' => false])
        </form>
    @endcomponent


    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách hàng đợi phòng khám'])


        @if ($queues->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Số hàng đợi</th>
                        <th>Lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Loại</th>
                        <th>Bác sĩ</th>
                        <th>Phòng</th>
                        <th>Trạng thái</th>
                        <th>Vào hàng</th>
                        <th>Số lần gọi</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach ($queues as $queue)

                        <tr>

                            @include('admin.layouts.partials.row-number', ['rows' => $queues, 'index' => $loop->index])
                            <td>
                                <strong>
                                    {{ $queue->queue_number }}
                                </strong>
                            </td>

                            <td>
                                @include('admin.layouts.partials.record-code', ['code' => $queue->medicalVisit?->visit_code ?: '—'])
                            </td>

                            <td>
                                {{ $queue->medicalVisit?->patient?->full_name ?: '—' }}
                            </td>

                            <td>
                                @if ($queue->queue_type === 'INITIAL_EXAM')
                                    Khám ban đầu
                                @elseif ($queue->queue_type === 'RETURN_AFTER_SERVICE')
                                    Quay lại sau dịch vụ
                                @else
                                    {{ \App\Models\AdminDisplay::label($queue->queue_type) }}
                                @endif
                            </td>

                            <td>
                                {{ $queue->medicalVisit?->doctor?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ $queue->medicalVisit?->examinationRoom?->name ?: '—' }}
                            </td>

                            <td>
                                @include('admin.layouts.partials.status-badge', ['badgeCode' => $queue->status?->name ?: '—'])
                            </td>

                            <td>
                                {{ $queue->queued_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td>
                                {{ $queue->call_count }}
                            </td>

                            <td><div class="admin-actions admin-table-actions">
                                <a
                                    href="{{ route(
                                        'admin.queues.show',
                                        $queue
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
                {{ $queues->links() }}
            </div>

        @else

            <div class="admin-u-caa0d3af">
                Không có dữ liệu hàng đợi phù hợp.
            </div>

        @endif

    </div>

@endsection
