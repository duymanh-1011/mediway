@extends('doctor.layout')

@inject('queueWorkflow', 'App\Services\Doctor\ExaminationQueueWorkflow')

@section('title', 'Hàng đợi khám')

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

        $labels = [
            'call' => 'Gọi bệnh nhân',
            'recall' => 'Gọi lại',
            'skip' => 'Bỏ qua',
            'start' => 'Bắt đầu khám',
        ];
    @endphp

    <section class="employee-section reception-work doctor-examination">

        {{-- QUAY LẠI --}}
        <a class="employee-card__link doctor-examination__back" href="{{ route('doctor.examination.rooms.index') }}">
            ← Danh sách phòng
        </a>

        {{-- TIÊU ĐỀ --}}
        <div class="employee-section__heading">
            <div>
                <h1>{{ $room->name }}</h1>

                <p>
                    {{ $room->room_code }}
                    · Hàng đợi của bạn
                </p>
            </div>

            <a class="portal-btn portal-btn--secondary" href="{{ $refreshUrl }}">
                Làm mới
            </a>
        </div>

        @if ($room->status !== 'ACTIVE')
            <div class="portal-alert portal-alert--warning" role="status">
                Phòng đang ngừng hoạt động.
                Bạn có thể xem lịch sử hàng đợi.
            </div>
        @endif

        {{-- BỘ LỌC --}}
        <form action="{{ route('doctor.examination.rooms.queue', $room) }}" method="get"
            class="employee-card employee-filter" data-queue-filter autocomplete="off">
            <div class="employee-filter__field">
                <label for="queue-date">Ngày tiếp nhận</label>

                <input id="queue-date" class="form-control" type="date" name="date" value="{{ $filterValues['date'] }}">
            </div>

            <div class="employee-filter__field">
                <label for="queue-search">
                    Bệnh nhân hoặc mã lượt khám
                </label>

                <input id="queue-search" class="form-control" type="search" name="q" maxlength="100"
                    value="{{ $filterValues['q'] }}">
            </div>

            <div class="employee-filter__field">
                <label for="queue-status">Trạng thái hàng đợi</label>

                <select id="queue-status" class="form-control" name="status">
                    <option value="">Tất cả trạng thái</option>

                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected(
                            (string) $filterValues['status']
                            === (string) $status->id
                        )>
                            {{ $status->description }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="employee-filter__field">
                <label for="queue-shift">Ca lịch hẹn</label>

                <select id="queue-shift" class="form-control" name="shift">
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

            <div class="employee-table-wrap" tabindex="0" role="region" aria-label="Hàng đợi phòng khám">
                <table class="employee-table">

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
                                            $displayStatus = $visit->displayStatus();

                                            $waiting = $queue->queue_type
                                                === 'RETURN_AFTER_SERVICE'
                                                ? $visitStatus === 'WAITING_FOR_SERVICE'
                                                : (
                                                    $visitStatus === 'WAITING'
                                                    && !$visit?->examination_started_at
                                                );

                                            $phase = $queueWorkflow->phaseToken($queue);
                                        @endphp

                                        <tr>
                                            {{-- SỐ KHÁM --}}
                                            <td>
                                                <strong class="doctor-examination__number">
                                                    {{ $queue->queue_number }}
                                                </strong>
                                            </td>

                                            {{-- BỆNH NHÂN --}}
                                            <td class="doctor-examination__patient">
                                                <strong>
                                                    {{ $visit?->patient?->full_name ?? '—' }}
                                                </strong>
                                                <br>{{ $visit->visit_code }}
                                            </td>

                                            {{-- TIẾP NHẬN --}}
                                            <td>
                                                {{
                            $visit?->checked_in_at
                                    ?->format('H:i d/m/Y')
                            ?? '—'
                                                    }}
                                            </td>

                                            {{-- BÁC SĨ --}}
                                            <td>
                                                {{ $visit?->doctor?->full_name ?? '—' }}
                                            </td>

                                            {{-- TRẠNG THÁI --}}
                                            <td>
                                                <x-portal.badge :type="\App\Models\AdminDisplay::badge($queueStatus)">
                                                    {{
                            $queue->status?->description
                            ?? 'Chưa xác định'
                                                        }}
                                                </x-portal.badge>
                                                @if($displayStatus && $displayStatus !== $queueStatus)
                                                    <br><small>{{ \App\Models\AdminDisplay::label($displayStatus) }}</small>
                                                @endif
                                            </td>

                                            {{-- THAO TÁC --}}
                                            <td>
                                                <div class="reception-actions doctor-examination__row-actions">
                                                    @if($canStart && $visit->examination_started_at)
                                                        <a class="employee-card__link" href="{{ route('doctor.examination.visits.show', $visit) }}">
                                                            Xem chi tiết
                                                        </a>
                                                    @endif

                                                    @if (
                                                            $visit
                                                            && $room->status === 'ACTIVE'
                                                            && $waiting
                                                            && $queue->status?->allows_transition
                                                            && $queue->status?->is_active
                                                            && $displayStatus !== 'WAITING_FOR_PAYMENT'
                                                        )

                                                        @foreach (($actions ?? []) as $action => $transitions)

                                                            @continue(
                                                                !isset($labels[$action])
                                                                || !isset($transitions[$queueStatus])
                                                                || (
                                                                    $action === 'start'
                                                                    && !($canStart ?? false)
                                                                )
                                                            )

                                                            @include(
                                                                'doctor.examination.confirm',
                                                                [
                                                                    'dialogId' => $action . '-' . $queue->id,

                                                                    'label' => $labels[$action],

                                                                    'url' => route(
                                                                        $action === 'start'
                                                                        ? 'doctor.examination.visits.start'
                                                                        : 'doctor.examination.queue.' . $action,
                                                                        [$room, $queue]
                                                                    ),

                                                                    'expected' => [
                                                                        'expected_status' => $queueStatus,
                                                                        'expected_calls' => $queue->call_count,
                                                                        'expected_phase' => $phase,
                                                                    ],
                                                                ]
                                                            )

                                                        @endforeach
                                                    @endif

                                                    {{-- HOÀN TẤT KHÁM --}}
                                                    @if (
                                                            $visit
                                                            && $canComplete
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
                                                            )
                                                        )

                                                        @include(
                                                            'doctor.examination.confirm',
                                                            [
                                                                'dialogId' => 'complete-' . $queue->id,
                                                                'label' => 'Hoàn tất lượt khám',
                                                                'url' => route(
                                                                    'doctor.examination.visits.complete',
                                                                    [$room, $visit]
                                                                ),
                                                                'expected' => [],
                                                            ]
                                                        )

                                                    @endif

                                                </div>
                                            </td>
                                        </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="doctor-examination__empty">
                                    Không có lượt khám được phân công
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
