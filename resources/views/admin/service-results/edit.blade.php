@extends('admin.layouts.app')

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

        <div class="result-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.service-results.index') }}">
                Kết quả dịch vụ
            </a>

            <span>/</span>

            <a href="{{ route(
                'admin.service-results.show',
                $result->id
            ) }}">
                Chi tiết
            </a>

            <span>/</span>

            <strong>
                Sửa
            </strong>

        </div>


        <div class="result-header">

            <div>

                <div class="result-module-label">
                    KẾT QUẢ DỊCH VỤ
                </div>

                <h1>
                    Sửa kết quả dịch vụ
                </h1>

                <p>
                    Cập nhật nội dung kết quả dịch vụ.
                    Mọi thay đổi sẽ được ghi vào nhật ký hoạt động.
                </p>

            </div>


            @if ($result->verified_by_user_id)

                <span class="result-header-badge verified">
                    Đã xác nhận
                </span>

            @else

                <span class="result-header-badge pending">
                    Chưa xác nhận
                </span>

            @endif

        </div>


        @if ($errors->any())

            <div class="result-alert">

                <strong>
                    Không thể cập nhật kết quả.
                </strong>

                <ul>

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        <form
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
                            {{ $order?->service_order_code ?? '—' }}
                        </strong>

                    </div>


                    <div class="result-context-item">

                        <span>
                            Lượt khám
                        </span>

                        <strong>
                            {{ $visit?->visit_code ?? '—' }}
                        </strong>

                    </div>


                    <div class="result-context-item">

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


                    <div class="result-context-item">

                        <span>
                            Trạng thái dịch vụ
                        </span>

                        <strong>
                            {{ $serviceStatus?->name ?? '—' }}
                        </strong>

                    </div>

                </div>

            </div>


            <div class="result-card">

                <div class="result-card-header">

                    <h2>
                        Nội dung kết quả
                    </h2>

                    <p>
                        Tóm tắt, kết luận chuyên môn và ghi chú.
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
                                {{ $message }}
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
                                {{ $message }}
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
                                {{ $message }}
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
                                    {{ $message }}
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
                                    {{ $message }}
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
                        Đường dẫn PDF và ảnh kết quả nếu có.
                    </p>

                </div>


                <div class="result-form-body">

                    <div class="result-form-group">

                        <label for="result_pdf_path">
                            Đường dẫn PDF
                        </label>

                        <input
                            id="result_pdf_path"
                            type="text"
                            name="result_pdf_path"
                            class="result-control"
                            maxlength="500"
                            value="{{ old(
                                'result_pdf_path',
                                $result->result_pdf_path
                            ) }}"
                            placeholder="Ví dụ: results/service-result.pdf"
                        >

                        @error('result_pdf_path')

                            <div class="result-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    <div class="result-form-group">

                        <label for="result_image_path">
                            Đường dẫn ảnh
                        </label>

                        <input
                            id="result_image_path"
                            type="text"
                            name="result_image_path"
                            class="result-control"
                            maxlength="500"
                            value="{{ old(
                                'result_image_path',
                                $result->result_image_path
                            ) }}"
                            placeholder="Ví dụ: results/service-result.jpg"
                        >

                        @error('result_image_path')

                            <div class="result-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

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
                    class="result-btn result-btn-secondary"
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="result-btn result-btn-primary"
                >
                    Cập nhật kết quả
                </button>

            </div>

        </form>

    </div>


    <style>
        .service-result-edit-page {
            width: 100%;
            color: #0f172a;
        }

        .service-result-edit-page * {
            box-sizing: border-box;
        }

        .result-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            color: #94a3b8;
            font-size: 13px;
        }

        .result-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .result-breadcrumb a:hover {
            color: #2563eb;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }

        .result-module-label {
            margin-bottom: 7px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .result-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .result-header p {
            margin: 8px 0 0;
            color: #64748b;
            line-height: 1.6;
        }

        .result-header-badge {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .result-header-badge.verified {
            background: #dcfce7;
            color: #15803d;
        }

        .result-header-badge.pending {
            background: #fef3c7;
            color: #b45309;
        }

        .result-alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fef2f2;
            color: #991b1b;
        }

        .result-alert ul {
            margin-bottom: 0;
        }

        .result-card {
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
        }

        .result-card-header {
            padding: 17px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .result-card-header h2 {
            margin: 0;
            font-size: 15px;
        }

        .result-card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .result-context-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .result-context-item {
            display: grid;
            gap: 5px;
            padding: 18px 20px;
            border-right: 1px solid #edf2f7;
        }

        .result-context-item:last-child {
            border-right: none;
        }

        .result-context-item span {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .result-context-item strong {
            font-size: 12px;
        }

        .result-context-item small {
            color: #64748b;
        }

        .result-form-body {
            display: grid;
            gap: 18px;
            padding: 20px;
        }

        .result-form-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .result-form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 700;
        }

        .result-control {
            display: block;
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #ffffff;
            color: #0f172a;
            font-family: "Times New Roman", Times, serif;
            font-size: 14px;
            outline: none;
        }

        .result-control:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        textarea.result-control {
            resize: vertical;
            line-height: 1.6;
        }

        .result-field-error {
            margin-top: 6px;
            color: #dc2626;
            font-size: 12px;
        }

        .result-warning {
            margin-bottom: 18px;
            padding: 15px 16px;
            border: 1px solid #fde68a;
            border-radius: 12px;
            background: #fffbeb;
        }

        .result-warning strong {
            display: block;
            margin-bottom: 5px;
            color: #92400e;
        }

        .result-warning p {
            margin: 0;
            color: #78350f;
            line-height: 1.6;
        }

        .result-confirm {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: #eff6ff;
        }

        .result-confirm label {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            cursor: pointer;
        }

        .result-confirm input {
            margin-top: 3px;
        }

        .result-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 25px;
        }

        .result-btn {
            display: inline-flex;
            min-width: 130px;
            height: 42px;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 9px;
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .result-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .result-btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .result-btn-primary:hover {
            background: #1d4ed8;
        }

        @media (max-width: 900px) {
            .result-context-grid,
            .result-form-grid {
                grid-template-columns: 1fr;
            }

            .result-context-item {
                border-right: none;
                border-bottom: 1px solid #edf2f7;
            }
        }

        @media (max-width: 700px) {
            .result-header {
                flex-direction: column;
            }

            .result-actions {
                flex-direction: column-reverse;
            }

            .result-btn {
                width: 100%;
            }
        }
    </style>

@endsection