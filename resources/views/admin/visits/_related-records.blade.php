<section class="card section-spacing">
    <h3 class="section-title">Đơn thuốc</h3>
    @if($medicalVisit->prescription)
        @php($prescription = $medicalVisit->prescription)
        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead><tr>
                    <th scope="col" class="admin-row-number">STT</th>
                    <th scope="col">Mã đơn thuốc</th>
                    <th scope="col">Bác sĩ kê đơn</th>
                    <th scope="col">Ngày kê</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Thao tác</th>
                </tr></thead>
                <tbody><tr>
                    <td class="admin-row-number">1</td>
                    <td>@include('admin.layouts.partials.record-code', ['code' => $prescription->prescription_code])</td>
                    <td>{{ $prescription->doctor?->full_name ?? 'Chưa cập nhật' }}</td>
                    <td>{{ $prescription->prescribed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $prescription->status])</td>
                    <td><div class="admin-actions admin-table-actions"><a href="{{ route('admin.prescriptions.show', $prescription) }}" class="admin-btn admin-btn-detail">Chi tiết</a></div></td>
                </tr></tbody>
            </table>
        </div>
    @else
        <p class="form-help">Lượt khám này chưa có đơn thuốc.</p>
    @endif
</section>

<section class="card section-spacing">
    <h3 class="section-title">Danh sách khoản phải thanh toán</h3>
    <div class="admin-table-scroll">
        <table class="admin-table">
            <thead><tr>
                <th scope="col" class="admin-row-number">STT</th>
                <th scope="col">Mã khoản thu</th>
                <th scope="col">Nội dung</th>
                <th scope="col">Số tiền</th>
                <th scope="col">Trạng thái</th>
                <th scope="col">Số giao dịch</th>
                <th scope="col">Thao tác</th>
            </tr></thead>
            <tbody>
                @forelse($medicalVisit->payables as $payable)
                    <tr>
                        @include('admin.layouts.partials.row-number', ['rows' => $medicalVisit->payables, 'index' => $loop->index])
                        <td>@include('admin.layouts.partials.record-code', ['code' => $payable->payable_code])</td>
                        <td>{{ $payable->description }}</td>
                        <td>{{ number_format((float) $payable->amount, 0, ',', '.') }} đ</td>
                        <td>@include('admin.layouts.partials.status-badge', ['badgeCode' => $payable->paymentStatus?->name, 'badgeContext' => 'payment'])</td>
                        <td>{{ $payable->transactions_count }}</td>
                        <td><div class="admin-actions admin-table-actions"><a href="{{ route('admin.payables.show', $payable) }}" class="admin-btn admin-btn-detail">Chi tiết</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="admin-empty">Lượt khám này chưa có khoản phải thanh toán.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
