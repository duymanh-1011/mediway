@extends('admin.layouts.app')
@section('admin-page', 'service-results-edit')

@section('title', 'Sửa kết quả dịch vụ')

@section('content')

    @php
        $item = $result->serviceOrderItem;
        $order = $item?->serviceOrder;
        $visit = $order?->medicalVisit;
        $service = $item?->medicalService;
        $serviceStatus = $item?->serviceStatus;
    @endphp

    <div class="service-result-edit-page">

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.service-results.index') }}">
                Kết quả dịch vụ
            </a>

            <span aria-hidden="true">/</span>

            <a href="{{ route(
                'admin.service-results.show',
                $result->id
            ) }}">Chi tiết</a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Sửa
            </strong>

        </nav>


        <div class="admin-info-header result-header">

            <div>


                <h1>
                    Sửa kết quả dịch vụ
                </h1>

                <p>
                    Cập nhật nội dung kết quả dịch vụ.
                    Mọi thay đổi sẽ được ghi vào nhật ký hoạt động.
                </p>

            </div>


            <div class="admin-actions">

                @if ($result->verified_by_user_id)

                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'VERIFIED'])

                @else

                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'UNVERIFIED'])

                @endif

                <a href="{{ route('admin.service-results.show', $result->id) }}" class="admin-btn admin-btn-secondary">
                    ← Quay lại
                </a>

            </div>

        </div>


        @if ($errors->any())

            <div class="result-alert">

                <strong>
                    Không thể cập nhật kết quả.
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


        <form novalidate enctype="multipart/form-data"
            method="POST"
            action="{{ route(
                'admin.service-results.update',
                $result->id
            ) }}"
        >

            @csrf
            @method('PATCH')


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Thông tin dịch vụ
                    </h2>

                    <p>
                        Dữ liệu nguồn chỉ dùng để đối chiếu.
                    </p>

                </div>


                <div class="result-context-grid">

                    <div class="result-context-item">

                        <span>
                            Phiếu chỉ định
                        </span>

                        <strong>
                            @include('admin.layouts.partials.record-code', ['code' => $order?->service_order_code ?? '—'])
                        </strong>

                    </div>


                    <div class="result-context-item">

                        <span>
                            Lượt khám
                        </span>

                        <strong>
                            @include('admin.layouts.partials.record-code', ['code' => $visit?->visit_code ?? '—'])
                        </strong>

                    </div>


                    <div class="result-context-item">

                        <span>
                            Dịch vụ
                        </span>

                        <strong>
                            @include('admin.layouts.partials.record-code', ['code' => $service?->service_code ?? '—'])
                        </strong>

                        <small>
                            {{ $service?->name ?? '—' }}
                        </small>

                    </div>


                    <div class="result-context-item">

                        <span>
                            Trạng thái dịch vụ
                        </span>

                        <strong>@include('admin.layouts.partials.status-badge', ['badgeCode' => $serviceStatus?->name ?? '—', 'badgeContext' => 'service'])</strong>

                    </div>

                </div>

            </div>


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Nội dung kết quả
                    </h2>

                    <p>
                        Phải có ít nhất nội dung tóm tắt, kết luận hoặc tệp PDF/ảnh đính kèm. Chỉ nhập ghi chú chưa đủ để hoàn tất dịch vụ.
                    </p>

                </div>


                <div class="result-form-body">

                    <div class="result-form-group">

                        <label for="result_summary">
                            Tóm tắt kết quả
                        </label>

                        <textarea
                            id="result_summary"
                            name="result_summary"
                            class="result-control"
                            rows="5"
                            placeholder="Nhập nội dung tóm tắt kết quả..."
                        >{{ old(
                            'result_summary',
                            $result->result_summary
                        ) }}</textarea>

                        @error('result_summary')

                            <div class="result-field-error">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>

                        @enderror

                    </div>


                    <div class="result-form-group">

                        <label for="result_conclusion">
                            Kết luận dịch vụ
                        </label>

                        <textarea
                            id="result_conclusion"
                            name="result_conclusion"
                            class="result-control"
                            rows="5"
                            placeholder="Nhập kết luận dịch vụ..."
                        >{{ old(
                            'result_conclusion',
                            $result->result_conclusion
                        ) }}</textarea>

                        @error('result_conclusion')

                            <div class="result-field-error">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>

                        @enderror

                    </div>


                    <div class="result-form-group">

                        <label for="notes">
                            Ghi chú
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            class="result-control"
                            rows="4"
                            placeholder="Nhập ghi chú nếu có..."
                        >{{ old(
                            'notes',
                            $result->notes
                        ) }}</textarea>

                        @error('notes')

                            <div class="result-field-error">
                                {{ \App\Models\AdminDisplay::text($message) }}
                            </div>

                        @enderror

                    </div>

                </div>

            </div>


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Xác nhận kết quả
                    </h2>

                    <p>
                        Bác sĩ xác nhận và thời gian trả kết quả.
                    </p>

                </div>


                <div class="result-form-body">

                    <div class="result-form-grid">

                        <div class="result-form-group">

                            <label for="verified_by_user_id">
                                Bác sĩ xác nhận
                            </label>

                            <select
                                id="verified_by_user_id"
                                name="verified_by_user_id"
                                class="result-control"
                            >

                                <option value="">
                                    Chưa xác nhận
                                </option>

                                @foreach ($verifiers as $verifier)

                                    <option
                                        value="{{ $verifier->id }}"
                                        @selected(
                                            (string) old(
                                                'verified_by_user_id',
                                                $result->verified_by_user_id
                                            )
                                            ===
                                            (string) $verifier->id
                                        )
                                    >
                                        {{ $verifier->business_code }}
                                    </option>

                                @endforeach

                            </select>

                            @error('verified_by_user_id')

                                <div class="result-field-error">
                                    {{ \App\Models\AdminDisplay::text($message) }}
                                </div>

                            @enderror

                        </div>


                        <div class="result-form-group">

                            <label for="resulted_at">
                                Thời gian trả kết quả
                            </label>

                            <input
                                id="resulted_at"
                                type="datetime-local"
                                name="resulted_at"
                                class="result-control"
                                value="{{ old(
                                    'resulted_at',
                                    $result->resulted_at
                                        ? $result->resulted_at->format(
                                            'Y-m-d\TH:i'
                                        )
                                        : ''
                                ) }}"
                            >

                            @error('resulted_at')

                                <div class="result-field-error">
                                    {{ \App\Models\AdminDisplay::text($message) }}
                                </div>

                            @enderror

                        </div>

                    </div>

                </div>

            </div>


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Tệp kết quả
                    </h2>

                    <p>
                        Tải lên PDF hoặc ảnh kết quả thực tế; tệp chỉ được mở sau khi đăng nhập.
                    </p>

                </div>


                <div class="result-form-body">

                    @include('admin.service-results._attachments')

                </div>

            </div>


            <div class="result-warning">

                <strong>
                    Lưu ý trước khi cập nhật
                </strong>

                <p>
                    Đây là dữ liệu y tế. Nội dung trước và sau khi sửa
                    sẽ được ghi vào nhật ký hoạt động.
                </p>

            </div>


            <div class="result-confirm">

                <label>

                    <input
                        type="checkbox"
                        name="confirm_update"
                        value="1"
                        required
                    >

                    <span>
                        Tôi xác nhận muốn cập nhật kết quả dịch vụ này.
                    </span>

                </label>

            </div>


            <div class="result-actions">

                <a
                    href="{{ route(
                        'admin.service-results.show',
                        $result->id
                    ) }}"
                    class="admin-btn admin-btn-secondary"
                >Hủy</a>

                <button
                    type="submit"
                    class="admin-btn admin-btn-primary"
                >Cập nhật</button>

            </div>

        </form>

    </div>




@endsection
