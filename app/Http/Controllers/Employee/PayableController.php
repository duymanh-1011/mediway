<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Support\EmployeeAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PayableController extends Controller
{
    private const NAVIGATION_KEYS = ['payment_type', 'payment_status_id', 'payment_method', 'date_from', 'date_to', 'page'];

    public static function navigationQuery(Request $request): array
    {
        $query = Arr::only($request->query(), self::NAVIGATION_KEYS);
        $token = $request->query('search');
        if (is_string($token) && isset(self::searches($request)[$token])) {
            $query['search'] = $token;
        }

        return $query;
    }

    public function filter(Request $request)
    {
        $sessionKey = self::searchSessionKey($request);
        $searches = self::searches($request);
        if ($request->boolean('reset')) {
            $token = $request->input('search');
            if (is_string($token)) {
                unset($searches[$token]);
                $request->session()->put($sessionKey, $searches);
            }

            return redirect()->route('employee.payments.index');
        }

        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80'], ...$this->filterRules($this->paymentTypes())]);
        if (filled($filters['q'] ?? null)) {
            // Give each search an opaque reference so multiple tabs can keep their own filters.
            $filters['search'] = $token = Str::random(24);
            $searches[$token] = $filters['q'];
            $request->session()->put($sessionKey, array_slice($searches, -10, preserve_keys: true));
        }

        return redirect()->route('employee.payments.index', Arr::except($filters, 'q'));
    }

    private static function searches(Request $request): array
    {
        return $request->session()->get(self::searchSessionKey($request), []);
    }

    private static function searchSessionKey(Request $request): string
    {
        return 'employee.payments.search.'.$request->user()->getAuthIdentifier();
    }

    private function paymentTypes(): array
    {
        return collect(['EXAMINATION_FEE', 'SERVICE_FEE', 'MEDICINE_FEE'])
            ->merge(Payable::query()->whereNotNull('payment_type')->distinct()->orderBy('payment_type')->pluck('payment_type'))
            ->unique()->values()->all();
    }

    private function filterRules(array $paymentTypes): array
    {
        return [
            'payment_type' => ['nullable', 'string', Rule::in($paymentTypes)],
            'payment_status_id' => ['nullable', 'integer', Rule::exists('payment_statuses', 'id')],
            'payment_method' => ['nullable', Rule::in(['CASH', 'QR'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $access = app(EmployeeAccess::class);

        abort_unless(
            $user && $access->allows($user, 'payment.view'),
            403
        );

        $employee = $access->employee($user);

        $paymentTypes = $this->paymentTypes();
        $urlFilters = $request->validate($this->filterRules($paymentTypes));
        $token = self::navigationQuery($request)['search'] ?? null;
        if ($token !== null) {
            $urlFilters['search'] = $token;
        }
        if ($request->query->has('q') || ($request->query->has('search') && $token === null)) {
            return redirect()->route('employee.payments.index', $urlFilters);
        }
        $filters = $urlFilters + ['q' => $token !== null ? self::searches($request)[$token] : null];

        // Danh mục trạng thái
        $paymentStatuses = PaymentStatus::query()
            ->orderBy('process_order')
            ->orderBy('id')
            ->get(['id', 'name']);

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
        // - CCCD và số điện thoại của bệnh nhân
        // - Tên người thu
        // - Mã nhân viên thu
        // - Mã giao dịch
        $keyword = trim($filters['q'] ?? '');

        if ($keyword !== '') {
            $pattern = '%' . $keyword . '%';

            $query->where(function ($subQuery) use ($keyword, $pattern) {
                $subQuery
                    ->where('payable_code', 'like', $pattern)
                    ->orWhere('description', 'like', $pattern)

                    ->orWhereHas(
                        'medicalVisit',
                        function ($visit) use ($keyword, $pattern) {
                            $visit
                                ->where('visit_code', 'like', $pattern)
                                ->orWhereHas(
                                    'patient',
                                    fn($patient) => $patient
                                        ->where('full_name', 'like', $pattern)
                                        ->orWhere('patient_code', 'like', $pattern)
                                        ->orWhereHas('detail', fn ($detail) => $detail->where('citizen_id_number', $keyword))
                                        ->orWhereHas('user', fn ($user) => $user->where('phone', $keyword))
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
        // Chỉ đưa các bộ lọc đã kiểm tra, không gồm từ khóa, vào URL.
        $payables = $query
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($urlFilters);
        $backQuery = $urlFilters + ['page' => $payables->currentPage() > 1 ? $payables->currentPage() : null];

        // Không cache HTML chứa trạng thái lọc cũ
        return response()
            ->view(
                'employee.payments.index',
                compact(
                    'employee',
                    'payables',
                    'paymentTypes',
                    'paymentStatuses',
                    'filters',
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
        $backQuery = self::navigationQuery($request);

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
