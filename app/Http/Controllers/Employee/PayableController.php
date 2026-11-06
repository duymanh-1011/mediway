<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $access = app(EmployeeAccess::class);

        abort_unless(
            $user && $access->allows($user, 'payment.view'),
            403
        );

        $employee = $access->employee($user);

        // Danh mục loại khoản thu
        $paymentTypes = Payable::query()
            ->whereNotNull('payment_type')
            ->distinct()
            ->orderBy('payment_type')
            ->pluck('payment_type');

        // Danh mục trạng thái
        $paymentStatuses = PaymentStatus::query()
            ->orderBy('process_order')
            ->orderBy('id')
            ->get(['id', 'name']);

        // Kiểm tra dữ liệu lọc
        $filters = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:80',
            ],
            'payment_type' => [
                'nullable',
                'string',
                Rule::in($paymentTypes->all()),
            ],
            'payment_status_id' => [
                'nullable',
                'integer',
                Rule::exists('payment_statuses', 'id'),
            ],
            'payment_method' => [
                'nullable',
                Rule::in(['CASH', 'QR']),
            ],
            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ]);

        // Lấy dữ liệu khoản thu và quan hệ
        $query = Payable::query()->with([
            'medicalVisit.patient:id,patient_code,full_name',
            'medicalVisit.appointment',
            'serviceOrderItem.serviceStatus',
            'serviceOrderItem.medicalService',
            'transactions' => fn($transaction) =>
            $transaction
                ->with('collector:id,employee_code,full_name')
                ->orderByDesc('id'),
            'paymentStatus:id,name',
        ]);

        // Tìm kiếm theo:
        // - Mã khoản thu
        // - Nội dung
        // - Mã lượt khám
        // - Tên bệnh nhân
        // - Mã bệnh nhân
        // - Tên người thu
        // - Mã nhân viên thu
        // - Mã giao dịch
        $keyword = trim($filters['q'] ?? '');

        if ($keyword !== '') {
            $pattern = '%' . $keyword . '%';

            $query->where(function ($subQuery) use ($pattern) {
                $subQuery
                    ->where('payable_code', 'like', $pattern)
                    ->orWhere('description', 'like', $pattern)

                    ->orWhereHas(
                        'medicalVisit',
                        function ($visit) use ($pattern) {
                            $visit
                                ->where('visit_code', 'like', $pattern)
                                ->orWhereHas(
                                    'patient',
                                    fn($patient) => $patient
                                        ->where('full_name', 'like', $pattern)
                                        ->orWhere('patient_code', 'like', $pattern)
                                );
                        }
                    )

                    ->orWhereHas(
                        'transactions.collector',
                        fn($collector) => $collector
                            ->where('full_name', 'like', $pattern)
                            ->orWhere('employee_code', 'like', $pattern)
                    )

                    ->orWhereHas(
                        'transactions',
                        fn($transaction) => $transaction
                            ->where('transaction_code', 'like', $pattern)
                    );
            });
        }

        // Lọc loại khoản thu
        if (!empty($filters['payment_type'])) {
            $query->where(
                'payment_type',
                $filters['payment_type']
            );
        }

        // Lọc trạng thái thanh toán
        if (!empty($filters['payment_status_id'])) {
            $query->where(
                'payment_status_id',
                $filters['payment_status_id']
            );
        }

        // Lọc phương thức: tiền mặt hoặc QR
        if (!empty($filters['payment_method'])) {
            $query->whereHas(
                'transactions',
                fn($transaction) => $transaction
                    ->where(
                        'payment_method',
                        $filters['payment_method']
                    )
            );
        }

        // Lọc từ ngày
        if (!empty($filters['date_from'])) {
            $query->whereDate(
                'generated_at',
                '>=',
                $filters['date_from']
            );
        }

        // Lọc đến ngày
        if (!empty($filters['date_to'])) {
            $query->whereDate(
                'generated_at',
                '<=',
                $filters['date_to']
            );
        }

        // Phân trang 10 khoản/trang
        // Giữ bộ lọc trong URL khi chuyển trang
        $payables = $query
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Không cache HTML chứa trạng thái lọc cũ
        return response()
            ->view(
                'employee.payments.index',
                compact(
                    'employee',
                    'payables',
                    'paymentTypes',
                    'paymentStatuses',
                    'filters'
                )
            )
            ->header(
                'Cache-Control',
                'private, no-store, no-cache, must-revalidate'
            )
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Xem chi tiết khoản phải thu.
     * Không thay đổi dữ liệu thanh toán.
     */
    public function show(Request $request, Payable $payable)
    {
        $user = $request->user();
        $access = app(EmployeeAccess::class);

        abort_unless(
            $user && $access->allows($user, 'payment.view'),
            403
        );

        $employee = $access->employee($user);

        // Lấy dữ liệu bệnh nhân, trạng thái và lịch sử thu
        $payable->load([
            'medicalVisit.patient',
            'medicalVisit.appointment',
            'paymentStatus',
            'serviceOrderItem.serviceStatus',
            'serviceOrderItem.medicalService',
            'transactions' => fn($query) => $query
                ->with('collector:id,employee_code,full_name')
                ->orderByDesc('id'),
        ]);

        // Giữ nguyên điều kiện lọc và trang
        // để quay lại đúng danh sách trước đó.
        $backQuery = $request->only([
            'q',
            'payment_type',
            'payment_status_id',
            'payment_method',
            'date_from',
            'date_to',
            'page',
        ]);

        return response()
            ->view(
                'employee.payments.show',
                compact(
                    'employee',
                    'payable',
                    'backQuery'
                )
            )
            ->header(
                'Cache-Control',
                'private, no-store, no-cache, must-revalidate'
            )
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
