@extends('admin.layouts.app')

@section('title', 'Chi tiết hàng đợi')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Hàng đợi /
        #{{ $queue->queue_number }}
    </div>
@endsection

@section('content')

    <div style="
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px;
    ">

        <div>
            <h1 class="page-title">
                Hàng đợi #{{ $queue->queue_number }}
            </h1>

            <p class="page-description">
                Theo dõi lịch sử và điều phối lượt chờ.
            </p>
        </div>

        <a
            href="{{ route('admin.queues.index') }}"
            class="btn btn-secondary"
        >
            Quay lại
        </a>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <h2 style="margin-top: 0;">
            Thông tin hàng đợi
        </h2>

        <div style="
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        ">

            <div>
                <strong>Số thứ tự</strong>
                <div>{{ $queue->queue_number }}</div>
            </div>

            <div>
                <strong>Loại</strong>

                <div>
                    @if ($queue->queue_type === 'INITIAL_EXAM')
                        Khám ban đầu
                    @elseif ($queue->queue_type === 'RETURN_AFTER_SERVICE')
                        Quay lại sau dịch vụ
                    @else
                        {{ $queue->queue_type }}
                    @endif
                </div>
            </div>

            <div>
                <strong>Trạng thái</strong>

                <div>
                    <span class="badge">
                        {{ $queue->status?->name ?: '—' }}
                    </span>
                </div>
            </div>

            <div>
                <strong>Số lần gọi</strong>
                <div>{{ $queue->call_count }}</div>
            </div>

            <div>
                <strong>Vào hàng</strong>

                <div>
                    {{ $queue->queued_at?->format(
                        'd/m/Y H:i:s'
                    ) ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Được gọi</strong>

                <div>
                    {{ $queue->called_at?->format(
                        'd/m/Y H:i:s'
                    ) ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bắt đầu phục vụ</strong>

                <div>
                    {{ $queue->service_started_at?->format(
                        'd/m/Y H:i:s'
                    ) ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Hoàn tất</strong>

                <div>
                    {{ $queue->completed_at?->format(
                        'd/m/Y H:i:s'
                    ) ?: '—' }}
                </div>
            </div>

        </div>


        <div style="margin-top: 20px;">
            <strong>Ghi chú</strong>

            <div style="
                margin-top: 6px;
                white-space: pre-line;
            ">
                {{ $queue->notes ?: 'Không có.' }}
            </div>
        </div>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <h2 style="margin-top: 0;">
            Bệnh nhân và lượt khám
        </h2>

        <div style="
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        ">

            <div>
                <strong>Mã lượt khám</strong>

                <div>
                    {{ $queue->medicalVisit?->visit_code ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bệnh nhân</strong>

                <div>
                    {{ $queue->medicalVisit?->patient?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Bác sĩ</strong>

                <div>
                    {{ $queue->medicalVisit?->doctor?->full_name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Chuyên khoa</strong>

                <div>
                    {{ $queue->medicalVisit?->specialty?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Phòng khám</strong>

                <div>
                    {{ $queue->medicalVisit?->examinationRoom?->name ?: '—' }}
                </div>
            </div>

            <div>
                <strong>Trạng thái lượt khám</strong>

                <div>
                    {{ $queue->medicalVisit?->status?->name ?: '—' }}
                </div>
            </div>

        </div>


        @if ($queue->medicalVisit)

            <div style="margin-top: 18px;">

                <a
                    href="{{ route(
                        'admin.visits.show',
                        $queue->medicalVisit
                    ) }}"
                    class="btn btn-secondary"
                >
                    Xem lượt khám
                </a>

            </div>

        @endif

    </div>


    <div class="card">

        <h2 style="margin-top: 0;">
            Điều phối hàng đợi
        </h2>

        <div style="
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        ">

            @if (
                in_array(
                    $queue->status?->name,
                    ['WAITING', 'CALLED', 'SKIPPED'],
                    true
                )
            )

                <form
                    method="POST"
                    action="{{ route(
                        'admin.queues.call',
                        $queue
                    ) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        {{ $queue->status?->name === 'CALLED'
                            ? 'Gọi lại'
                            : 'Gọi bệnh nhân' }}
                    </button>
                </form>

            @endif


            @if ($queue->status?->name === 'CALLED')

                <form
                    method="POST"
                    action="{{ route(
                        'admin.queues.start-service',
                        $queue
                    ) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Bắt đầu phục vụ
                    </button>
                </form>

            @endif


            @if ($queue->status?->name === 'IN_SERVICE')

                <form
                    method="POST"
                    action="{{ route(
                        'admin.queues.complete',
                        $queue
                    ) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Hoàn tất
                    </button>
                </form>

            @endif


            @if (
                in_array(
                    $queue->status?->name,
                    ['WAITING', 'CALLED'],
                    true
                )
            )

                <form
                    method="POST"
                    action="{{ route(
                        'admin.queues.skip',
                        $queue
                    ) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-danger"
                        onclick="
                            return confirm(
                                'Xác nhận bỏ qua bệnh nhân này?'
                            );
                        "
                    >
                        Bỏ qua
                    </button>
                </form>

            @endif


            @if (
                in_array(
                    $queue->status?->name,
                    ['COMPLETED'],
                    true
                )
            )

                <span style="color: #64748b;">
                    Lượt chờ đã hoàn tất.
                </span>

            @endif

        </div>

    </div>

@endsection
