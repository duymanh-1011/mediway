<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\PaymentTransaction;
use App\Models\QrPayment;
use App\Models\User;
use App\Models\VisitStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Tests\TestCase;

class CashierQrPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Payable $first;
    private Payable $second;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        config(['services.cashier_qr.driver' => 'demo']);
        $this->actingAsEmployeeWorkspace(User::where('business_code', 'NV000004')->sole(), 'cashier');
        $this->first = Payable::where('payable_code', 'PT000011')->sole();
        $this->second = Payable::where('payable_code', 'PT000012')->sole();
    }

    public function test_walk_in_exam_fee_offers_cash_and_qr_and_queues_once_after_payment(): void
    {
        $visit = $this->first->medicalVisit->replicate()->fill([
            'visit_code' => 'LK-QR-EXAM', 'appointment_id' => null, 'reception_source' => 'WALK_IN',
            'checked_in_at' => now(), 'reception_queue_number' => null,
            'visit_status_id' => VisitStatus::where('name', 'WAITING')->sole()->id,
        ]);
        $visit->save();
        $fee = app(\App\Services\ExaminationFeeService::class)->createForVisit($visit);
        $fee->update(['amount' => 300000]);
        $selection = route('employee.payments.qr.create', ['visit' => $visit->id, 'exam_fee' => $fee->id]);
        $collect = route('employee.payments.collect.create', $fee);
        $this->get(route('employee.payments.show', $fee))->assertOk()->assertSee($collect)->assertDontSee($selection);
        $this->get($selection)->assertRedirect(route('employee.payments.collect.create', ['payable' => $fee, 'method' => 'QR']));
        $this->get($collect)->assertOk()->assertSeeText('Tiền mặt')->assertSeeText('Chuyển khoản QR')->assertSeeText('300.000 đ');

        $this->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'QR', 'payable_ids' => [$fee->id]])
            ->assertSessionHasNoErrors()->assertRedirect();
        $payment = QrPayment::where('medical_visit_id', $visit->id)->sole();
        $this->assertSame(300000, $payment->amount);
        $this->assertSame(0, $visit->queues()->count());
        $this->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => '1'])
            ->assertSessionHasErrors('payment_method');
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('employee.payments.qr.simulate', $payment))->assertRedirect(route('employee.payments.qr.show', $payment));
        }
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame('INITIAL_EXAM', $visit->queues()->sole()->queue_type);
        $this->get(route('employee.payments.qr.receipt', $payment))->assertOk()
            ->assertViewIs('employee.payments.receipt')->assertViewHas('receiptType', 'exam')
            ->assertSeeText('HÓA ĐƠN')->assertSeeText('In phiếu thu A4')->assertSeeText('Phí khám bệnh')
            ->assertSeeText('Chuyển khoản QR')->assertSeeText('300.000 đ')->assertSeeText('Ba trăm nghìn đồng')
            ->assertDontSeeText('Biên lai mô phỏng')->assertDontSeeText('Menu làm việc');
        $this->get(route('employee.payments.qr.show', $payment))->assertOk()->assertSeeText('Xem hóa đơn');
        $this->get(route('employee.payments.show', $fee))->assertOk()
            ->assertSee('href="'.route('employee.payments.qr.receipt', $payment).'">Xem hóa đơn', false);
    }

    public function test_exam_fee_is_never_added_to_a_service_qr(): void
    {
        $fee = Payable::where('payable_code', 'PT000004')->sole();
        $this->post(route('employee.payments.qr.store', $this->first->medical_visit_id), [
            'payable_ids' => [$this->first->id, $fee->id],
        ])->assertSessionHasErrors(['qr' => 'Phí khám cần một mã QR riêng.']);
        $this->assertDatabaseCount('qr_payments', 0);
    }

    public function test_two_services_share_one_qr_and_receipt_and_count_as_one_payment(): void
    {
        $this->get(route('employee.payments.collect.create', $this->first))
            ->assertOk()->assertSeeText('Công thức máu')->assertSeeText('CRP')->assertSeeText('Chọn tất cả khoản có thể thu');
        $this->post(route('employee.payments.collect.store', $this->first), [
            'payment_method' => 'QR', 'payable_ids' => [$this->first->id, $this->second->id],
        ])->assertSessionHasNoErrors();
        $payment = QrPayment::sole();
        $this->assertSame(270000, $payment->amount);
        $this->get(route('employee.payments.qr.show', $payment))->assertOk()->assertSeeText('Chờ thanh toán')
            ->assertSeeText('Thông tin bệnh nhân')->assertSeeText('Tổng kết khoản thu')->assertSeeText('270.000 đ')
            ->assertDontSeeText('Mã mô phỏng dùng khi demo.')->assertSeeText('Mô phỏng đã nhận tiền')
            ->assertSee('MEDIWAY DEMO '.$payment->order_code.' 270000 VND');
        $this->get(route('employee.payments.qr.receipt', $payment))->assertNotFound();
        $this->post(route('employee.payments.collect.store', $this->first), ['payment_method' => 'CASH', 'confirm_payment' => '1'])
            ->assertSessionHasErrors('payment_method');

        $forbidden = User::where('business_code', 'NV000001')->sole();
        $this->flushSession();
        $this->actingAsEmployeeWorkspace($forbidden, 'reception')
            ->post(route('employee.payments.qr.simulate', $payment))->assertForbidden();
        $this->flushSession();
        $this->actingAsEmployeeWorkspace(User::where('business_code', 'NV000004')->sole(), 'cashier');
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('employee.payments.qr.simulate', $payment))->assertRedirect(route('employee.payments.qr.show', $payment));
        }
        $this->assertSame('PAID', $this->first->fresh()->paymentStatus->name);
        $this->assertSame('PAID', $this->second->fresh()->paymentStatus->name);
        $this->assertSame('READY', $this->first->fresh()->serviceOrderItem->technicalStage());
        $transactions = PaymentTransaction::where('qr_payment_id', $payment->id)->get();
        $this->assertCount(2, $transactions);
        $this->assertEquals(270000, $transactions->sum('amount'));
        $this->assertCount(1, $transactions->pluck('provider_transaction_code')->unique());
        $this->get(route('employee.payments.qr.receipt', $payment))->assertOk()
            ->assertViewIs('employee.payments.receipt')->assertViewHas('receiptType', 'service')
            ->assertSeeText('Công thức máu')->assertSeeText('CRP')
            ->assertSeeText('PT000011')->assertSeeText('PT000012')->assertSeeText('270.000 đ')
            ->assertSeeText($payment->fresh()->provider_reference);
        $legacy = PaymentTransaction::whereNull('qr_payment_id')->where('transaction_result', 'SUCCESS')->whereDate('confirmed_at', today())->count();
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'QT000001')->sole())->get(route('admin.dashboard'))
            ->assertOk()->assertViewHas('dailySuccessfulPayments', $legacy + 1);
        $this->get(route('admin.payment-transactions.index', ['q' => $payment->order_code]))
            ->assertOk()->assertSeeText('Phiếu QR '.$payment->order_code);
    }

    public function test_counters_can_collect_service_fees_in_cash_with_one_receipt(): void
    {
        $route = route('employee.payments.collect.store', $this->first);
        $ids = [$this->first->id, $this->second->id];
        $payload = ['payment_method' => 'CASH', 'confirm_payment' => '1', 'payable_ids' => $ids];

        $this->post($route, ['payment_method' => 'CASH', 'confirm_payment' => '1', 'selection_submitted' => '1'])
            ->assertSessionHasErrors('payable_ids');
        $originalAmount = $this->second->amount;
        $this->second->update(['amount' => 1]);
        $this->post($route, $payload)->assertSessionHasErrors('payment_method');
        $this->assertSame('UNPAID', $this->first->fresh()->paymentStatus->name);
        $this->assertDatabaseCount('cash_payment_batches', 0);
        $this->second->update(['amount' => $originalAmount]);

        $response = $this->post($route, $payload)->assertSessionHasNoErrors();
        $batch = \App\Models\CashPaymentBatch::sole();
        $transactions = $batch->transactions()->orderBy('id')->get();
        $this->assertCount(2, $transactions);
        $this->assertEquals(270000, $transactions->sum('amount'));
        $this->assertSame('PAID', $this->first->fresh()->paymentStatus->name);
        $this->assertSame('PAID', $this->second->fresh()->paymentStatus->name);
        $response->assertRedirect(route('employee.payments.receipt', $transactions->first()));
        foreach ($transactions as $transaction) {
            $this->get(route('employee.payments.receipt', $transaction))->assertOk()->assertSeeText($batch->receipt_code)
                ->assertSeeText('Công thức máu')->assertSeeText('CRP')->assertSeeText('270.000 đ')->assertSeeText('Tiền mặt');
        }
        $this->post($route, $payload)->assertSessionHasErrors('payable_ids');
        $this->assertDatabaseCount('cash_payment_batches', 1);

        $singles = PaymentTransaction::whereNull('cash_payment_batch_id')->where('transaction_result', 'SUCCESS')->whereDate('confirmed_at', today())->count();
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'QT000001')->sole())->get(route('admin.dashboard'))
            ->assertOk()->assertViewHas('dailySuccessfulPayments', $singles + 1);
    }

    public function test_expired_qr_can_be_cancelled_and_replaced_with_a_subset(): void
    {
        $payment = $this->createQr([$this->first->id, $this->second->id]);
        $this->post(route('employee.payments.qr.store', $this->first->medical_visit_id), ['payable_ids' => [$this->second->id]])
            ->assertSessionHasErrors('qr');
        $this->travel(11)->minutes();
        $this->getJson(route('employee.payments.qr.status', $payment))->assertJsonPath('expired', true);
        $this->post(route('employee.payments.qr.simulate', $payment))->assertSessionHasErrors('qr');
        $this->post(route('employee.payments.qr.cancel', $payment))->assertRedirect();
        $this->assertSame('CANCELLED', $payment->fresh()->status);
        $replacement = $this->createQr([$this->second->id]);
        $this->assertSame(150000, $replacement->amount);
        $this->post(route('employee.payments.qr.simulate', $replacement))->assertRedirect();
        $this->assertSame('UNPAID', $this->first->fresh()->paymentStatus->name);
        $this->assertSame('PAID', $this->second->fresh()->paymentStatus->name);
    }

    public function test_wrong_visit_and_browser_amount_are_rejected(): void
    {
        $route = route('employee.payments.qr.store', $this->first->medical_visit_id);
        $other = Payable::where('medical_visit_id', '!=', $this->first->medical_visit_id)->firstOrFail();
        $this->post($route, ['payable_ids' => [$this->first->id, $other->id]])->assertSessionHasErrors('qr');
        $this->post($route, ['payable_ids' => [$this->first->id], 'amount' => 100])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('qr_payments', 0);
    }

    public function test_provider_webhook_rejects_forgery_and_applies_valid_transfer_once(): void
    {
        $payment = $this->createPayOsQr();
        $payload = $this->transfer($payment, 270000);
        $url = route('payments.webhooks.payos');
        $this->postJson($url, [...$payload, 'signature' => str_repeat('0', 64)])->assertBadRequest();
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertOk();
        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame(2, PaymentTransaction::where('qr_payment_id', $payment->id)->count());
    }

    public function test_wrong_provider_amount_goes_to_review_without_marking_services_paid(): void
    {
        $payment = $this->createPayOsQr();
        $this->postJson(route('payments.webhooks.payos'), $this->transfer($payment, 269000))->assertOk();
        $this->assertSame('REVIEW', $payment->fresh()->status);
        $this->assertSame(269000, $payment->fresh()->review_amount);
        $this->assertSame('UNPAID', $this->first->fresh()->paymentStatus->name);
        $this->assertSame(0, PaymentTransaction::where('qr_payment_id', $payment->id)->count());
    }

    public function test_real_qr_is_released_only_after_provider_confirms_cancellation(): void
    {
        $payment = $this->createPayOsQr();
        $url = 'https://api-merchant.payos.vn/v2/payment-requests/link-qr-test';
        $data = [
            'orderCode' => $payment->order_code, 'id' => 'link-qr-test', 'amount' => 270000, 'amountPaid' => 0,
        ];
        Http::fake([
            $url => Http::sequence()->push($this->signed([...$data, 'status' => 'PENDING']))
                ->push($this->signed([...$data, 'status' => 'CANCELLED'])),
            $url.'/cancel' => Http::response($this->signed([...$data, 'status' => 'CANCELLED'])),
        ]);
        $this->post(route('employee.payments.qr.cancel', $payment))->assertRedirect(route('employee.payments.qr.create', $payment->medical_visit_id));
        $this->assertSame('CANCELLED', $payment->fresh()->status);
        $this->postJson(route('payments.webhooks.payos'), $this->transfer($payment, 270000))->assertOk();
        $this->assertSame('REVIEW', $payment->fresh()->status);
        $this->assertSame('UNPAID', $this->first->fresh()->paymentStatus->name);
    }

    private function createQr(array $ids): QrPayment
    {
        $this->post(route('employee.payments.qr.store', $this->first->medical_visit_id), ['payable_ids' => $ids])
            ->assertSessionHasNoErrors()->assertRedirect();

        return QrPayment::latest('id')->firstOrFail();
    }

    private function createPayOsQr(): QrPayment
    {
        config([
            'services.cashier_qr.driver' => 'payos', 'services.cashier_qr.client_id' => 'test-id',
            'services.cashier_qr.api_key' => 'test-api', 'services.cashier_qr.checksum_key' => 'test-hmac',
        ]);
        Http::preventStrayRequests();
        Http::fake(['https://api-merchant.payos.vn/v2/payment-requests' => fn ($request) => Http::response(
            $this->signed([
                'orderCode' => $request['orderCode'], 'amount' => $request['amount'], 'currency' => 'VND',
                'status' => 'PENDING', 'paymentLinkId' => 'link-qr-test', 'qrCode' => 'test-qr-content',
            ])
        )]);
        $payment = $this->createQr([$this->first->id, $this->second->id]);
        Http::assertSent(fn ($request) => $request['amount'] === 270000 && $request->hasHeader('x-client-id', 'test-id'));
        $this->assertSame('PENDING', $payment->status);

        return $payment;
    }

    private function transfer(QrPayment $payment, int $amount): array
    {
        return $this->signed([
            'orderCode' => $payment->order_code, 'amount' => $amount, 'code' => '00', 'currency' => 'VND',
            'paymentLinkId' => 'link-qr-test', 'reference' => 'BANK-123',
            'transactionDateTime' => now('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s'),
        ]);
    }

    private function signed(array $data): array
    {
        ksort($data);
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = $key.'='.$value;
        }

        return ['code' => '00', 'data' => $data, 'signature' => hash_hmac('sha256', implode('&', $fields), 'test-hmac')];
    }
}
