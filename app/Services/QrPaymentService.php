<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\MedicalVisit;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\QrPayment;
use App\Models\ServiceOrderItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrPaymentService
{
    public function __construct(private PayOsQrGateway $gateway, private AppointmentPaymentDateGuard $dateGuard) {}

    public static function driver(): ?string
    {
        $driver = config('services.cashier_qr.driver');

        return match (true) {
            $driver === 'demo' && app()->environment('local', 'testing') => 'demo',
            $driver === 'payos' && filled(config('services.cashier_qr.client_id'))
                && filled(config('services.cashier_qr.api_key')) && filled(config('services.cashier_qr.checksum_key')) => 'payos',
            default => null,
        };
    }

    public static function selectionUrl(Payable $payable): string
    {
        return route('employee.payments.qr.create', [
            'visit' => $payable->medical_visit_id,
            'exam_fee' => $payable->payment_type === 'EXAMINATION_FEE' ? $payable->id : null,
        ]);
    }

    public function candidates(MedicalVisit $visit, ?int $examFee = null): Builder
    {
        if ($examFee !== null) {
            return $visit->payables()->getQuery()->outstandingForPatient()->whereKey($examFee)->where('payment_type', 'EXAMINATION_FEE');
        }

        return Payable::query()->outstandingServiceFeesForVisit($visit)
            ->with('serviceOrderItem.medicalService')->orderBy('id');
    }

    public function create(MedicalVisit $visit, Employee $employee, array $ids): QrPayment
    {
        abort_unless($provider = self::driver(), 404);
        [$payment, $created] = DB::transaction(function () use ($visit, $employee, $ids, $provider): array {
            $visit = $this->lockVisit($visit->id);
            $active = QrPayment::blocking()->where('medical_visit_id', $visit->id)->lockForUpdate()->first();
            if ($active) {
                if ($active->items()->pluck('payable_id')->sort()->values()->all() === collect($ids)->sort()->values()->all()) {
                    return [$active, false];
                }
                $this->invalid('Lượt khám đang có một phiếu QR. Hãy mở phiếu đó trước khi chọn khoản thu khác.');
            }

            $payables = $this->lockPayables($visit, $ids);
            $examFee = $payables->firstWhere('payment_type', 'EXAMINATION_FEE');
            if ($examFee && $payables->count() !== 1) {
                $this->invalid('Phí khám cần một mã QR riêng.');
            }
            $lines = [];
            foreach ($payables as $payable) {
                $this->dateGuard->assertCanPay($payable);
                $amount = $this->amount($payable);
                $lines[] = [
                    'payable_id' => $payable->id,
                    'description' => $examFee ? 'Phí khám' : $payable->serviceOrderItem->medicalService->name,
                    'amount' => $amount,
                ];
            }
            $total = array_sum(array_column($lines, 'amount'));
            if ($total > 999_999_999) {
                $this->invalid('Tổng tiền vượt giới hạn của một mã QR. Hãy chia nhỏ danh sách dịch vụ.');
            }
            $now = now('Asia/Ho_Chi_Minh');
            $expires = $visit->appointment_id || $examFee
                ? $now->copy()->addMinutes(10)->min($now->copy()->endOfDay())
                : $now->copy()->addMinutes(10);
            $creating = $provider === 'payos';
            $payment = QrPayment::create([
                'medical_visit_id' => $visit->id, 'created_by_employee_id' => $employee->id, 'provider' => $provider,
                'order_code' => random_int(1_000_000_000, 9_999_999_999), 'status' => $creating ? 'CREATING' : 'PENDING',
                'amount' => $total, 'expires_at' => $expires->setTimezone(config('app.timezone')),
            ]);
            $payment->items()->createMany($lines);
            if (! $creating) {
                $payment->update(['qr_payload' => 'MEDIWAY DEMO '.$payment->order_code.' '.$total.' VND']);
            }

            return [$payment, true];
        }, 3);

        if ($created && $payment->provider === 'payos') {
            try {
                $link = $this->gateway->create($payment);
                QrPayment::whereKey($payment->id)->where('status', 'CREATING')->update([...$link, 'status' => 'PENDING']);
            } catch (ConnectionException|ValidationException) {
                // The provider may have created the link before the connection failed.
                return $payment->refresh();
            }
        }

        return $payment->refresh();
    }

    private function lockVisit(int $id): MedicalVisit
    {
        return MedicalVisit::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function lockPayables(MedicalVisit $visit, array $ids): Collection
    {
        $items = ServiceOrderItem::query()->whereIn('id', Payable::query()->whereIn('id', $ids)->where('medical_visit_id', $visit->id)
            ->select('service_order_item_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $payables = Payable::query()->whereIn('id', $ids)->where('medical_visit_id', $visit->id)
            ->with('paymentStatus')->orderBy('id')->lockForUpdate()->get();
        if ($payables->count() !== count($ids) || $payables->isEmpty()) {
            $this->invalid('Các khoản thu phải thuộc cùng một lượt khám.');
        }
        if ($visit->appointment_id) {
            $visit->setRelation('appointment', \App\Models\Appointment::whereKey($visit->appointment_id)->lockForUpdate()->first());
        }
        foreach ($payables as $payable) {
            $payable->setRelation('medicalVisit', $visit);
            $payable->setRelation('serviceOrderItem', $items->get($payable->service_order_item_id));
        }

        return $payables;
    }

    private function amount(Payable $payable): int
    {
        if (! QrPayment::supports($payable)
            || ! in_array($payable->paymentStatus?->name, ['UNPAID', 'PENDING'], true)
            || $payable->transactions()->where('transaction_result', 'SUCCESS')->exists()
            || ! preg_match('/\A[1-9]\d*\.00\z/', $payable->amount)) {
            $this->invalid('Khoản '.$payable->payable_code.' không còn đủ điều kiện thanh toán QR.');
        }
        if ($payable->payment_type === 'SERVICE_FEE') {
            $item = $payable->serviceOrderItem;
            if (! $item?->medicalService || ! $item->serviceStatus
                || $item->serviceStatus->name === 'CANCELLED' || $item->serviceOrder?->status === 'CANCELLED'
                || (int) $item->serviceOrder?->medical_visit_id !== (int) $payable->medical_visit_id
                || $payable->amount !== $item->price_at_order) {
                $this->invalid('Khoản '.$payable->payable_code.' không còn đủ điều kiện thanh toán QR.');
            }
        } elseif ($payable->medicalVisit->status?->name === 'CANCELLED'
            || ($payable->medicalVisit->reception_source === 'WALK_IN' && ! $payable->medicalVisit->checked_in_at?->isToday())) {
            $this->invalid('Lượt khám đã hủy hoặc ngày tiếp nhận đã qua.');
        }

        return (int) $payable->amount;
    }

    public function verifyWebhook(array $payload): void
    {
        abort_unless(self::driver() === 'payos', 404);
        $data = $this->gateway->verifiedWebhook($payload);
        if (($data['code'] ?? null) !== '00' || ! is_int($data['orderCode'] ?? null)) {
            return;
        }

        $payment = QrPayment::where('provider', 'payos')->where('order_code', $data['orderCode'])->first();
        if ($payment) {
            if (! $payment->provider_link_id) {
                try {
                    $remote = $this->gateway->lookup($payment);
                } catch (ConnectionException|ValidationException) {
                    abort(503, 'Chưa kiểm tra được mã giao dịch payOS.');
                }
                abort_unless($remote['id'] === ($data['paymentLinkId'] ?? null), 422);
                QrPayment::whereKey($payment->id)->whereNull('provider_link_id')->update(['provider_link_id' => $remote['id']]);
                $payment->refresh();
            }
            abort_unless($payment->provider_link_id && $payment->provider_link_id === ($data['paymentLinkId'] ?? null), 422);
            abort_unless(($data['currency'] ?? null) === 'VND', 422);
            $this->confirmTransfer($payment, $data);
        }
    }

    public function simulate(QrPayment $payment): void
    {
        abort_unless(self::driver() === 'demo' && $payment->provider === 'demo', 404);
        $this->applyTransfer($payment, $payment->amount, 'DEMO-'.$payment->order_code, CarbonImmutable::now());
    }

    public function refresh(QrPayment $payment): void
    {
        abort_unless(self::driver() === 'payos' && $payment->provider === 'payos', 404);
        try {
            $this->applyProviderStatus($payment, $this->gateway->lookup($payment));
        } catch (ConnectionException) {
            $this->invalid('Chưa kết nối được payOS. Vui lòng kiểm tra lại sau.');
        }
    }

    public function cancel(QrPayment $payment): void
    {
        if (! in_array($payment->status, ['CREATING', 'PENDING'], true)) {
            $this->invalid('Phiếu QR không còn ở trạng thái có thể hủy.');
        }
        if ($payment->provider === 'demo') {
            $this->markCancelled($payment);
            if ($payment->refresh()->status !== 'CANCELLED') {
                $this->invalid('Phiếu QR không còn ở trạng thái có thể hủy.');
            }

            return;
        }
        abort_unless(self::driver() === 'payos', 404);
        if ($payment->status === 'CREATING' && $payment->created_at->gt(now()->subSeconds(20))) {
            $this->invalid('Đang chờ phản hồi từ payOS. Hãy thử lại sau ít giây.');
        }
        try {
            $this->applyProviderStatus($payment, $this->gateway->cancel($payment));
        } catch (ConnectionException|ValidationException) {
            // An expired request without a stored payload never displayed a QR code.
            $this->withLockedPayment($payment, static function (QrPayment $locked): void {
                if ($locked->status === 'CREATING' && $locked->expires_at->isPast() && ! $locked->qr_payload) {
                    $locked->update(['status' => 'CANCELLED']);
                }
            });
        }
        if ($payment->refresh()->status !== 'CANCELLED') {
            $this->invalid('Chưa xác minh được việc hủy phiếu. Vui lòng kiểm tra trạng thái thanh toán.');
        }
    }

    private function applyProviderStatus(QrPayment $payment, array $data): void
    {
        $received = $data['amountPaid'] ?? null;
        $status = $data['status'] ?? null;
        if ($received === 0 && in_array($status, ['EXPIRED', 'CANCELLED'], true)) {
            $this->markCancelled($payment);
        } elseif (is_int($received) && $received > 0) {
            $transfers = $data['transactions'] ?? [];
            if ($status === 'PAID' && is_array($transfers) && count($transfers) === 1
                && (array_values($transfers)[0]['amount'] ?? null) === $received) {
                $this->confirmTransfer($payment, array_values($transfers)[0]);
            } else {
                $this->withLockedPayment($payment, fn (QrPayment $locked) => $this->review($locked, $received, null, 'Số tiền hoặc số lần chuyển khoản cần được đối soát.'));
            }
        }
    }

    private function markCancelled(QrPayment $payment): void
    {
        $this->withLockedPayment($payment, static function (QrPayment $locked): void {
            if (in_array($locked->status, ['CREATING', 'PENDING'], true)) {
                $locked->update(['status' => 'CANCELLED', 'qr_payload' => null]);
            }
        });
    }

    private function confirmTransfer(QrPayment $payment, array $data): void
    {
        $amount = $data['amount'] ?? null;
        $reference = $data['reference'] ?? null;
        abort_unless(is_int($amount) && $amount > 0 && is_string($reference) && filled($reference) && strlen($reference) <= 120, 422);
        $time = $data['transactionDateTime'] ?? null;
        $paidAt = null;
        if (is_string($time)) {
            try {
                $paidAt = CarbonImmutable::parse($time, 'Asia/Ho_Chi_Minh');
            } catch (\Carbon\Exceptions\InvalidFormatException) {
                // The transfer must be reviewed when its timestamp cannot be verified.
            }
        }
        $this->applyTransfer($payment, $amount, $reference, $paidAt);
    }

    private function applyTransfer(QrPayment $payment, int $amount, string $reference, ?CarbonInterface $paidAt): void
    {
        $this->withLockedPayment($payment, function (QrPayment $locked, MedicalVisit $visit) use ($amount, $reference, $paidAt): void {
            if ($locked->provider_reference === $reference || $locked->review_reference === $reference) {
                return;
            }
            if ($locked->provider === 'payos' && QrPayment::where('provider', 'payos')->where('provider_reference', $reference)->exists()) {
                $this->review($locked, $amount, $reference, 'Mã tham chiếu đã được ghi nhận trên một phiếu khác.');

                return;
            }
            if (! in_array($locked->status, ['CREATING', 'PENDING'], true) || $amount !== $locked->amount || ! $paidAt
                || $paidAt->lt($locked->created_at->copy()->subMinute()) || $paidAt->gt($locked->expires_at)
                || $paidAt->gt(now()->addMinute())) {
                if ($locked->provider === 'demo') {
                    $this->invalid('Phiếu QR đã hết hạn hoặc không còn chờ thanh toán.');
                }
                $this->review($locked, $amount, $reference, 'Giao dịch sai số tiền, sai thời hạn hoặc phiếu đã thay đổi.');

                return;
            }

            $items = $locked->items()->get()->keyBy('payable_id');
            try {
                $payables = $this->lockPayables($visit, $items->keys()->all());
                foreach ($payables as $payable) {
                    $this->dateGuard->assertCanPay($payable, $paidAt);
                    if ($this->amount($payable) !== $items[$payable->id]->amount) {
                        $this->invalid('Số tiền khoản thu đã thay đổi.');
                    }
                }
                $paidStatus = PaymentStatus::where('name', 'PAID')->where('is_active', true)->where('allows_service_execution', true)->first();
                if (! $paidStatus) {
                    $this->invalid('Chưa cấu hình trạng thái Đã thanh toán.');
                }
                $paymentTime = $paidAt->toImmutable()->setTimezone(config('app.timezone'));
                // Leave the provider event available for reconciliation if issuing the queue number fails.
                DB::transaction(function () use ($payables, $locked, $items, $reference, $paymentTime, $paidStatus, $amount): void {
                    foreach ($payables as $payable) {
                        PaymentTransaction::create([
                            'transaction_code' => 'GD'.Str::ulid(), 'payable_id' => $payable->id, 'qr_payment_id' => $locked->id,
                            'amount' => $items[$payable->id]->amount, 'payment_method' => 'QR', 'transaction_result' => 'SUCCESS',
                            'provider_transaction_code' => $reference, 'started_at' => $locked->created_at, 'paid_at' => $paymentTime,
                            'confirmed_at' => now(), 'collected_by_employee_id' => $locked->created_by_employee_id,
                        ]);
                        $payable->update(['payment_status_id' => $paidStatus->id, 'paid_at' => $paymentTime]);
                        app(ExaminationFeeService::class)->enqueueAfterPayment($payable);
                    }
                    $locked->update(['status' => 'PAID', 'paid_at' => $paymentTime, 'provider_reference' => $reference, 'qr_payload' => null]);
                    $this->audit($locked, 'QR_PAYMENT_CONFIRMED', ['amount' => $amount, 'reference' => $reference]);
                });
            } catch (ValidationException $error) {
                $this->review($locked, $amount, $reference, $error->validator->errors()->first());
            }
        });
    }

    private function withLockedPayment(QrPayment $payment, callable $operation): void
    {
        DB::transaction(function () use ($payment, $operation): void {
            $visit = $this->lockVisit($payment->medical_visit_id);
            $operation(QrPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail(), $visit);
        }, 3);
    }

    private function review(QrPayment $payment, int $amount, ?string $reference, string $reason): void
    {
        if ($payment->review_amount === $amount && $payment->review_reference === $reference) {
            return;
        }
        $payment->update([
            'status' => $payment->status === 'PAID' ? 'PAID' : 'REVIEW',
            'review_amount' => $amount, 'review_reference' => $reference, 'review_reason' => Str::limit($reason, 250),
            'qr_payload' => null,
        ]);
        $this->audit($payment, 'QR_PAYMENT_REVIEW', ['amount' => $amount, 'reference' => $reference, 'reason' => $reason]);
    }

    private function audit(QrPayment $payment, string $action, array $values): void
    {
        AuditLog::create([
            'user_id' => auth()->id(), 'role_name_snapshot' => auth()->user()?->role?->name,
            'action' => $action, 'entity_type' => 'qr_payments', 'entity_id' => $payment->id,
            'entity_business_code' => (string) $payment->order_code, 'new_values' => $values, 'performed_at' => now(),
            'description' => $action === 'QR_PAYMENT_CONFIRMED' ? 'Ghi nhận một lần thanh toán qua QR.' : 'Phiếu thanh toán QR cần đối soát.',
        ]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['qr' => $message]);
    }
}
