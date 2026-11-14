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
        $this->withSession(['employee_workspace_mode' => 'cashier']);
    }

    private function cashier(): User { return User::where('business_code', 'NV000004')->firstOrFail(); }
    private function payable(): Payable { return Payable::where('payable_code', 'PT000012')->firstOrFail(); }
    private function payload(array $extra = []): array { return array_merge(['payment_method' => 'CASH', 'confirm_payment' => '1'], $extra); }

    public function test_cashier_screens_render_using_the_production_vite_manifest(): void
    {
        if (! is_file(public_path('build/manifest.json')) || is_file(public_path('hot'))) {
            $this->markTestSkipped('Run npm run build with the Vite dev server stopped.');
        }

        $this->withVite();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $payable = $this->payable();
        $this->actingAs($this->cashier());

        $this->get(route('employee.payments.index'))->assertOk()->assertSee('/build/assets/employee-payments-', false);
        $this->get(route('employee.payments.show', $payable))->assertOk()->assertSee('/build/assets/employee-payments-', false);
        $this->get(route('employee.payments.collect.create', $payable))->assertOk()->assertSee('/build/assets/employee-payments-', false);

        $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertRedirect();
        $transaction = $payable->transactions()->where('transaction_result', 'SUCCESS')->firstOrFail();
        $this->get(route('employee.payments.receipt', $transaction))->assertOk()->assertSee('/build/assets/payment-receipt-', false);
    }

    public function test_cashier_collects_once_with_authenticated_identity_and_audit(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $user = $this->cashier(); $payable = $this->payable();
        $this->actingAs($user)->get(route('employee.payments.collect.create', $payable))->assertOk()->assertSee($payable->payable_code);
        $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertRedirect(route('employee.payments.show', $payable));
        $transaction = $payable->transactions()->where('transaction_result', 'SUCCESS')->firstOrFail();
        $this->assertSame($user->employee->id, $transaction->collected_by_employee_id);
        $this->assertSame('PAID', $payable->fresh()->paymentStatus->name);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'entity_type' => 'payment_transactions', 'entity_id' => $transaction->id]);
        $this->get(route('employee.payments.show', $payable))->assertOk()->assertSee($user->employee->employee_code)->assertSee($transaction->transaction_code)->assertSeeText('Đã ghi nhận thanh toán tiền mặt thành công.', false);
        $this->from(route('employee.payments.collect.create', $payable))
            ->post(route('employee.payments.collect.store', $payable), $this->payload())
            ->assertRedirect(route('employee.payments.show', $payable))
            ->assertSessionHasErrors(['payment_method' => 'Khoản thu không hợp lệ hoặc đã có giao dịch thành công.']);
        $this->assertSame(1, $payable->transactions()->where('transaction_result', 'SUCCESS')->count());
    }

    public function test_non_cashier_and_tampered_inputs_are_rejected(): void
    {
        $payable = $this->payable();
        $other = User::where('business_code', 'NV000001')->firstOrFail();
        $this->actingAsEmployeeWorkspace($other, 'reception')->get(route('employee.payments.collect.create', $payable))->assertForbidden();
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
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $payable = $this->payable(); $this->actingAs($this->cashier());
        foreach (['PAID', 'CANCELLED'] as $status) {
            $payable->update(['payment_status_id' => PaymentStatus::where('name', $status)->value('id')]);
            $this->post(route('employee.payments.collect.store', $payable), $this->payload())
                ->assertSessionHasErrors(['payment_method' => 'Khoản thu không ở trạng thái có thể thanh toán.']);
        }
        $payable->serviceOrderItem->update(['price_at_order' => 0]);
        $payable->update(['payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'amount' => 0]);
        $this->post(route('employee.payments.collect.store', $payable), $this->payload())
            ->assertSessionHasErrors(['payment_method' => 'Khoản thu không hợp lệ hoặc đã có giao dịch thành công.']);
        $this->assertSame(0, $payable->transactions()->count());
    }

    public function test_missing_service_item_is_rejected_by_cashier_and_admin_without_financial_writes(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $payable = $this->payable();
        $payable->update(['payment_type' => 'SERVICE_FEE', 'service_order_item_id' => null]);
        $transactions = $payable->transactions()->count();
        $audits = DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count();
        $queues = DB::table('examination_room_queues')->count();
        $this->actingAs($this->cashier())->post(route('employee.payments.collect.store', $payable), $this->payload())
            ->assertSessionHasErrors('payment_method');
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.payables.payment-transactions.store', $payable), $this->payload([
                'collected_by_employee_id' => $this->cashier()->employee->id,
            ]))->assertSessionHasErrors('payment_method');
        $this->assertSame('UNPAID', $payable->fresh()->paymentStatus->name);
        $this->assertSame($transactions, $payable->transactions()->count());
        $this->assertSame($audits, DB::table('audit_logs')->where('entity_type', 'payment_transactions')->count());
        $this->assertSame($queues, DB::table('examination_room_queues')->count());
    }

    public function test_service_item_from_another_visit_or_cancelled_is_rejected(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $payable = $this->payable();
        $original = \App\Models\ServiceOrderItem::whereHas('serviceOrder', fn ($q) => $q->where('medical_visit_id', '!=', $payable->medical_visit_id))->firstOrFail();
        $order = $original->serviceOrder->replicate();
        $order->service_order_code = 'TEST-F08-ORDER';
        $order->save();
        $item = $original->replicate();
        $item->service_order_id = $order->id;
        $item->save();
        $payable->update(['payment_type' => 'SERVICE_FEE', 'service_order_item_id' => $item->id]);
        $this->actingAs($this->cashier())->post(route('employee.payments.collect.store', $payable), $this->payload())
            ->assertSessionHasErrors('payment_method');
        $payable->update(['medical_visit_id' => $item->serviceOrder->medical_visit_id]);
        $item->update(['service_status_id' => \App\Models\ServiceStatus::where('name', 'CANCELLED')->firstOrFail()->id]);
        $this->post(route('employee.payments.collect.store', $payable), $this->payload())->assertSessionHasErrors('payment_method');
        $this->assertSame('UNPAID', $payable->fresh()->paymentStatus->name);
        $this->assertSame(0, $payable->transactions()->where('transaction_result', 'SUCCESS')->count());
    }

    public function test_admin_without_employee_selects_cashier_and_audit_records_actual_actor(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $cashier = $this->cashier();
        $payable = $this->payable();
        $amount = $payable->amount;
        $this->assertNull($admin->employee);
        $this->actingAs($admin)->post(route('admin.payables.payment-transactions.store', $payable), $this->payload([
            'collected_by_employee_id' => $cashier->employee->id,
            'amount' => 1,
            'payment_status_id' => PaymentStatus::where('name', 'CANCELLED')->value('id'),
        ]))->assertSessionHasNoErrors();
        $transaction = $payable->fresh()->transactions()->where('transaction_result', 'SUCCESS')->sole();
        $this->assertSame((string) $amount, (string) $transaction->amount);
        $this->assertSame($cashier->employee->id, $transaction->collected_by_employee_id);
        $this->assertSame('PAID', $payable->fresh()->paymentStatus->name);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'entity_type' => 'payment_transactions', 'entity_id' => $transaction->id]);
        $this->actingAs($cashier)->get(route('employee.payments.receipt', $transaction))
            ->assertOk()->assertSee($cashier->employee->full_name);
    }

    public function test_inactive_cashier_cannot_be_selected_by_admin_or_collect(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh'));
        $payable = $this->payable();
        $cashier = $this->cashier();
        $cashier->employee->update(['status' => 'INACTIVE']);
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->post(route('admin.payables.payment-transactions.store', $payable), $this->payload([
                'collected_by_employee_id' => $cashier->employee->id,
            ]))->assertSessionHasErrors('collected_by_employee_id');
        $this->flushSession();
        $this->actingAsEmployeeWorkspace($cashier, 'cashier')->post(route('employee.payments.collect.store', $payable), $this->payload())->assertForbidden();
        $this->assertSame('UNPAID', $payable->fresh()->paymentStatus->name);
        $this->assertSame(0, $payable->transactions()->where('transaction_result', 'SUCCESS')->count());
    }
}
