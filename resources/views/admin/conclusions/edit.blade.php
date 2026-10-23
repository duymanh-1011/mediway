@extends('admin.layouts.app')
@section('admin-page', 'conclusions-edit')

@section('title', 'Sửa kết luận lượt khám')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Vận hành khám</span>
    <span aria-hidden="true">/</span>
    <span>Kết luận</span>
    <span aria-hidden="true">/</span>
    <span>@include('admin.layouts.partials.record-code', ['code' => $conclusion->medicalVisit?->visit_code])</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Sửa</strong>
@endsection

@section('content')

    <div class="admin-u-ed1667a9">

        <h1 class="page-title">
            Sửa kết luận lượt khám
        </h1>

        <p class="page-description">
            Lượt khám:
            @include('admin.layouts.partials.record-code', ['code' => $conclusion->medicalVisit?->visit_code])
        </p>

    </div>


    <div class="card">

        <form novalidate
            method="POST"
            action="{{ route(
                'admin.conclusions.update',
                $conclusion
            ) }}"
        >

            @csrf
            @method('PUT')


            <div class="admin-u-6691f14f">

                <div>

                    <label class="admin-u-63101fce"
                        for="doctor_id"

                    >
                        Bác sĩ kết luận
                    </label>

                    <select class="admin-u-184ecf5a"
                        id="doctor_id"
                        name="doctor_id"
                        required

                    >

                        <option value="">
                            -- Chọn bác sĩ --
                        </option>

                        @foreach ($doctors as $doctor)

                            <option
                                value="{{ $doctor->id }}"
                                @selected(
                                    (string) old(
                                        'doctor_id',
                                        $conclusion->doctor_id
                                    )
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

                    @error('doctor_id')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="professional_conclusion"

                    >
                        Kết luận chuyên môn
                    </label>

                    <textarea class="admin-u-fb28156a"
                        id="professional_conclusion"
                        name="professional_conclusion"
                        rows="5"
                        required

                    >{{ old(
                        'professional_conclusion',
                        $conclusion->professional_conclusion
                    ) }}</textarea>

                    @error('professional_conclusion')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="disposition"

                    >
                        Hướng xử trí
                    </label>

                    <input class="admin-u-419e8d7e"
                        type="text"
                        id="disposition"
                        name="disposition"
                        list="disposition-options"
                        value="{{ old(
                            'disposition',
                            $conclusion->disposition
                        ) }}"

                    >

                    <datalist id="disposition-options">
                        @foreach ($dispositions as $disposition)
                            <option value="{{ $disposition }}">
                        @endforeach
                    </datalist>

                    @error('disposition')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="treatment_plan"

                    >
                        Kế hoạch điều trị
                    </label>

                    <textarea class="admin-u-fb28156a"
                        id="treatment_plan"
                        name="treatment_plan"
                        rows="4"

                    >{{ old(
                        'treatment_plan',
                        $conclusion->treatment_plan
                    ) }}</textarea>

                    @error('treatment_plan')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="instructions"

                    >
                        Dặn dò
                    </label>

                    <textarea class="admin-u-fb28156a"
                        id="instructions"
                        name="instructions"
                        rows="4"

                    >{{ old(
                        'instructions',
                        $conclusion->instructions
                    ) }}</textarea>

                    @error('instructions')
                        <div class="admin-u-6aa3ac13">
                            {{ \App\Models\AdminDisplay::text($message) }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label class="admin-u-63101fce">
                        Có tái khám
                    </label>

                    <select class="admin-u-184ecf5a"
                        id="has_follow_up"
                        name="has_follow_up"
                        required

                    >

                        <option
                            value="0"
                            @selected(
                                (string) old(
                                    'has_follow_up',
                                    (int) $conclusion->has_follow_up
                                )
                                ===
                                '0'
                            )
                        >
                            Không
                        </option>

                        <option
                            value="1"
                            @selected(
                                (string) old(
                                    'has_follow_up',
                                    (int) $conclusion->has_follow_up
                                )
                                ===
                                '1'
                            )
                        >
                            Có
                        </option>

                    </select>
@error('has_follow_up')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div id="follow-up-fields">

                    <div class="admin-u-2f23af6b">

                        <label class="admin-u-63101fce"
                            for="follow_up_date"

                        >
                            Ngày tái khám
                        </label>

                        <input class="admin-u-419e8d7e"
                            type="date"
                            id="follow_up_date"
                            name="follow_up_date"
                            value="{{ old(
                                'follow_up_date',
                                $conclusion->follow_up_date
                                    ? $conclusion->follow_up_date->format(
                                        'Y-m-d'
                                    )
                                    : ''
                            ) }}"

                        >

                        @error('follow_up_date')
                            <div class="admin-u-6aa3ac13">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>
                        @enderror

                    </div>


                    <div>

                        <label class="admin-u-63101fce"
                            for="follow_up_reason"

                        >
                            Lý do tái khám
                        </label>

                        <textarea class="admin-u-419e8d7e"
                            id="follow_up_reason"
                            name="follow_up_reason"
                            rows="3"

                        >{{ old(
                            'follow_up_reason',
                            $conclusion->follow_up_reason
                        ) }}</textarea>

                        @error('follow_up_reason')
                            <div class="admin-u-6aa3ac13">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>
                        @enderror

                    </div>

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="concluded_at"

                    >
                        Thời điểm kết luận
                    </label>

                    <input class="admin-u-419e8d7e"
                        type="datetime-local"
                        id="concluded_at"
                        name="concluded_at"
                        value="{{ old(
                            'concluded_at',
                            $conclusion->concluded_at
                                ? $conclusion->concluded_at->format(
                                    'Y-m-d\TH:i'
                                )
                                : ''
                        ) }}"

                    >
@error('concluded_at')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div>

                    <label class="admin-u-63101fce"
                        for="notes"

                    >
                        Ghi chú
                    </label>

                    <textarea class="admin-u-419e8d7e"
                        id="notes"
                        name="notes"
                        rows="4"

                    >{{ old(
                        'notes',
                        $conclusion->notes
                    ) }}</textarea>
@error('notes')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-u-5786f359">

                    <button
                        type="submit"
                        class="admin-btn admin-btn-primary"
                    >Cập nhật</button>

                    <a
                        href="{{ route(
                            'admin.conclusions.show',
                            $conclusion
                        ) }}"
                        class="admin-btn admin-btn-secondary"
                    >Hủy</a>

                </div>

            </div>

        </form>

    </div>

@endsection


@push('scripts')

<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const followUpSelect =
                document.getElementById(
                    'has_follow_up'
                );

            const followUpFields =
                document.getElementById(
                    'follow-up-fields'
                );

            function toggleFollowUpFields() {

                followUpFields.style.display =
                    followUpSelect.value === '1'
                        ? 'block'
                        : 'none';
            }

            toggleFollowUpFields();

            followUpSelect.addEventListener(
                'change',
                toggleFollowUpFields
            );
        }
    );

</script>

@endpush
