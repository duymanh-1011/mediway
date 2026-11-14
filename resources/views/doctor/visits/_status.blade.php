{{-- Cùng một bảng màu cho trạng thái lượt khám trên danh sách và chi tiết. --}}
@php
    $tone = match ($status?->name) {
        'WAITING' => 'waiting',
        'IN_EXAMINATION' => 'examining',
        'WAITING_FOR_SERVICE' => 'service',
        'RETURNING' => 'returning',
        'COMPLETED' => 'completed',
        default => 'neutral',
    };
@endphp
<span class="doctor-visit-status doctor-visit-status--{{ $tone }}">{{ $status?->description ?: ($status?->name ?: 'Chưa cập nhật trạng thái') }}</span>
