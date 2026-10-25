
@php
    $editing = isset($employee);
    $detail = $editing ? $employee->detail : null;
    $employeeTypeValue = old('employee_type', $detail?->employee_type);
    $employeeTypeValue = is_string($employeeTypeValue) ? \App\Models\AdminDisplay::employeeTypeCode($employeeTypeValue) : '';

    $selectedUserId = old('user_id', '');

    $personalFields = [
        ['name' => 'citizen_id_number', 'label' => 'Số CCCD', 'max' => 12],
        ['name' => 'citizen_id_issue_place', 'label' => 'Nơi cấp CCCD', 'max' => 150],
        ['name' => 'email', 'label' => 'Email', 'max' => 150],
        ['name' => 'hometown', 'label' => 'Quê quán', 'max' => 255],
        ['name' => 'permanent_address', 'label' => 'Địa chỉ thường trú', 'max' => 255],
        ['name' => 'current_address', 'label' => 'Địa chỉ hiện tại', 'max' => 255],
        ['name' => 'nationality', 'label' => 'Quốc tịch', 'max' => 80],
        ['name' => 'ethnicity', 'label' => 'Dân tộc', 'max' => 80],
        ['name' => 'education_level', 'label' => 'Trình độ học vấn', 'max' => 120],
        ['name' => 'professional_qualification', 'label' => 'Trình độ chuyên môn', 'max' => 150],
    ];

    $workFields = [
        ['name' => 'department', 'label' => 'Phòng ban', 'max' => 120],
        ['name' => 'position', 'label' => 'Chức vụ', 'max' => 120],
        ['name' => 'employee_type', 'label' => 'Loại nhân viên', 'max' => 80],
        ['name' => 'primary_shift', 'label' => 'Ca làm việc chính', 'max' => 80],
        ['name' => 'work_location', 'label' => 'Nơi làm việc', 'max' => 180],
    ];

    $longFields = [
        ['name' => 'job_description', 'label' => 'Mô tả công việc', 'max' => 5000],
        ['name' => 'professional_skills', 'label' => 'Kỹ năng chuyên môn', 'max' => 5000],
        ['name' => 'notes', 'label' => 'Ghi chú', 'max' => 5000],
    ];

    $documents = [
        [
            'input' => 'avatar',
            'column' => 'avatar_path',
            'type' => 'avatar',
            'label' => 'Ảnh đại diện',
            'image' => true,
            'accept' => '.jpg,.jpeg,.png,.webp',
        ],
        [
            'input' => 'citizen_id_front',
            'column' => 'citizen_id_front_path',
            'type' => 'citizen-id-front',
            'label' => 'CCCD mặt trước',
            'image' => true,
            'accept' => '.jpg,.jpeg,.png,.webp',
        ],
        [
            'input' => 'citizen_id_back',
            'column' => 'citizen_id_back_path',
            'type' => 'citizen-id-back',
            'label' => 'CCCD mặt sau',
            'image' => true,
            'accept' => '.jpg,.jpeg,.png,.webp',
        ],
        [
            'input' => 'profile_pdf',
            'column' => 'profile_pdf_path',
            'type' => 'profile-pdf',
            'label' => 'Hồ sơ PDF',
            'image' => false,
            'accept' => '.pdf',
        ],
    ];
@endphp

<div class="em-section">
    <h2>Thông tin nhân viên</h2>
    <p>Các trường có dấu * là bắt buộc.</p>
</div>

<div class="em-grid">
    @if($editing)
        <div class="em-field">
            <label>Mã nhân viên</label>
            <input class="admin-record-code" type="text" value="{{ $employee->employee_code }}" disabled>
        </div>

        <div class="em-field">
            <label>Tài khoản liên kết</label>
            <input class="admin-record-code"
                type="text"
                value="{{ $employee->user?->business_code }} — {{ $employee->user?->phone }}"
                disabled
            >
        </div>
    @else
        <div class="em-field em-full">
            <label for="user_id">Tài khoản nhân viên có sẵn</label>
            <select id="user_id" name="user_id">
                <option value="">-- Tạo tài khoản nhân viên mới --</option>

                @foreach($availableUsers as $account)
                    <option
                        value="{{ $account->id }}"
                        @selected((string) $selectedUserId === (string) $account->id)
                    >
                        {{ $account->business_code }} — {{ $account->phone }}
                        ({{ \App\Models\AdminDisplay::label($account->status) }})
                    </option>
                @endforeach
            </select>

            @error('user_id')
                <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror

            <small class="em-help">
                Chọn tài khoản nhân viên chưa có hồ sơ,
                hoặc để trống để tạo tài khoản mới.
            </small>
        </div>

        <div class="em-field em-account-new">
            <label for="phone">Số điện thoại tài khoản mới *</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                value="{{ old('phone') }}"
                maxlength="10"
                placeholder="09xxxxxxxx"
            >
            @error('phone')
                <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror
        </div>

        <div class="em-field em-account-new">
            <label for="password">Mật khẩu tài khoản mới *</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="new-password"
                placeholder="Ít nhất 8 ký tự"
            >
            @error('password')
                <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror
        </div>

        <div class="em-field em-account-new">
            <label for="password_confirmation">Xác nhận mật khẩu *</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
            >
@error('password_confirmation')<div class="field-error">{{ $message }}</div>@enderror
        </div>
    @endif

    <div class="em-field">
        <label for="full_name">Họ và tên nhân viên <span>*</span></label>
        <input
            type="text"
            id="full_name"
            name="full_name"
            maxlength="120"
            value="{{ old('full_name', $editing ? $employee->full_name : '') }}"
            required
        >
        @error('full_name')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="em-field">
        <label for="date_of_birth">Ngày sinh</label>
        <input
            type="date"
            id="date_of_birth"
            name="date_of_birth"
            max="{{ now()->toDateString() }}"
            value="{{ old('date_of_birth', $editing ? $employee->date_of_birth?->format('Y-m-d') : '') }}"
        >
        @error('date_of_birth')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="em-field">
        <label for="gender">Giới tính</label>
        <select id="gender" name="gender">
            <option value="">-- Chọn giới tính --</option>

            @foreach(['Nam', 'Nữ', 'Khác'] as $genderOption)
                <option
                    value="{{ $genderOption }}"
                    @selected(old('gender', $editing ? $employee->gender : '') === $genderOption)
                >
                    {{ $genderOption }}
                </option>
            @endforeach
        </select>

        @error('gender')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="em-field">
        <label for="status">Trạng thái hồ sơ <span>*</span></label>
        <select id="status" name="status" required>
            <option
                value="ACTIVE"
                @selected(old('status', $editing ? $employee->status : 'ACTIVE') === 'ACTIVE')
            >
                Đang hoạt động
            </option>
            <option
                value="INACTIVE"
                @selected(old('status', $editing ? $employee->status : 'ACTIVE') === 'INACTIVE')
            >
                Ngừng hoạt động
            </option>
        </select>

        @error('status')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>
</div>

<div class="em-section em-section-gap">
    <h2>Thông tin cá nhân</h2>
    <p>Giấy tờ tùy thân, địa chỉ và trình độ.</p>
</div>

<div class="em-grid">
    @foreach($personalFields as $field)
        <div class="em-field">
            <label for="{{ $field['name'] }}">
                {{ $field['label'] }}
            </label>

            <input
                type="{{ $field['name'] === 'email' ? 'email' : 'text' }}"
                id="{{ $field['name'] }}"
                name="{{ $field['name'] }}"
                maxlength="{{ $field['max'] }}"
                value="{{ old($field['name'], $detail?->{$field['name']}) }}"
            >

            @error($field['name'])
                <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror
        </div>
    @endforeach

    <div class="em-field">
        <label for="citizen_id_issue_date">Ngày cấp CCCD</label>
        <input
            type="date"
            id="citizen_id_issue_date"
            name="citizen_id_issue_date"
            max="{{ now()->toDateString() }}"
            value="{{ old('citizen_id_issue_date', $detail?->citizen_id_issue_date?->format('Y-m-d')) }}"
        >
        @error('citizen_id_issue_date')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>
</div>

<div class="em-section em-section-gap">
    <h2>Thông tin công việc</h2>
    <p>Phòng ban, chức vụ, loại nhân viên và thời gian làm việc.</p>
</div>

<div class="em-grid">
    @foreach($workFields as $field)
        <div class="em-field">
            <label for="{{ $field['name'] }}">
                {{ $field['label'] }}
                @if($field['name'] === 'employee_type')<span>*</span>@endif
            </label>

            @if($field['name'] === 'employee_type')
                <select id="employee_type" name="employee_type" required aria-required="true">
                    <option value="">Chọn loại nhân viên</option>
                    @foreach($employeeTypes as $type)
                        <option value="{{ $type->code }}" @selected($employeeTypeValue === $type->code)>{{ $type->name }}</option>
                    @endforeach
                </select>
            @else
            <input
                type="text"
                id="{{ $field['name'] }}"
                name="{{ $field['name'] }}"
                maxlength="{{ $field['max'] }}"
                value="{{ old($field['name'], $detail?->{$field['name']}) }}"
            >
            @endif

            @error($field['name'])
                <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror
        </div>
    @endforeach

    <div class="em-field">
        <label for="hire_date">Ngày tuyển dụng</label>
        <input
            type="date"
            id="hire_date"
            name="hire_date"
            max="{{ now()->toDateString() }}"
            value="{{ old('hire_date', $detail?->hire_date?->format('Y-m-d')) }}"
        >
        @error('hire_date')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="em-field">
        <label for="termination_date">Ngày nghỉ việc</label>
        <input
            type="date"
            id="termination_date"
            name="termination_date"
            value="{{ old('termination_date', $detail?->termination_date?->format('Y-m-d')) }}"
        >
        @error('termination_date')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
    </div>

    <div class="em-field">
        <label for="years_of_service">Số năm công tác</label>
        <input
            type="number"
            id="years_of_service"
            name="years_of_service"
            min="0"
            max="999.99"
            step="0.01"
            value="{{ old('years_of_service', $detail?->years_of_service) }}"
        >
        @error('years_of_service')
            <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
        @enderror
        <small class="em-help">
            Nhập theo số năm công tác do đơn vị xác nhận.
        </small>
    </div>

    @foreach($longFields as $field)
        <div class="em-field em-full">
            <label for="{{ $field['name'] }}">
                {{ $field['label'] }}
            </label>

            <textarea
                id="{{ $field['name'] }}"
                name="{{ $field['name'] }}"
                maxlength="{{ $field['max'] }}"
                rows="3"
            >{{ old($field['name'], $detail?->{$field['name']}) }}</textarea>

            @error($field['name'])
                <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror
        </div>
    @endforeach
</div>

<div class="em-section em-section-gap">
    <h2>Tài liệu đính kèm</h2>
    <p>
        Ảnh tối đa 2 MB, PDF tối đa 5 MB.
        Khi chỉnh sửa, không chọn tệp mới thì tệp cũ được giữ nguyên.
    </p>
</div>

<div class="em-document-grid">
    @foreach($documents as $document)
        @php
            $hasFile = $editing
                && $detail
                && filled($detail->{$document['column']});

            $fileUrl = $hasFile
                ? route('admin.employees.document', [$employee, $document['type']])
                : null;
        @endphp

        <div class="em-document">
            <label for="{{ $document['input'] }}">
                {{ $document['label'] }}
            </label>

            <div class="em-preview">
                @if($document['image'])
                    <img
                        id="em-preview-{{ $document['input'] }}"
                        src="{{ $fileUrl ?? '' }}"
                        alt="{{ $document['label'] }}"
                        @if (! $hasFile) hidden @endif
                    >

                    <span
                        id="em-empty-{{ $document['input'] }}"
                        @if ($hasFile) hidden @endif
                    >
                        Chưa có ảnh
                    </span>
                @else
                    <span class="em-pdf-symbol">PDF</span>

                    <span id="em-file-label-{{ $document['input'] }}">
                        {{ $hasFile ? 'Đã có hồ sơ PDF' : 'Chưa có hồ sơ PDF' }}
                    </span>
                @endif
            </div>

            @if($hasFile)
                <a
                    href="{{ $fileUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="em-document-link"
                >
                    Xem tài liệu hiện tại
                </a>
            @endif

            <input
                type="file"
                id="{{ $document['input'] }}"
                name="{{ $document['input'] }}"
                accept="{{ $document['accept'] }}"
                class="em-upload"
                data-kind="{{ $document['image'] ? 'image' : 'file' }}"
            >

            @error($document['input'])
                <small class="em-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
            @enderror
        </div>
    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const userSelect = document.getElementById('user_id');

    if (userSelect) {
        const accountFields = document.querySelectorAll('.em-account-new');

        function updateAccountFields() {
            const creatingAccount = userSelect.value === '';

            accountFields.forEach(function (field) {
                field.hidden = !creatingAccount;

                field.querySelectorAll('input').forEach(function (input) {
                    input.disabled = !creatingAccount;
                });
            });
        }

        userSelect.addEventListener('change', updateAccountFields);
        updateAccountFields();
    }

    document.querySelectorAll('.em-upload').forEach(function (input) {
        const preview = document.getElementById('em-preview-' + input.id);
        const empty = document.getElementById('em-empty-' + input.id);
        const fileLabel = document.getElementById('em-file-label-' + input.id);

        const originalSrc = preview ? preview.getAttribute('src') : '';
        const originalLabel = fileLabel ? fileLabel.textContent.trim() : '';

        let objectUrl = null;

        input.addEventListener('change', function () {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            const file = input.files && input.files[0];

            if (!file) {
                if (preview) {
                    preview.src = originalSrc || '';
                    preview.hidden = !originalSrc;
                }

                if (empty) {
                    empty.hidden = Boolean(originalSrc);
                }

                if (fileLabel) {
                    fileLabel.textContent = originalLabel;
                }

                return;
            }

            if (input.dataset.kind === 'image') {
                if (!file.type.startsWith('image/')) {
                    alert('Vui lòng chọn ảnh hợp lệ.');
                    input.value = '';
                    return;
                }

                objectUrl = URL.createObjectURL(file);

                preview.src = objectUrl;
                preview.hidden = false;

                if (empty) {
                    empty.hidden = true;
                }
            } else if (fileLabel) {
                fileLabel.textContent = 'Đã chọn: ' + file.name;
            }
        });
    });
});
</script>
