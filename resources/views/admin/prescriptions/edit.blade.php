@extends('admin.layouts.app')

@section('title', 'Sửa đơn thuốc')

@section('content')

    <div class="prescription-edit-page">

        <div class="prescription-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.prescriptions.index') }}">
                Đơn thuốc
            </a>

            <span>/</span>

            <a href="{{ route(
        'admin.prescriptions.show',
        $prescription->id
    ) }}">
                {{ $prescription->prescription_code }}
            </a>

            <span>/</span>

            <strong>
                Sửa
            </strong>

        </div>


        <div class="prescription-header">

            <div>

                <div class="prescription-module-label">
                    ĐƠN THUỐC
                </div>

                <h1>
                    Sửa đơn thuốc
                </h1>

                <p>
                    Chỉnh sửa có kiểm soát.
                    Mọi thay đổi sẽ được ghi nhật ký hoạt động.
                </p>

            </div>

        </div>


        @if ($errors->any())

            <div class="prescription-alert">

                <strong>
                    Không thể cập nhật đơn thuốc.
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
        'admin.prescriptions.update',
        $prescription->id
    ) }}">

            @csrf
            @method('PATCH')


            {{-- Context --}}
            <div class="prescription-card">

                <div class="prescription-card-header">

                    <h2>
                        Thông tin nguồn
                    </h2>

                    <p>
                        Mã đơn, lượt khám và bác sĩ không thay đổi tại đây.
                    </p>

                </div>


                <div class="prescription-context-grid">

                    <div>

                        <span>
                            Mã đơn thuốc
                        </span>

                        <strong>
                            {{ $prescription->prescription_code }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Lượt khám
                        </span>

                        <strong>
                            {{ $visit?->visit_code ?? '—' }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Bác sĩ kê đơn
                        </span>

                        <strong>
                            {{ $doctor?->full_name ?? '—' }}
                        </strong>

                        @if ($doctor?->doctor_code)

                            <small>
                                {{ $doctor->doctor_code }}
                            </small>

                        @endif

                    </div>


                    <div>

                        <span>
                            Số thuốc
                        </span>

                        <strong>
                            {{ $prescription->items_count }}
                        </strong>

                    </div>

                </div>

            </div>


            {{-- Main form --}}
            <div class="prescription-card">

                <div class="prescription-card-header">

                    <h2>
                        Nội dung đơn thuốc
                    </h2>

                </div>


                <div class="prescription-form-body">

                    <div class="prescription-form-grid">

                        <div class="prescription-form-group">

                            <label for="prescribed_at">
                                Thời gian kê đơn
                            </label>

                            <input id="prescribed_at" type="datetime-local" name="prescribed_at"
                                class="prescription-control" value="{{ old(
        'prescribed_at',
        $prescription->prescribed_at
        ? $prescription->prescribed_at->format(
            'Y-m-d\TH:i'
        )
        : ''
    ) }}">

                            @error('prescribed_at')

                                <div class="prescription-field-error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        <div class="prescription-form-group">

                            <label for="status">
                                Trạng thái
                            </label>

                            <select id="status" name="status" class="prescription-control">

                                @foreach ($statuses as $status)

                                    <option value="{{ $status }}" @selected(
                                        old(
                                            'status',
                                            $prescription->status
                                        ) === $status
                                    )>
                                        {{ $status }}
                                    </option>

                                @endforeach

                            </select>

                            @error('status')

                                <div class="prescription-field-error">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    <div class="prescription-form-group">

                        <label for="general_instructions">
                            Hướng dẫn chung
                        </label>

                        <textarea id="general_instructions" name="general_instructions"
                            class="prescription-control prescription-textarea" rows="5">{{ old(
        'general_instructions',
        $prescription->general_instructions
    ) }}</textarea>

                        @error('general_instructions')

                            <div class="prescription-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    <div class="prescription-form-group">

                        <label for="doctor_notes">
                            Ghi chú bác sĩ
                        </label>

                        <textarea id="doctor_notes" name="doctor_notes" class="prescription-control prescription-textarea"
                            rows="5">{{ old(
        'doctor_notes',
        $prescription->doctor_notes
    ) }}</textarea>

                        @error('doctor_notes')

                            <div class="prescription-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    <div class="prescription-form-group">

                        <label for="prescription_pdf_path">
                            Đường dẫn PDF
                        </label>

                        <input id="prescription_pdf_path" type="text" name="prescription_pdf_path"
                            class="prescription-control" value="{{ old(
        'prescription_pdf_path',
        $prescription->prescription_pdf_path
    ) }}">

                        @error('prescription_pdf_path')

                            <div class="prescription-field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                </div>

            </div>


            <div class="prescription-warning">

                <strong>
                    Lưu ý
                </strong>

                <p>
                    Đây là dữ liệu y tế. Mọi thay đổi sẽ được lưu
                    vào audit log với dữ liệu trước và sau cập nhật.
                </p>

            </div>


            <div class="prescription-actions">

                <a href="{{ route(
        'admin.prescriptions.show',
        $prescription->id
    ) }}" class="prescription-btn prescription-btn-secondary">
                    Hủy
                </a>

                <label for="prescription-confirm-modal" class="prescription-btn prescription-btn-primary">
                    Cập nhật đơn thuốc
                </label>

            </div>


            {{-- CSS modal --}}
            <input id="prescription-confirm-modal" type="checkbox" class="prescription-modal-toggle">


            <div class="prescription-modal">

                <label for="prescription-confirm-modal" class="prescription-modal-backdrop"></label>


                <div class="prescription-modal-dialog">

                    <div class="prescription-modal-icon">
                        !
                    </div>

                    <h3>
                        Xác nhận cập nhật đơn thuốc
                    </h3>

                    <p>
                        Bạn đang thay đổi dữ liệu y tế của đơn
                        <strong>
                            {{ $prescription->prescription_code }}
                        </strong>.
                    </p>


                    <div class="prescription-modal-actions">

                        <label for="prescription-confirm-modal" class="prescription-btn prescription-btn-secondary">
                            Quay lại
                        </label>


                        <button type="submit" name="confirm_update" value="1"
                            class="prescription-btn prescription-btn-danger">
                            Xác nhận cập nhật
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>


    <style>
        .prescription-edit-page {
            --primary: #2563eb;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;
            color: var(--text);
        }

        .prescription-edit-page * {
            box-sizing: border-box;
        }

        .prescription-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            color: #94a3b8;
            font-size: 13px;
        }

        .prescription-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .prescription-breadcrumb strong {
            color: #334155;
        }

        .prescription-header {
            margin-bottom: 22px;
        }

        .prescription-module-label {
            margin-bottom: 7px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .prescription-header h1 {
            margin: 0;
            font-size: 29px;
        }

        .prescription-header p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .prescription-alert {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 11px;
        }

        .prescription-alert ul {
            margin: 7px 0 0;
            padding-left: 18px;
        }

        .prescription-card {
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #ffffff;
        }

        .prescription-card-header {
            padding: 17px 20px;
            border-bottom: 1px solid var(--border);
        }

        .prescription-card-header h2 {
            margin: 0;
            font-size: 15px;
        }

        .prescription-card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 11px;
        }

        .prescription-context-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .prescription-context-grid>div {
            padding: 18px 20px;
            border-right: 1px solid #edf2f7;
        }

        .prescription-context-grid>div:last-child {
            border-right: none;
        }

        .prescription-context-grid span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .prescription-context-grid strong {
            font-size: 11px;
        }

        .prescription-context-grid small {
            display: block;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 9px;
        }

        .prescription-form-body {
            display: grid;
            gap: 18px;
            padding: 20px;
        }

        .prescription-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .prescription-form-group label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
        }

        .prescription-control {
            display: block;
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            background: #ffffff;
            color: #0f172a;
            font-family: inherit;
            font-size: 12px;
        }

        select.prescription-control,
        input.prescription-control {
            height: 42px;
        }

        .prescription-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .prescription-textarea {
            resize: vertical;
            line-height: 1.6;
        }

        .prescription-field-error {
            margin-top: 6px;
            color: #dc2626;
            font-size: 10px;
        }

        .prescription-warning {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid #fde68a;
            border-radius: 10px;
            background: #fffbeb;
        }

        .prescription-warning strong {
            display: block;
            margin-bottom: 5px;
            color: #92400e;
            font-size: 11px;
        }

        .prescription-warning p {
            margin: 0;
            color: #78350f;
            font-size: 10px;
            line-height: 1.6;
        }

        .prescription-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
        }

        .prescription-btn {
            display: inline-flex;
            min-width: 120px;
            height: 40px;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .prescription-btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .prescription-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .prescription-btn-danger {
            border: 1px solid #dc2626;
            background: #dc2626;
            color: #ffffff;
        }

        .prescription-modal-toggle {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .prescription-modal {
            position: fixed;
            z-index: 9999;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .prescription-modal-toggle:checked+.prescription-modal {
            display: flex;
        }

        .prescription-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            cursor: pointer;
        }

        .prescription-modal-dialog {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 450px;
            padding: 24px;
            border-radius: 14px;
            background: #ffffff;
            text-align: center;
        }

        .prescription-modal-icon {
            display: flex;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: #fef3c7;
            color: #b45309;
            font-weight: 800;
        }

        .prescription-modal-dialog h3 {
            margin: 0;
            font-size: 17px;
        }

        .prescription-modal-dialog p {
            margin: 10px 0 20px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
        }

        .prescription-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
        }

        @media (max-width: 900px) {
            .prescription-context-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {

            .prescription-context-grid,
            .prescription-form-grid {
                grid-template-columns: 1fr;
            }

            .prescription-context-grid>div {
                border-right: none;
            }

            .prescription-actions,
            .prescription-modal-actions {
                flex-direction: column-reverse;
            }

            .prescription-btn {
                width: 100%;
            }
        }
    </style>

@endsection