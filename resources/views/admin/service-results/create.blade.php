@extends('admin.layouts.app')
@section('admin-page', 'service-results-create')

@section('title', 'Nhập kết quả dịch vụ')

@section('content')

    @php
        $item = $serviceOrderItem;
        $order = $item->serviceOrder;
        $visit = $order?->medicalVisit;
        $service = $item->medicalService;
        $status = $item->serviceStatus;
    @endphp

    <div class="result-create-page">

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Dịch vụ</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.service-orders.index') }}">
                Chỉ định dịch vụ
            </a>

            <span aria-hidden="true">/</span>

            <a href="{{ route(
                'admin.service-orders.show',
                $item->service_order_id
            ) }}">
                {{ $order?->service_order_code ?? 'Phiếu chỉ định' }}
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Nhập kết quả
            </strong>

        </nav>


        <div class="admin-info-header result-header">

            <div>


                <h1>
                    Nhập kết quả dịch vụ
                </h1>

                <p>
                    Ghi nhận kết quả chuyên môn cho dịch vụ đang thực hiện.
                </p>

            </div>

            <span class="result-status">
                {{ \App\Models\AdminDisplay::label($status?->name ?? '—') }}
            </span>

        </div>


        @if ($errors->any())

            <div class="result-alert">

                <strong>
                    Không thể lưu kết quả.
                </strong>

                <ul>

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ \App\Models\AdminDisplay::text($error) }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        <div class="result-card">

            <div class="result-card-header">

                <h2>
                    Thông tin dịch vụ
                </h2>

            </div>

            <div class="context-grid">

                <div class="context-item">

                    <span>
                        Phiếu chỉ định
                    </span>

                    <strong>
                        {{ $order?->service_order_code ?? '—' }}
                    </strong>

                </div>


                <div class="context-item">

                    <span>
                        Lượt khám
                    </span>

                    <strong>
                        {{ $visit?->visit_code ?? '—' }}
                    </strong>

                </div>


                <div class="context-item">

                    <span>
                        Dịch vụ
                    </span>

                    <strong>
                        {{ $service?->service_code ?? '—' }}
                    </strong>

                    <small>
                        {{ $service?->name ?? '—' }}
                    </small>

                </div>


                <div class="context-item">

                    <span>
                        Trạng thái
                    </span>

                    <strong>
                        {{ \App\Models\AdminDisplay::label($status?->name ?? '—') }}
                    </strong>

                </div>

            </div>

        </div>


        <form novalidate enctype="multipart/form-data"
            method="POST"
            action="{{ route(
                'admin.service-order-items.service-results.store',
                $item
            ) }}"
        >

            @csrf


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Nội dung kết quả
                    </h2>

                </div>

                <div class="form-body">

                    <p class="form-help">Phải có ít nhất nội dung tóm tắt, kết luận hoặc tệp PDF/ảnh đính kèm. Chỉ nhập ghi chú chưa đủ để hoàn tất dịch vụ.</p>

                    <div class="form-group">

                        <label for="result_summary">
                            Tóm tắt kết quả
                        </label>

                        <textarea
                            id="result_summary"
                            name="result_summary"
                            rows="5"
                            class="form-control"
                            placeholder="Nhập tóm tắt kết quả..."
                        >{{ old('result_summary') }}</textarea>
@error('result_summary')<div class="field-error">{{ $message }}</div>@enderror

                    </div>


                    <div class="form-group">

                        <label for="result_conclusion">
                            Kết luận dịch vụ
                        </label>

                        <textarea
                            id="result_conclusion"
                            name="result_conclusion"
                            rows="5"
                            class="form-control"
                            placeholder="Nhập kết luận chuyên môn..."
                        >{{ old('result_conclusion') }}</textarea>
@error('result_conclusion')<div class="field-error">{{ $message }}</div>@enderror

                    </div>


                    <div class="form-group">

                        <label for="notes">
                            Ghi chú
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="form-control"
                            placeholder="Nhập ghi chú nếu có..."
                        >{{ old('notes') }}</textarea>
@error('notes')<div class="field-error">{{ $message }}</div>@enderror

                    </div>

                </div>

            </div>


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Xác nhận kết quả
                    </h2>

                </div>

                <div class="form-body">

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="verified_by_user_id">
                                Bác sĩ xác nhận
                            </label>

                            <select
                                id="verified_by_user_id"
                                name="verified_by_user_id"
                                class="form-control"
                            >

                                <option value="">
                                    Chưa xác nhận
                                </option>

                                @foreach ($verifiers as $verifier)

                                    <option
                                        value="{{ $verifier->id }}"
                                        @selected(
                                            (string) old(
                                                'verified_by_user_id'
                                            )
                                            ===
                                            (string) $verifier->id
                                        )
                                    >
                                        {{ $verifier->business_code }}
                                    </option>

                                @endforeach

                            </select>
@error('verified_by_user_id')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        <div class="form-group">

                            <label for="resulted_at">
                                Thời gian trả kết quả
                            </label>

                            <input
                                id="resulted_at"
                                type="datetime-local"
                                name="resulted_at"
                                class="form-control"
                                value="{{ old(
                                    'resulted_at',
                                    now()->format('Y-m-d\TH:i')
                                ) }}"
                            >
@error('resulted_at')<div class="field-error">{{ $message }}</div>@enderror

                        </div>

                    </div>

                </div>

            </div>


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Tệp kết quả
                    </h2>

                </div>

                <div class="form-body">

                    @include('admin.service-results._attachments')

                </div>

            </div>


            <div class="confirm-box">

                <label>

                    <input
                        type="checkbox"
                        name="confirm_create"
                        value="1"
                        required
                    >

                    <span>
                        Tôi xác nhận thông tin kết quả trên là đúng
                        và đồng ý lưu vào hồ sơ dịch vụ.
                    </span>

                </label>

            </div>


            <div class="form-actions">

                <a
                    href="{{ route(
                        'admin.service-orders.show',
                        $item->service_order_id
                    ) }}"
                    class="btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Lưu kết quả
                </button>

            </div>

        </form>

    </div>




@endsection
