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
    if ($hasBeenCalled ?? false) {
        $tone = 'examining';
    }
    $label = match (true) {
        $hasBeenCalled ?? false => 'Đã gọi',
        $code === 'WAITING_FOR_RETURN' => 'Chờ đọc kết quả',
        $code !== null => \App\Models\AdminDisplay::label($code),
        default => 'Chưa cập nhật trạng thái',
    };
@endphp
<span class="doctor-visit-status doctor-visit-status--{{ $tone }}" data-visit-status="{{ $code }}">{{ $label }}</span>
