@extends('admin.layouts.app')

@section('title', 'Kết luận lượt khám')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard / Vận hành khám / Kết luận
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Kết luận lượt khám
        </h1>

        <p class="page-description">
            Tra cứu kết luận chuyên môn,
            hướng xử trí và lịch tái khám.
        </p>

    </div>


    <div
        class="card"
        style="margin-bottom: 18px;"
    >

        <form
            method="GET"
            action="{{ route('admin.conclusions.index') }}"
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
                        placeholder="Visit, bệnh nhân, kết luận..."
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
                        Hướng xử trí
                    </label>

                    <select
                        name="disposition"
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

                        @foreach ($dispositions as $disposition)

                            <option
                                value="{{ $disposition }}"
                                @selected(
                                    request('disposition')
                                    ===
                                    $disposition
                                )
                            >
                                {{ $disposition }}
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
                        Tái khám
                    </label>

                    <select
                        name="has_follow_up"
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
                            value="1"
                            @selected(
                                request('has_follow_up') === '1'
                            )
                        >
                            Có tái khám
                        </option>

                        <option
                            value="0"
                            @selected(
                                request('has_follow_up') === '0'
                            )
                        >
                            Không tái khám
                        </option>

                    </select>

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Từ ngày
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
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
                        Đến ngày
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

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
                    href="{{ route('admin.conclusions.index') }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>

    </div>


    <div class="card">

        @if ($conclusions->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th>Lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Kết luận</th>
                        <th>Xử trí</th>
                        <th>Tái khám</th>
                        <th>Thời gian</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>


                    <tbody>

                    @foreach ($conclusions as $conclusion)

                        <tr>

                            <td>
                                <strong>
                                    {{ $conclusion->medicalVisit?->visit_code ?: '—' }}
                                </strong>
                            </td>

                            <td>
                                {{ $conclusion->medicalVisit?->patient?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ $conclusion->doctor?->full_name ?: '—' }}
                            </td>

                            <td>
                                {{ \Illuminate\Support\Str::limit(
                                    $conclusion->professional_conclusion,
                                    70
                                ) }}
                            </td>

                            <td>
                                {{ $conclusion->disposition ?: '—' }}
                            </td>

                            <td>

                                @if ($conclusion->has_follow_up)

                                    <span class="badge badge-warning">
                                        Có
                                    </span>

                                    @if ($conclusion->follow_up_date)
                                        <div style="
                                            margin-top: 4px;
                                            font-size: 12px;
                                        ">
                                            {{ $conclusion
                                                ->follow_up_date
                                                ->format('d/m/Y') }}
                                        </div>
                                    @endif

                                @else

                                    <span class="badge">
                                        Không
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ $conclusion->concluded_at?->format(
                                    'd/m/Y H:i'
                                ) ?: '—' }}
                            </td>

                            <td>

                                <div style="
                                    display: flex;
                                    flex-wrap: wrap;
                                    gap: 6px;
                                ">

                                    <a
                                        href="{{ route(
                                            'admin.conclusions.show',
                                            $conclusion
                                        ) }}"
                                        class="btn btn-secondary"
                                    >
                                        Chi tiết
                                    </a>


                                    @if (
                                        $conclusion->medicalVisit?->status?->name
                                        !==
                                        'COMPLETED'
                                    )

                                        <a
                                            href="{{ route(
                                                'admin.conclusions.edit',
                                                $conclusion
                                            ) }}"
                                            class="btn btn-primary"
                                        >
                                            Sửa
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div style="margin-top: 18px;">
                {{ $conclusions->links() }}
            </div>

        @else

            <div style="
                padding: 30px;
                text-align: center;
                color: #64748b;
            ">
                Không tìm thấy kết luận phù hợp.
            </div>

        @endif

    </div>

@endsection
