@extends('admin.layouts.app')

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

        <div class="result-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.service-orders.index') }}">
                Chỉ định dịch vụ
            </a>

            <span>/</span>

            <a href="{{ route(
                'admin.service-orders.show',
                $item->service_order_id
            ) }}">
                {{ $order?->service_order_code ?? 'Phiếu chỉ định' }}
            </a>

            <span>/</span>

            <strong>
                Nhập kết quả
            </strong>

        </div>


        <div class="result-header">

            <div>

                <div class="result-module-label">
                    KẾT QUẢ DỊCH VỤ
                </div>

                <h1>
                    Nhập kết quả dịch vụ
                </h1>

                <p>
                    Ghi nhận kết quả chuyên môn cho dịch vụ đang thực hiện.
                </p>

            </div>

            <span class="result-status">
                {{ $status?->name ?? '—' }}
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
                            {{ $error }}
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
                        {{ $status?->name ?? '—' }}
                    </strong>

                </div>

            </div>

        </div>


        <form
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

                    <div class="form-group">

                        <label for="result_pdf_path">
                            Đường dẫn PDF
                        </label>

                        <input
                            id="result_pdf_path"
                            type="text"
                            name="result_pdf_path"
                            class="form-control"
                            maxlength="500"
                            value="{{ old('result_pdf_path') }}"
                            placeholder="Ví dụ: results/ket-qua.pdf"
                        >

                    </div>


                    <div class="form-group">

                        <label for="result_image_path">
                            Đường dẫn ảnh
                        </label>

                        <input
                            id="result_image_path"
                            type="text"
                            name="result_image_path"
                            class="form-control"
                            maxlength="500"
                            value="{{ old('result_image_path') }}"
                            placeholder="Ví dụ: results/ket-qua.jpg"
                        >

                    </div>

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
                >
                    Hủy
                </a>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Lưu kết quả
                </button>

            </div>

        </form>

    </div>


    <style>
        .result-create-page {
            width: 100%;
            color: #0f172a;
        }

        .result-create-page * {
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
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
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

        .result-status {
            display: inline-flex;
            padding: 8px 13px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
        }

        .result-alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
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

        .context-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .context-item {
            display: grid;
            gap: 5px;
            padding: 18px 20px;
            border-right: 1px solid #edf2f7;
        }

        .context-item:last-child {
            border-right: none;
        }

        .context-item span {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .context-item strong {
            font-size: 12px;
        }

        .context-item small {
            color: #64748b;
        }

        .form-body {
            display: grid;
            gap: 18px;
            padding: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 700;
        }

        .form-control {
            display: block;
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            outline: none;
            background: #ffffff;
            color: #0f172a;
            font-family: "Times New Roman", Times, serif;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        textarea.form-control {
            resize: vertical;
            line-height: 1.6;
        }

        .confirm-box {
            margin-bottom: 20px;
            padding: 15px 16px;
            border: 1px solid #fde68a;
            border-radius: 12px;
            background: #fffbeb;
        }

        .confirm-box label {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            cursor: pointer;
        }

        .confirm-box input {
            margin-top: 3px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 25px;
        }

        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            min-width: 120px;
            height: 42px;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        @media (max-width: 900px) {
            .context-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .context-item {
                border-right: none;
                border-bottom: 1px solid #edf2f7;
            }
        }

        @media (max-width: 700px) {
            .result-header {
                flex-direction: column;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }
        }
    </style>

@endsection