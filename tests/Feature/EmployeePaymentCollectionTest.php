<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeePaymentCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    private function cashier(): User { return User::where('business_code', 'NV000004')->firstOrFail(); }
    private function payable(): Payable { return Payable::where('payable_code', 'PT000012')->firstOrFail(); }
    private function payload(array $extra = []): array { return array_merge(['payment_method' => 'CASH', 'confirm_payment' => '1'], $extra); }

    public function test_cashier_collects_once_with_authenticated_identity_and_audit(): void
    {
        $user = $this->cashier(); $payable = $this->payable();
        $this->actingAs($user)->get(route('employee.payments.collect.create', $payable))->assertOk()->assertSee($payable->payable_code);
        $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertRedirect(route('employee.payments.show', $payable));
        $transaction = $payable->transactions()->where('transaction_result', 'SUCCESS')->firstOrFail();
        $this->assertSame($user->employee->id, $transaction->collected_by_employee_id);
        $this->assertSame('PAID', $payable->fresh()->paymentStatus->name);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'entity_type' => 'payment_transactions', 'entity_id' => $transaction->id]);
        $this->get(route('employee.payments.show', $payable))->assertOk()->assertSee($user->employee->employee_code)->assertSee($transaction->transaction_code)->assertSeeText('Đã ghi nhận thanh toán thành công.', false);
        $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertSessionHasErrors();
        $this->assertSame(1, $payable->transactions()->where('transaction_result', 'SUCCESS')->count());
    }

    public function test_non_cashier_and_tampered_inputs_are_rejected(): void
    {
        $payable = $this->payable();
        $other = User::where('business_code', 'NV000001')->firstOrFail();
        $this->actingAs($other)->get(route('employee.payments.collect.create', $payable))->assertForbidden();
        $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertForbidden();
    }

    public function test_cashier_cannot_forge_collector_amount_or_confirm_unverified_qr(): void
    {
        $payable = $this->payable();
        $this->actingAs($this->cashier());
        foreach ([['collected_by_employee_id' => 1], ['amount' => -1], ['amount' => 999999999], ['payment_method' => 'QR']] as $input) {
            $this->post(route('employee.payments.collect.store', $payable), $this->payload($input))->assertSessionHasErrors(array_keys($input));
        }
        $this->assertSame(0, $payable->transactions()->where('transaction_result', 'SUCCESS')->count());
    }

    public function test_paid_and_cancelled_and_invalid_amount_are_rejected(): void
    {
        $payable = $this->payable(); $this->actingAs($this->cashier());
        foreach (['PAID', 'CANCELLED'] as $status) {
            $payable->update(['payment_status_id' => PaymentStatus::where('name', $status)->value('id')]);
            $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertSessionHasErrors();
        }
        $payable->update(['payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'amount' => -1]);
        $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertSessionHasErrors();
    }
}
