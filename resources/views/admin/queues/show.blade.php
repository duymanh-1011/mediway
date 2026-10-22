@extends('admin.layouts.app')
@section('admin-page', 'queues-show')

@section('title', 'Chi tiết hàng đợi')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Hàng đợi</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">#{{ $queue->queue_number }}</strong>
@endsection

@section('content')

    <div class="admin-u-e0f559a1">

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
            class="admin-btn admin-btn-secondary btn btn-secondary"
        >Quay lại</a>

    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <h2 class="admin-u-ec0f7ffa">
            Thông tin hàng đợi
        </h2>

        <div class="admin-u-5811d829">

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
                        {{ \App\Models\AdminDisplay::label($queue->queue_type) }}
                    @endif
                </div>
            </div>

            <div>
                <strong>Trạng thái</strong>

                <div>
                    <span class="badge">
                        {{ \App\Models\AdminDisplay::label($queue->status?->name ?: '—') }}
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


        <div class="admin-u-4f7f1ad5">
            <strong>Ghi chú</strong>

            <div class="admin-u-0872b0cf">
                {{ $queue->notes ?: 'Không có.' }}
            </div>
        </div>

    </div>


    <div
        class="admin-u-2f23af6b card"

    >

        <h2 class="admin-u-ec0f7ffa">
            Bệnh nhân và lượt khám
        </h2>

        <div class="admin-u-5811d829">

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
                    {{ \App\Models\AdminDisplay::label($queue->medicalVisit?->status?->name ?: '—') }}
                </div>
            </div>

        </div>


        @if ($queue->medicalVisit)

            <div class="admin-u-6840f0a6">

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

        <h2 class="admin-u-ec0f7ffa">
            Điều phối hàng đợi
        </h2>

        <div class="admin-u-b4534c93">

            @if (
                in_array(
                    $queue->status?->name,
                    ['WAITING', 'CALLED', 'SKIPPED'],
                    true
                )
            )

                <form novalidate
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

                <form novalidate
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

                <form novalidate
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

                <form novalidate
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

                <span class="admin-u-a6f48144">
                    Lượt chờ đã hoàn tất.
                </span>

            @endif

        </div>

    </div>

@endsection
