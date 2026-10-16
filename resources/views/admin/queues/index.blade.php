@extends('admin.layouts.app')

@section('title', 'Hàng đợi phòng khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard / Vận hành khám / Hàng đợi phòng khám
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">
        <h1 class="page-title">
            Hàng đợi phòng khám
        </h1>

        <p class="page-description">
            Theo dõi và điều phối bệnh nhân
            trong hàng đợi phòng khám.
        </p>
    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <form
            method="GET"
            action="{{ route('admin.queues.index') }}"
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
                        placeholder="Mã lượt khám, bệnh nhân..."
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
                        name="queue_status_id"
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

                        @foreach ($queueStatuses as $status)

                            <option
                                value="{{ $status->id }}"
                                @selected(
                                    (string) request('queue_status_id')
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
                        Loại hàng đợi
                    </label>

                    <select
                        name="queue_type"
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
                </div>


                <div>
                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Ngày
                    </label>

                    <input
                        type="date"
                        name="date"
                        value="{{ request('date') }}"
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
                    href="{{ route('admin.queues.index') }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>

    </div>


    <div class="card">

        @if ($queues->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th>STT</th>
                        <th>Lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Loại</th>
                        <th>Bác sĩ</th>
                        <th>Phòng</th>
                        <th>Trạng thái</th>
                        <th>Vào hàng</th>
                        <th>Số lần gọi</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach ($queues as $queue)

                        <tr>

                            <td>
                                <strong>
                                    {{ $queue->queue_number }}
                                </strong>
                            </td>

                            <td>
                                {{ $queue->medicalVisit?->visit_code ?: '—' }}
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
                                    {{ $queue->queue_type }}
                                @endif
                            </td>

                            <td>
                                {{ $queue->medicalVisit?->doctor?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ $queue->medicalVisit?->examinationRoom?->name ?: '—' }}
                            </td>

                            <td>
                                <span class="badge">
                                    {{ $queue->status?->name ?: '—' }}
                                </span>
                            </td>

                            <td>
                                {{ $queue->queued_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td>
                                {{ $queue->call_count }}
                            </td>

                            <td>
                                <a
                                    href="{{ route(
                                        'admin.queues.show',
                                        $queue
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
                {{ $queues->links() }}
            </div>

        @else

            <div style="
                padding: 30px;
                text-align: center;
                color: #64748b;
            ">
                Không có dữ liệu hàng đợi phù hợp.
            </div>

        @endif

    </div>

@endsection
