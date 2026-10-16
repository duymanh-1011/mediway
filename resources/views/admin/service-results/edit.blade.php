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

        {{-- Breadcrumb --}}
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


        {{-- Header --}}
        <div class="result-header">

            <div>

                <div class="result-module-label">
                    KẾT QUẢ DỊCH VỤ
                </div>

                <h1>
                    Sửa kết quả dịch vụ
                </h1>

                <p>
                    Cập nhật nội dung kết quả có kiểm soát.
                    Mọi thay đổi sẽ được ghi vào nhật ký hoạt động.
                </p>

            </div>


            @if ($result->verified_by_user_id)

                <span class="result-header-badge verified">
                    <span></span>
                    Đã xác nhận
                </span>

            @else

                <span class="result-header-badge pending">
                    <span></span>
                    Chưa xác nhận
                </span>

            @endif

        </div>


        {{-- Error summary --}}
        @if ($errors->any())

            <div class="result-alert result-alert-error">

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


        <form method="POST" action="{{ route(
        'admin.service-results.update',
        $result->id
    ) }}">

            @csrf
            @method('PATCH')


            {{-- Context --}}
            <div class="result-card">

                <div class="result-card-header">

                    <div>

                        <h2>
                            Thông tin dịch vụ
                        </h2>

                        <p>
                            Dữ liệu nguồn chỉ hiển thị để đối chiếu,
                            không được thay đổi tại màn hình này.
                        </p>

                    </div>

                </div>


                <div class="result-context-grid">

                    {{-- Order --}}
                    <div class="result-context-item">

                        <div class="result-label">
                            Phiếu chỉ định
                        </div>

                        <div class="result-code">
                            {{ $order?->service_order_code ?? '—' }}
                        </div>

                    </div>


                    {{-- Visit --}}
                    <div class="result-context-item">

                        <div class="result-label">
                            Lượt khám
                        </div>

                        <div class="result-secondary-code">
                            {{ $visit?->visit_code ?? '—' }}
                        </div>

                    </div>


                    {{-- Service --}}
                    <div class="result-context-item">

                        <div class="result-label">
                            Dịch vụ
                        </div>

                        @if ($service)

                            <div class="result-service-info">

                                <strong>
                                    {{ $service->name }}
                                </strong>

                                <span>
                                    {{ $service->service_code }}
                                </span>

                            </div>

                        @else

                            <span class="result-empty">
                                —
                            </span>

                        @endif

                    </div>


                    {{-- Status --}}
                    <div class="result-context-item">

                        <div class="result-label">
                            Trạng thái dịch vụ
                        </div>

                        @if ($serviceStatus)

                            <div class="result-status">
                                {{ $serviceStatus->name }}
                            </div>

                        @else

                            <span class="result-empty">
                                —
                            </span>

                        @endif

                    </div>

                </div>

            </div>


            {{-- Main result --}}
            <div class="result-card">

                <div class="result-card-header">

                    <div>

                        <h2>
                            Nội dung kết quả
                        </h2>

                        <p>
                            Tóm tắt và kết luận chuyên môn của dịch vụ.
                        </p>

                    </div>

                </div>


                <div class="result-form-body">

                    {{-- Summary --}}
                    <div class="result-form-group">

                        <label for="result_summary">
                            Tóm tắt kết quả
                        </label>

                        <textarea id="result_summary" name="result_summary" class="result-control result-textarea" rows="5"
                            placeholder="Nhập nội dung tóm tắt kết quả...">{{ old(
        'result_summary',
        $result->result_summary
    ) }}</textarea>

                        @error('result_summary')

                            <div class="result-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- Conclusion --}}
                    <div class="result-form-group">

                        <label for="result_conclusion">
                            Kết luận dịch vụ
                        </label>

                        <textarea id="result_conclusion" name="result_conclusion" class="result-control result-textarea"
                            rows="5" placeholder="Nhập kết luận dịch vụ...">{{ old(
        'result_conclusion',
        $result->result_conclusion
    ) }}</textarea>

                        @error('result_conclusion')

                            <div class="result-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- Notes --}}
                    <div class="result-form-group">

                        <label for="notes">
                            Ghi chú
                        </label>

                        <textarea id="notes" name="notes" class="result-control result-textarea" rows="4"
                            placeholder="Nhập ghi chú nếu có...">{{ old(
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


            {{-- Parameters --}}
            <div class="result-card">

                <div class="result-card-header">

                    <div>

                        <h2>
                            Chỉ số / thông số kết quả
                        </h2>

                        <p>
                            Trường này lưu dữ liệu JSON theo đúng schema
                            của service_results.
                        </p>

                    </div>

                </div>


                <div class="result-form-body">

                    <div class="result-form-group">

                        <label for="result_parameters">
                            JSON Parameters
                        </label>

                        <textarea id="result_parameters" name="result_parameters" class="result-control result-json"
                            rows="12" placeholder='{"key": "value"}'>{{ old(
        'result_parameters',
        $result->result_parameters
        ? json_encode(
            $result->result_parameters,
            JSON_PRETTY_PRINT
            |
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
        )
        : ''
    ) }}</textarea>

                        <div class="result-help">
                            Phải nhập đúng định dạng JSON.
                            Nếu không có dữ liệu, có thể để trống.
                        </div>

                        @error('result_parameters')

                            <div class="result-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                </div>

            </div>


            {{-- Verification --}}
            <div class="result-card">

                <div class="result-card-header">

                    <div>

                        <h2>
                            Xác nhận kết quả
                        </h2>

                        <p>
                            Người xác nhận và thời gian trả kết quả.
                        </p>

                    </div>

                </div>


                <div class="result-form-body">

                    <div class="result-form-grid">

                        {{-- Verifier --}}
                        <div class="result-form-group">

                            <label for="verified_by_user_id">
                                Người xác nhận
                            </label>

                            <select id="verified_by_user_id" name="verified_by_user_id" class="result-control">

                                <option value="">
                                    Chưa xác nhận
                                </option>

                                @foreach ($verifiers as $verifier)

                                    <option value="{{ $verifier->id }}" @selected(
                                        (string) old(
                                            'verified_by_user_id',
                                            $result->verified_by_user_id
                                        )
                                        ===
                                        (string) $verifier->id
                                    )>
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


                        {{-- Resulted at --}}
                        <div class="result-form-group">

                            <label for="resulted_at">
                                Thời gian trả kết quả
                            </label>

                            <input id="resulted_at" type="datetime-local" name="resulted_at" class="result-control" value="{{ old(
        'resulted_at',
        $result->resulted_at
        ? $result->resulted_at->format(
            'Y-m-d\TH:i'
        )
        : ''
    ) }}">

                            @error('resulted_at')

                                <div class="result-field-error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>

                </div>

            </div>


            {{-- Files --}}
            <div class="result-card">

                <div class="result-card-header">

                    <div>

                        <h2>
                            Tệp kết quả
                        </h2>

                        <p>
                            Đường dẫn PDF và ảnh kết quả.
                        </p>

                    </div>

                </div>


                <div class="result-form-body">

                    <div class="result-form-group">

                        <label for="result_pdf_path">
                            Đường dẫn PDF
                        </label>

                        <input id="result_pdf_path" type="text" name="result_pdf_path" class="result-control" value="{{ old(
        'result_pdf_path',
        $result->result_pdf_path
    ) }}" placeholder="Ví dụ: results/service-result.pdf">

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

                        <input id="result_image_path" type="text" name="result_image_path" class="result-control" value="{{ old(
        'result_image_path',
        $result->result_image_path
    ) }}" placeholder="Ví dụ: results/service-result.jpg">

                        @error('result_image_path')

                            <div class="result-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                </div>

            </div>


            {{-- Warning --}}
            <div class="result-warning">

                <div class="result-warning-icon">
                    !
                </div>

                <div>

                    <strong>
                        Lưu ý trước khi cập nhật
                    </strong>

                    <p>
                        Đây là dữ liệu y tế. Thay đổi sẽ được ghi lại
                        trong audit log gồm dữ liệu trước và sau chỉnh sửa,
                        người thao tác và thời gian thực hiện.
                    </p>

                </div>

            </div>


            {{-- Actions --}}
            <div class="result-actions">

                <a href="{{ route(
        'admin.service-results.show',
        $result->id
    ) }}" class="result-btn result-btn-secondary">
                    Hủy
                </a>


                {{-- CSS-only modal trigger --}}
                <label for="confirm-update-modal" class="result-btn result-btn-primary">
                    Cập nhật kết quả
                </label>

            </div>


            {{-- CSS-only modal state --}}
            <input type="checkbox" id="confirm-update-modal" class="result-modal-toggle">


            {{-- Confirmation modal --}}
            <div class="result-modal">

                <label for="confirm-update-modal" class="result-modal-backdrop"></label>


                <div class="result-modal-dialog">

                    <div class="result-modal-icon">
                        !
                    </div>


                    <h3>
                        Xác nhận cập nhật kết quả
                    </h3>


                    <p>
                        Bạn đang thay đổi dữ liệu kết quả y tế.
                        Thao tác này sẽ được ghi vào nhật ký hoạt động.
                    </p>


                    <div class="result-modal-summary">

                        <div>

                            <span>
                                Phiếu chỉ định
                            </span>

                            <strong>
                                {{ $order?->service_order_code ?? '—' }}
                            </strong>

                        </div>


                        <div>

                            <span>
                                Dịch vụ
                            </span>

                            <strong>
                                {{ $service?->service_code ?? '—' }}
                            </strong>

                        </div>

                    </div>


                    <div class="result-modal-actions">

                        <label for="confirm-update-modal" class="result-btn result-btn-secondary">
                            Quay lại
                        </label>


                        <button type="submit" name="confirm_update" value="1" class="result-btn result-btn-danger">
                            Xác nhận cập nhật
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>


    <style>
        .service-result-edit-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .service-result-edit-page * {
            box-sizing: border-box;
        }


        /* =========================
           BREADCRUMB
        ========================= */

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
            color: var(--primary);
        }

        .result-breadcrumb strong {
            color: #334155;
        }


        /* =========================
           HEADER
        ========================= */

        .result-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }

        .result-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .result-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;
        }

        .result-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .result-header-badge {
            display: inline-flex;
            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .result-header-badge span {
            width: 7px;
            height: 7px;

            border-radius: 50%;
        }

        .result-header-badge.verified {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #15803d;
        }

        .result-header-badge.verified span {
            background: #22c55e;
        }

        .result-header-badge.pending {
            border: 1px solid #fde68a;

            background: #fffbeb;

            color: #b45309;
        }

        .result-header-badge.pending span {
            background: #f59e0b;
        }


        /* =========================
           ALERT
        ========================= */

        .result-alert {
            margin-bottom: 20px;

            padding: 14px 16px;

            border-radius: 10px;

            font-size: 12px;

            line-height: 1.6;
        }

        .result-alert-error {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #b91c1c;
        }

        .result-alert strong {
            display: block;

            margin-bottom: 6px;
        }

        .result-alert ul {
            margin: 0;

            padding-left: 18px;
        }


        /* =========================
           CARD
        ========================= */

        .result-card {
            overflow: hidden;

            margin-bottom: 22px;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .result-card-header {
            padding: 18px 20px;

            border-bottom: 1px solid var(--border);
        }

        .result-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .result-card-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =========================
           CONTEXT
        ========================= */

        .result-context-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .result-context-item {
            padding: 18px 20px;

            border-right: 1px solid #edf2f7;
        }

        .result-context-item:last-child {
            border-right: none;
        }

        .result-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .result-code,
        .result-secondary-code {
            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 11px;
            font-weight: 750;
        }

        .result-code {
            color: #1d4ed8;
        }

        .result-secondary-code {
            color: #475569;
        }

        .result-service-info {
            display: grid;

            gap: 3px;
        }

        .result-service-info strong {
            color: #0f172a;

            font-size: 12px;
        }

        .result-service-info span {
            color: #94a3b8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
        }

        .result-status {
            display: inline-flex;

            padding: 5px 9px;

            border-radius: 999px;

            background: #dcfce7;

            color: #15803d;

            font-size: 10px;
            font-weight: 800;
        }

        .result-empty {
            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================
           FORM
        ========================= */

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

            color: #334155;

            font-size: 12px;
            font-weight: 700;
        }

        .result-control {
            display: block;

            width: 100%;

            min-height: 42px;

            padding: 10px 12px;

            border: 1px solid #cbd5e1;
            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #0f172a;

            font-family: inherit;
            font-size: 13px;

            transition: .2s;
        }

        select.result-control,
        input.result-control {
            height: 42px;

            padding-top: 0;
            padding-bottom: 0;
        }

        .result-control:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        .result-textarea {
            resize: vertical;

            line-height: 1.65;
        }

        .result-json {
            resize: vertical;

            background: #0f172a;

            color: #e2e8f0;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 12px;

            line-height: 1.65;
        }

        .result-json:focus {
            border-color: #60a5fa;
        }

        .result-help {
            margin-top: 6px;

            color: #64748b;

            font-size: 10px;

            line-height: 1.5;
        }

        .result-field-error {
            margin-top: 6px;

            color: #dc2626;

            font-size: 10px;
            font-weight: 650;
        }


        /* =========================
           WARNING
        ========================= */

        .result-warning {
            display: flex;
            align-items: flex-start;

            gap: 12px;

            margin-bottom: 20px;

            padding: 15px 16px;

            border: 1px solid #fde68a;
            border-radius: 12px;

            background: #fffbeb;
        }

        .result-warning-icon {
            display: flex;

            width: 30px;
            height: 30px;

            flex: 0 0 30px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #f59e0b;

            color: #ffffff;

            font-size: 12px;
            font-weight: 800;
        }

        .result-warning strong {
            display: block;

            margin-bottom: 5px;

            color: #92400e;

            font-size: 12px;
        }

        .result-warning p {
            margin: 0;

            color: #78350f;

            font-size: 11px;

            line-height: 1.65;
        }


        /* =========================
           BUTTON
        ========================= */

        .result-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;

            gap: 10px;

            margin-bottom: 25px;
        }

        .result-btn {
            display: inline-flex;

            min-width: 120px;
            height: 42px;

            align-items: center;
            justify-content: center;

            padding: 0 16px;

            border-radius: 9px;

            font-family: inherit;
            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;
        }

        .result-btn-primary {
            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }

        .result-btn-primary:hover {
            background: var(--primary-dark);
        }

        .result-btn-secondary {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #475569;
        }

        .result-btn-danger {
            border: 1px solid #dc2626;

            background: #dc2626;

            color: #ffffff;
        }

        .result-btn-danger:hover {
            background: #b91c1c;
        }


        /* =========================
           CSS-ONLY MODAL
        ========================= */

        .result-modal-toggle {
            position: absolute;

            width: 1px;
            height: 1px;

            opacity: 0;

            pointer-events: none;
        }

        .result-modal {
            position: fixed;

            z-index: 9999;

            inset: 0;

            display: none;

            align-items: center;
            justify-content: center;

            padding: 20px;
        }

        .result-modal-toggle:checked+.result-modal {
            display: flex;
        }

        .result-modal-backdrop {
            position: absolute;

            inset: 0;

            background: rgba(15, 23, 42, .55);

            cursor: pointer;
        }

        .result-modal-dialog {
            position: relative;

            z-index: 2;

            width: 100%;
            max-width: 470px;

            padding: 24px;

            border-radius: 15px;

            background: #ffffff;

            box-shadow:
                0 25px 60px rgba(15, 23, 42, .22);

            text-align: center;
        }

        .result-modal-icon {
            display: flex;

            width: 48px;
            height: 48px;

            align-items: center;
            justify-content: center;

            margin: 0 auto 14px;

            border-radius: 50%;

            background: #fef3c7;

            color: #b45309;

            font-size: 20px;
            font-weight: 800;
        }

        .result-modal-dialog h3 {
            margin: 0;

            color: #0f172a;

            font-size: 18px;
        }

        .result-modal-dialog>p {
            margin: 10px 0 18px;

            color: #64748b;

            font-size: 12px;

            line-height: 1.65;
        }

        .result-modal-summary {
            overflow: hidden;

            margin-bottom: 20px;

            border: 1px solid #e2e8f0;
            border-radius: 10px;

            text-align: left;
        }

        .result-modal-summary>div {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 11px 13px;

            border-bottom: 1px solid #edf2f7;
        }

        .result-modal-summary>div:last-child {
            border-bottom: none;
        }

        .result-modal-summary span {
            color: #64748b;

            font-size: 10px;
        }

        .result-modal-summary strong {
            color: #0f172a;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 11px;
        }

        .result-modal-actions {
            display: flex;
            justify-content: flex-end;

            gap: 9px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .result-context-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .result-context-item {
                border-bottom: 1px solid #edf2f7;
            }

            .result-context-item:nth-child(2n) {
                border-right: none;
            }

        }


        @media (max-width: 768px) {

            .result-header {
                flex-direction: column;
            }

            .result-header h1 {
                font-size: 24px;
            }

            .result-context-grid,
            .result-form-grid {
                grid-template-columns: 1fr;
            }

            .result-context-item {
                border-right: none;
            }

            .result-actions {
                flex-direction: column-reverse;
            }

            .result-btn {
                width: 100%;
            }

            .result-modal-actions {
                flex-direction: column-reverse;
            }

        }
    </style>

@endsection