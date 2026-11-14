@extends('doctor.layout')

@inject('queueWorkflow', 'App\Services\Doctor\ExaminationQueueWorkflow')

@section('title', 'Hàng đợi khám')

@include('doctor.examination.assets')

@section('content')
    @php
        $filterValues = [
            'date' => $date,
            'q' => $filters['q'] ?? '',
            'status' => $filters['status'] ?? '',
            'shift' => $filters['shift'] ?? '',
        ];

        $refreshUrl = route(
            'doctor.examination.rooms.queue',
            array_merge(
                ['room' => $room],
                array_filter(
                    $filterValues,
                    static fn($value) => $value !== ''
                        && $value !== null
                )
            )
        );

        $actionLabels = [
            'call' => 'Gọi bệnh nhân',
            'recall' => 'Gọi lại',
            'skip' => 'Bỏ qua',
            'start' => 'Bắt đầu khám',
        ];
    @endphp

    <section class="employee-section reception-work doctor-examination d01-queue">

        <a class="employee-card__link doctor-examination__back" href="{{ route('doctor.examination.rooms.index') }}">
            ← Danh sách phòng
        </a>

        <div class="employee-section__heading">
            <div>
                <h1>{{ $room->name }}</h1>
                <p>Hàng đợi của bạn</p>
            </div>

            <a class="portal-btn portal-btn--secondary" href="{{ $refreshUrl }}">
                Làm mới
            </a>
        </div>

        @unless($canOperate)
            <p class="portal-alert portal-alert--info" role="status">Tài khoản chưa có quyền thao tác hàng đợi. Bạn đang xem ở chế độ chỉ đọc.</p>
        @endunless

        {{-- BỘ LỌC --}}
        <form action="{{ route('doctor.examination.rooms.queue', $room) }}" method="get"
            class="employee-card employee-filter" aria-label="Lọc hàng đợi khám" autocomplete="off" data-queue-filter>
            <div class="employee-filter__field">
                <label for="queue-date">Ngày tiếp nhận</label>

                <input id="queue-date" class="form-control" type="date" name="date" value="{{ $filterValues['date'] }}"
                    data-default-value="{{ $filterValues['date'] }}">
            </div>

            <div class="employee-filter__field">
                <label for="queue-search">
                    Tên bệnh nhân hoặc số điện thoại
                </label>

                <input id="queue-search" class="form-control" type="search" name="q" maxlength="100"
                    placeholder="Nhập tên hoặc số điện thoại..." value="{{ $filterValues['q'] }}"
                    data-default-value="{{ $filterValues['q'] }}">
            </div>

            {{-- SÁU TRẠNG THÁI --}}
            <div class="employee-filter__field">
                <label for="queue-status">Trạng thái</label>

                <select id="queue-status" class="form-control" name="status"
                    data-default-value="{{ $filterValues['status'] }}">
                    <option value="">Tất cả trạng thái</option>

                    @foreach ($examinationStatusLabels as $code => $label)
                        <option value="{{ $code }}" @selected(
                            (string) $filterValues['status']
                            === (string) $code
                        )>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="employee-filter__field">
                <label for="queue-shift">Ca lịch hẹn</label>

                <select id="queue-shift" class="form-control" name="shift"
                    data-default-value="{{ $filterValues['shift'] }}">
                    <option value="">
                        Tất cả, gồm cả lượt đến trực tiếp
                    </option>

                    @foreach ($shifts as $shift)
                                <option value="{{ $shift }}" @selected(
                                    (string) $filterValues['shift']
                                    === (string) $shift
                                )>
                                    {{
                        [
                            'MORNING' => 'Sáng',
                            'AFTERNOON' => 'Chiều',
                        ][$shift] ?? $shift
                                            }}
                                </option>
                    @endforeach
                </select>
            </div>

            <div class="reception-actions">
                <x-portal.button type="submit">
                    Lọc hàng đợi
                </x-portal.button>

                <a class="portal-btn portal-btn--secondary" href="{{ route('doctor.examination.rooms.queue', $room) }}">
                    Xóa bộ lọc
                </a>
            </div>
        </form>

        {{-- DANH SÁCH HÀNG ĐỢI --}}
        <section class="employee-card doctor-examination__results" aria-labelledby="queue-list-title">
            <div class="employee-section__heading">
                <h2 id="queue-list-title">Hàng đợi khám</h2>
            </div>

            <div class="employee-table-wrap d01-table-wrap" tabindex="0" role="region" aria-label="Hàng đợi phòng khám">
                <table class="employee-table d01-table">
                    <thead>
                        <tr>
                            <th scope="col">Số khám</th>
                            <th scope="col">Bệnh nhân</th>
                            <th scope="col">Tiếp nhận</th>
                            <th scope="col">Bác sĩ</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($queues as $queue)
                            @php
                                $visit = $queue->medicalVisit;

                                $queueStatus = $queue->status?->name;
                                $visitStatus = $visit?->status?->name;

                                $businessStatus = $visit?->displayStatus();

                                $examinationStatus = $queue->examination_stage;

                                $statusLabel = $examinationStatusLabels[$examinationStatus] ?? 'Chưa xác định';

                                $waiting = $visit
                                    && (
                                        $queue->queue_type === 'RETURN_AFTER_SERVICE'
                                        ? $visitStatus === 'WAITING_FOR_SERVICE'
                                        : (
                                            $visitStatus === 'WAITING'
                                            && !$visit->examination_started_at
                                        )
                                    );

                                $canChangeQueue = $canOperate && $visit
                                    && in_array($examinationStatus, ['WAITING', 'CALLED', 'RETURNING'], true)
                                    && $room->status === 'ACTIVE'
                                    && $waiting
                                    && (bool) ($queue->status?->allows_transition)
                                    && (bool) ($queue->status?->is_active)
                                    && $businessStatus !== 'WAITING_FOR_PAYMENT';

                                $canCompleteVisit = $canOperate && $visit
                                    && ($canComplete ?? false)
                                    && $room->status === 'ACTIVE'
                                    && $queue->id === (int) $visit->latest_examination_queue_id
                                    && in_array(
                                        $queueStatus,
                                        ['IN_SERVICE', 'COMPLETED'],
                                        true
                                    )
                                    && $visit->examination_started_at
                                    && !$visit->examination_ended_at
                                    && in_array(
                                        $visitStatus,
                                        ['IN_EXAMINATION', 'RETURNING'],
                                        true
                                    );
                            @endphp

                            <tr>
                                <td>
                                    <strong class="doctor-examination__number">
                                        {{ $queue->queue_number }}
                                    </strong>
                                </td>

                                <td class="doctor-examination__patient">
                                    <strong class="d01-patient-name">
                                        {{ $visit?->patient?->full_name ?? '—' }}
                                    </strong>

                                </td>

                                <td>
                                    {{ $visit?->checked_in_at?->format('H:i d/m/Y') ?? '—' }}
                                </td>

                                <td>
                                    {{ $visit?->doctor?->full_name ?? '—' }}
                                </td>

                                {{-- SÁU TRẠNG THÁI --}}
                                <td>
                                    <span class="d01-status d01-status--{{ $examinationStatus ?? 'unknown' }}">
                                        {{ $statusLabel }}
                                    </span>

                                    @if ($examinationStatus === 'WAITING' && $queueStatus === 'SKIPPED')
                                        <small class="d01-muted">Đã bỏ qua lượt gọi</small>
                                    @endif

                                    @if ($businessStatus === 'WAITING_FOR_PAYMENT')
                                        <small class="d01-muted">Chưa hoàn tất thanh toán</small>
                                    @endif
                                </td>

                                {{-- THAO TÁC --}}
                                <td>
                                    <div class="d01-actions">

                                        @if($canOperate && $canStart && $examinationStatus === 'COMPLETED')
                                            <x-portal.button variant="secondary" :href="route('doctor.examination.visits.show', ['visit' => $visit])">
                                                Xem chi tiết
                                            </x-portal.button>
                                        @elseif($canChangeQueue)
                                            @php
                                                $phase = $queueWorkflow->phaseToken($queue);
                                            @endphp
                                            @foreach($actions as $action => $transitions)
                                                @continue(!isset($transitions[$queueStatus]) || ($action === 'start' && !$canStart))
                                                @php
                                                    $actionRoute = $action === 'start'
                                                        ? 'doctor.examination.visits.start'
                                                        : 'doctor.examination.queue.'.$action;
                                                @endphp
                                                @include('doctor.examination.confirm', [
                                                    'dialogId' => $action.'-'.$queue->id,
                                                    'label' => $actionLabels[$action],
                                                    'heading' => $action === 'call' ? 'Xác nhận gọi bệnh nhân' : null,
                                                    'submitLabel' => $action === 'call' ? 'Xác nhận gọi' : 'Xác nhận',
                                                    'url' => route($actionRoute, [$room, $queue]),
                                                    'expected' => [
                                                        'expected_status' => $queueStatus,
                                                        'expected_calls' => $queue->call_count,
                                                        'expected_phase' => $phase,
                                                    ],
                                                ])
                                            @endforeach
                                            @if($queueStatus === 'CALLED' && !$canStart)
                                                <small class="d01-muted">Chưa tích hợp trang khám bệnh</small>
                                            @endif
                                        @elseif($canCompleteVisit)
                                            @include('doctor.examination.confirm', [
                                                'dialogId' => 'complete-'.$queue->id,
                                                'label' => 'Hoàn tất lượt khám',
                                                'url' => route('doctor.examination.visits.complete', [$room, $visit]),
                                                'expected' => [],
                                            ])
                                        @else
                                            <span class="d01-muted">Không có thao tác khả dụng</span>
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="doctor-examination__empty">
                                    Không có lượt khám phù hợp
                                    phù hợp với bộ lọc.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-portal.pagination :paginator="$queues" />

        </section>
    </section>

@endsection
