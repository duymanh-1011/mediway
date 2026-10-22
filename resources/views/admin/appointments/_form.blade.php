@php

    $selectedPatientId = old(
        'patient_id',
        isset($appointment)
            ? $appointment->patient_id
            : ''
    );

    $selectedSlotId = old(
        'appointment_time_slot_id',
        isset($appointment)
            ? $appointment->appointment_time_slot_id
            : ''
    );

    $selectedScheduleId = '';

    if (
        isset($appointment)
        &&
        $appointment->appointmentTimeSlot
    ) {
        $selectedScheduleId =
            $appointment
                ->appointmentTimeSlot
                ->doctor_schedule_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Khi validation lỗi ở trang Create
    |--------------------------------------------------------------------------
    |
    | Form chỉ gửi appointment_time_slot_id,
    | nên tìm lại schedule chứa slot đã chọn.
    |
    */

    if (
        old('appointment_time_slot_id')
        &&
        !$selectedScheduleId
    ) {

        foreach ($schedules as $schedule) {

            foreach (
                $schedule->appointmentTimeSlots
                as $slot
            ) {

                if (
                    (string) $slot->id
                    ===
                    (string) old(
                        'appointment_time_slot_id'
                    )
                ) {
                    $selectedScheduleId =
                        $schedule->id;

                    break 2;
                }
            }
        }
    }

@endphp


<div class="form-grid">

    {{-- Bệnh nhân --}}
    <div class="form-group">

        <label
            for="patient_id"
            class="form-label"
        >
            Bệnh nhân

            <span class="required">
                *
            </span>
        </label>


        <select
            id="patient_id"
            name="patient_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn bệnh nhân --
            </option>


            @foreach ($patients as $patient)

                <option
                    value="{{ $patient->id }}"

                    @selected(
                        (string) $selectedPatientId
                        ===
                        (string) $patient->id
                    )
                >

                    {{ $patient->patient_code }}

                    -

                    {{ $patient->full_name }}

                </option>

            @endforeach

        </select>


        @error('patient_id')

            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>

        @enderror

    </div>


    {{-- Lịch làm việc bác sĩ --}}
    <div class="form-group">

        <label
            for="doctor_schedule_id"
            class="form-label"
        >
            Lịch làm việc bác sĩ

            <span class="required">
                *
            </span>
        </label>


        <select
            id="doctor_schedule_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn lịch làm việc --
            </option>


            @foreach ($schedules as $schedule)

                <option
                    value="{{ $schedule->id }}"

                    @selected(
                        (string) $selectedScheduleId
                        ===
                        (string) $schedule->id
                    )
                >

                    {{ $schedule
                        ->work_date
                        ->format('d/m/Y') }}

                    |

                    {{ $schedule
                        ->doctor
                        ?->doctor_code }}

                    -

                    {{ $schedule
                        ->doctor
                        ?->full_name }}

                    |

                    {{ $schedule
                        ->specialty
                        ?->name }}

                    |

                    {{ $schedule
                        ->examinationRoom
                        ?->room_code }}

                </option>

            @endforeach

        </select>

    </div>


    {{-- Khung giờ --}}
    <div class="form-group">

        <label
            for="appointment_time_slot_id"
            class="form-label"
        >
            Khung giờ khám

            <span class="required">
                *
            </span>
        </label>


        <select
            id="appointment_time_slot_id"
            name="appointment_time_slot_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn lịch làm việc trước --
            </option>


            @foreach ($schedules as $schedule)

                @foreach (
                    $schedule->appointmentTimeSlots
                    as $slot
                )

                    @if ($slot->is_selectable)

                        @php

                            $remaining =
                                max(
                                    0,
                                    (int) $slot->max_patients
                                    -
                                    (int) $slot->booked_count
                                );

                        @endphp


                        <option
                            value="{{ $slot->id }}"

                            data-schedule-id="{{ $schedule->id }}"

                            @selected(
                                (string) $selectedSlotId
                                ===
                                (string) $slot->id
                            )
                        >

                            {{ substr(
                                $slot->start_time,
                                0,
                                5
                            ) }}

                            -

                            {{ substr(
                                $slot->end_time,
                                0,
                                5
                            ) }}

                            |

                            Đã đặt:
                            {{ $slot->booked_count }}
                            /
                            {{ $slot->max_patients }}

                            |

                            Còn:
                            {{ $remaining }}

                        </option>

                    @endif

                @endforeach

            @endforeach

        </select>


        @error('appointment_time_slot_id')

            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>

        @enderror

    </div>

</div>


{{-- Lý do khám --}}
<div class="form-group">

    <label
        for="reason_for_visit"
        class="form-label"
    >
        Lý do khám

        <span class="required">
            *
        </span>
    </label>


    <textarea
        id="reason_for_visit"
        name="reason_for_visit"
        class="form-control"
        rows="4"
        placeholder="Nhập lý do bệnh nhân đến khám..."
        required
    >{{ old(
        'reason_for_visit',
        isset($appointment)
            ? $appointment->reason_for_visit
            : ''
    ) }}</textarea>


    @error('reason_for_visit')

        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>

    @enderror

</div>


{{-- Ghi chú --}}
<div class="form-group">

    <label
        for="patient_notes"
        class="form-label"
    >
        Ghi chú bệnh nhân
    </label>


    <textarea
        id="patient_notes"
        name="patient_notes"
        class="form-control"
        rows="3"
        placeholder="Ghi chú thêm nếu có..."
    >{{ old(
        'patient_notes',
        isset($appointment)
            ? $appointment->patient_notes
            : ''
    ) }}</textarea>


    @error('patient_notes')

        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>

    @enderror

</div>


<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const scheduleSelect =
                document.getElementById(
                    'doctor_schedule_id'
                );

            const slotSelect =
                document.getElementById(
                    'appointment_time_slot_id'
                );


            if (
                !scheduleSelect
                ||
                !slotSelect
            ) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Lưu toàn bộ option slot
            |--------------------------------------------------------------------------
            */

            const allSlotOptions =
                Array.from(
                    slotSelect.querySelectorAll(
                        'option[data-schedule-id]'
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | Lọc slot theo schedule
            |--------------------------------------------------------------------------
            */

            function filterSlots(
                keepSelected
            ) {

                const scheduleId =
                    scheduleSelect.value;

                let currentSelectedSlot =
                    '';

                if (keepSelected) {
                    currentSelectedSlot =
                        slotSelect.value;
                }


                /*
                |--------------------------------------------------------------------------
                | Xóa toàn bộ option hiện tại
                |--------------------------------------------------------------------------
                */

                slotSelect.innerHTML = '';


                /*
                |--------------------------------------------------------------------------
                | Option mặc định
                |--------------------------------------------------------------------------
                */

                const defaultOption =
                    document.createElement(
                        'option'
                    );

                defaultOption.value = '';


                if (!scheduleId) {

                    defaultOption.textContent =
                        '-- Chọn lịch làm việc trước --';

                } else {

                    defaultOption.textContent =
                        '-- Chọn khung giờ --';

                }


                slotSelect.appendChild(
                    defaultOption
                );


                /*
                |--------------------------------------------------------------------------
                | Chưa chọn schedule
                |--------------------------------------------------------------------------
                */

                if (!scheduleId) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Lấy slot đúng schedule
                |--------------------------------------------------------------------------
                */

                const matchingSlots =
                    allSlotOptions.filter(
                        function (option) {

                            return String(
                                option.dataset.scheduleId
                            )
                            ===
                            String(scheduleId);
                        }
                    );


                if (
                    matchingSlots.length
                    ===
                    0
                ) {

                    defaultOption.textContent =
                        '-- Không còn khung giờ khả dụng --';

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Thêm slot vào select
                |--------------------------------------------------------------------------
                */

                matchingSlots.forEach(
                    function (originalOption) {

                        const option =
                            originalOption.cloneNode(
                                true
                            );


                        if (
                            currentSelectedSlot
                            &&
                            String(option.value)
                            ===
                            String(currentSelectedSlot)
                        ) {
                            option.selected = true;
                        }


                        slotSelect.appendChild(
                            option
                        );

                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Lần đầu mở trang
            |--------------------------------------------------------------------------
            |
            | Nếu Edit hoặc validation quay lại,
            | Blade đã selected schedule và slot.
            |
            */

            const initialSelectedSlot =
                slotSelect.value;


            filterSlots(false);


            if (initialSelectedSlot) {

                const option =
                    allSlotOptions.find(
                        function (item) {

                            return String(
                                item.value
                            )
                            ===
                            String(
                                initialSelectedSlot
                            );
                        }
                    );


                if (option) {

                    scheduleSelect.value =
                        option.dataset.scheduleId;


                    filterSlots(false);


                    slotSelect.value =
                        initialSelectedSlot;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Khi đổi schedule
            |--------------------------------------------------------------------------
            */

            scheduleSelect.addEventListener(
                'change',
                function () {

                    filterSlots(false);

                }
            );

        }
    );

</script>
