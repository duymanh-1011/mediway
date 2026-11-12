<div data-month-assignment>
    <div class="form-group">
        <label for="{{ $prefix }}-specialty">Chuyên khoa</label>
        <select id="{{ $prefix }}-specialty" name="{{ $specialtyName }}" data-month-specialty data-lookup-native>
            <option value="">Chọn chuyên khoa</option>
            @foreach($specialties as $specialty)
                <option value="{{ $specialty->id }}" @selected((int) $specialtyId === $specialty->id)>{{ $specialty->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="{{ $prefix }}-room">Phòng khám</label>
        <select id="{{ $prefix }}-room" name="{{ $roomName }}" data-month-room data-lookup-native>
            <option value="">Chọn phòng</option>
            @foreach($rooms as $room)
                <option value="{{ $room->id }}" data-specialty="{{ $room->specialty_id }}" @selected((int) $roomId === $room->id)>
                    {{ $room->name }}{{ $specialties->firstWhere('id', $room->specialty_id) ? ' · '.$specialties->firstWhere('id', $room->specialty_id)->name : '' }}
                </option>
            @endforeach
        </select>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-month-assignment]').forEach((fields) => {
                const specialty = fields.querySelector('[data-month-specialty]');
                const room = fields.querySelector('[data-month-room]');
                const refresh = (clear) => {
                    for (const option of room.options) {
                        if (!option.value) continue;
                        const incompatible = option.dataset.specialty !== specialty.value;
                        if (clear && incompatible && option.selected) room.value = '';
                        option.disabled = incompatible && !option.selected;
                        option.hidden = option.disabled;
                    }
                };
                specialty.addEventListener('change', () => refresh(true));
                refresh(false);
            });
        </script>
    @endpush
@endonce
