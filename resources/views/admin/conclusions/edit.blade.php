@extends('admin.layouts.app')

@section('title', 'Sửa kết luận lượt khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Kết luận /
        {{ $conclusion->medicalVisit?->visit_code }} /
        Sửa
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Sửa kết luận lượt khám
        </h1>

        <p class="page-description">
            Lượt khám:
            {{ $conclusion->medicalVisit?->visit_code }}
        </p>

    </div>


    <div class="card">

        <form
            method="POST"
            action="{{ route(
                'admin.conclusions.update',
                $conclusion
            ) }}"
        >

            @csrf
            @method('PUT')


            <div style="display: grid; gap: 18px;">

                <div>

                    <label
                        for="doctor_id"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Bác sĩ kết luận
                    </label>

                    <select
                        id="doctor_id"
                        name="doctor_id"
                        required
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: white;
                        "
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
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="professional_conclusion"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Kết luận chuyên môn
                    </label>

                    <textarea
                        id="professional_conclusion"
                        name="professional_conclusion"
                        rows="5"
                        required
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            resize: vertical;
                        "
                    >{{ old(
                        'professional_conclusion',
                        $conclusion->professional_conclusion
                    ) }}</textarea>

                    @error('professional_conclusion')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="disposition"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Hướng xử trí
                    </label>

                    <input
                        type="text"
                        id="disposition"
                        name="disposition"
                        list="disposition-options"
                        value="{{ old(
                            'disposition',
                            $conclusion->disposition
                        ) }}"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                    <datalist id="disposition-options">
                        @foreach ($dispositions as $disposition)
                            <option value="{{ $disposition }}">
                        @endforeach
                    </datalist>

                    @error('disposition')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="treatment_plan"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Kế hoạch điều trị
                    </label>

                    <textarea
                        id="treatment_plan"
                        name="treatment_plan"
                        rows="4"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            resize: vertical;
                        "
                    >{{ old(
                        'treatment_plan',
                        $conclusion->treatment_plan
                    ) }}</textarea>

                    @error('treatment_plan')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label
                        for="instructions"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Dặn dò
                    </label>

                    <textarea
                        id="instructions"
                        name="instructions"
                        rows="4"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            resize: vertical;
                        "
                    >{{ old(
                        'instructions',
                        $conclusion->instructions
                    ) }}</textarea>

                    @error('instructions')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Có tái khám
                    </label>

                    <select
                        id="has_follow_up"
                        name="has_follow_up"
                        required
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: white;
                        "
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

                </div>


                <div id="follow-up-fields">

                    <div style="margin-bottom: 18px;">

                        <label
                            for="follow_up_date"
                            style="
                                display: block;
                                margin-bottom: 6px;
                                font-weight: 700;
                            "
                        >
                            Ngày tái khám
                        </label>

                        <input
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
                            style="
                                width: 100%;
                                padding: 10px 12px;
                                border: 1px solid #e2e8f0;
                                border-radius: 8px;
                            "
                        >

                        @error('follow_up_date')
                            <div style="
                                margin-top: 5px;
                                color: #dc2626;
                            ">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div>

                        <label
                            for="follow_up_reason"
                            style="
                                display: block;
                                margin-bottom: 6px;
                                font-weight: 700;
                            "
                        >
                            Lý do tái khám
                        </label>

                        <textarea
                            id="follow_up_reason"
                            name="follow_up_reason"
                            rows="3"
                            style="
                                width: 100%;
                                padding: 10px 12px;
                                border: 1px solid #e2e8f0;
                                border-radius: 8px;
                            "
                        >{{ old(
                            'follow_up_reason',
                            $conclusion->follow_up_reason
                        ) }}</textarea>

                        @error('follow_up_reason')
                            <div style="
                                margin-top: 5px;
                                color: #dc2626;
                            ">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                <div>

                    <label
                        for="concluded_at"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Thời điểm kết luận
                    </label>

                    <input
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
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                </div>


                <div>

                    <label
                        for="notes"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Ghi chú
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >{{ old(
                        'notes',
                        $conclusion->notes
                    ) }}</textarea>

                </div>


                <div style="
                    display: flex;
                    gap: 8px;
                ">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Lưu thay đổi
                    </button>

                    <a
                        href="{{ route(
                            'admin.conclusions.show',
                            $conclusion
                        ) }}"
                        class="btn btn-secondary"
                    >
                        Hủy
                    </a>

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
