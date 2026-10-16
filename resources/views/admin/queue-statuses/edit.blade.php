@extends('admin.layouts.app')

@section('title', 'Sửa trạng thái hàng đợi')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard /
        Trạng thái hàng đợi /
        {{ $queueStatus->name }} /
        Sửa
    </div>
@endsection

@section('content')

    <div style="margin-bottom: 22px;">

        <h1 class="page-title">
            Sửa trạng thái hàng đợi
        </h1>

        <p class="page-description">
            Cập nhật cấu hình trạng thái
            {{ $queueStatus->name }}.
        </p>

    </div>


    <div class="card">

        <form
            method="POST"
            action="{{ route(
                'admin.queue-statuses.update',
                $queueStatus
            ) }}"
        >

            @csrf
            @method('PUT')


            <div style="
                display: grid;
                gap: 18px;
            ">

                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Tên trạng thái
                    </label>

                    <input
                        type="text"
                        value="{{ $queueStatus->name }}"
                        disabled
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #f8fafc;
                        "
                    >

                    <small style="color: #64748b;">
                        Tên trạng thái là khóa nghiệp vụ và không được thay đổi.
                    </small>

                </div>


                <div>

                    <label
                        for="description"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Mô tả
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >{{ old(
                        'description',
                        $queueStatus->description
                    ) }}</textarea>

                    @error('description')
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
                        for="process_order"
                        style="
                            display: block;
                            margin-bottom: 6px;
                            font-weight: 700;
                        "
                    >
                        Thứ tự xử lý
                    </label>

                    <input
                        type="number"
                        id="process_order"
                        name="process_order"
                        min="0"
                        value="{{ old(
                            'process_order',
                            $queueStatus->process_order
                        ) }}"
                        required
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                    @error('process_order')
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
                        Cho phép chuyển trạng thái
                    </label>

                    <select
                        name="allows_transition"
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
                            value="1"
                            @selected(
                                (string) old(
                                    'allows_transition',
                                    (int) $queueStatus->allows_transition
                                ) === '1'
                            )
                        >
                            Có
                        </option>

                        <option
                            value="0"
                            @selected(
                                (string) old(
                                    'allows_transition',
                                    (int) $queueStatus->allows_transition
                                ) === '0'
                            )
                        >
                            Không
                        </option>

                    </select>

                    @error('allows_transition')
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
                        Trạng thái hoạt động
                    </label>

                    <select
                        name="is_active"
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
                            value="1"
                            @selected(
                                (string) old(
                                    'is_active',
                                    (int) $queueStatus->is_active
                                ) === '1'
                            )
                        >
                            Đang hoạt động
                        </option>

                        <option
                            value="0"
                            @selected(
                                (string) old(
                                    'is_active',
                                    (int) $queueStatus->is_active
                                ) === '0'
                            )
                        >
                            Ngừng hoạt động
                        </option>

                    </select>

                    @error('is_active')
                        <div style="
                            margin-top: 5px;
                            color: #dc2626;
                        ">
                            {{ $message }}
                        </div>
                    @enderror

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
                            'admin.queue-statuses.show',
                            $queueStatus
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
