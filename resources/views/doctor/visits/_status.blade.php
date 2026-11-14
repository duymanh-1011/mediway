{{-- Cùng một bảng màu cho trạng thái lượt khám trên danh sách và chi tiết. --}}
@php
    // Trạng thái hiển thị tính theo cùng quy tắc với Admin; không đổi trạng thái trong CSDL.
    $code = isset($visit) ? $visit->displayStatus() : ($status?->name ?? null);
    $tone = match ($code) {
        'WAITING', 'WAITING_FOR_PAYMENT' => 'waiting',
        'IN_EXAMINATION' => 'examining',
        'WAITING_FOR_SERVICE' => 'service',
        'RETURNING', 'WAITING_FOR_RETURN', 'SERVICES_RESOLVED' => 'returning',
        'COMPLETED' => 'completed',
        default => 'neutral',
    };
@endphp
<span class="doctor-visit-status doctor-visit-status--{{ $tone }}" data-visit-status="{{ $code }}">{{ $code ? \App\Models\AdminDisplay::label($code) : 'Chưa cập nhật trạng thái' }}</span>
