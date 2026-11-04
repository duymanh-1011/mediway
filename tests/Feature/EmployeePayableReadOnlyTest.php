<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePayableReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    private function account(string $code): User
    {
        return User::where('business_code', $code)->firstOrFail();
    }

    public function test_cashier_can_open_real_payables_list(): void
    {
        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index'))
            ->assertOk()
            ->assertSee('Danh sách khoản phải thu')
            ->assertSee('PT000012');
    }

    public function test_other_employee_types_cannot_open_cashier_list(): void
    {
        foreach (['LAB_TECHNICIAN', 'IMAGING_TECHNICIAN', 'RECEPTIONIST'] as $type) {
            $user = $this->account('NV000002');
            $user->employee->detail()->update(['employee_type' => $type]);
            $this->actingAs($user)
                ->get(route('employee.payments.index'))
                ->assertForbidden();
        }
    }

    public function test_guest_patient_and_admin_cannot_open_cashier_list(): void
    {
        $this->get(route('employee.payments.index'))
            ->assertRedirect(route('patient.login'));

        foreach (['BN000001', 'QT000001'] as $code) {
            $this->flushSession();
            $this->actingAs($this->account($code))
                ->get(route('employee.payments.index'))
                ->assertForbidden();
        }
    }

    public function test_search_returns_only_matching_payable(): void
    {
        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index', ['q' => 'PT000012']))
            ->assertOk()
            ->assertSee('PT000012')
            ->assertDontSee('PT000011');
    }

    public function test_payment_type_and_status_filters_work(): void
    {
        $unpaid = PaymentStatus::where('name', 'UNPAID')->firstOrFail();
        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index', [
                'payment_type' => 'SERVICE_FEE',
                'payment_status_id' => $unpaid->id,
            ]))
            ->assertOk()
            ->assertSee('PT000012')
            ->assertDontSee('PT000010')
            ->assertDontSee('PT000004');
    }

    public function test_date_filter_returns_empty_list_outside_seeded_dates(): void
    {
        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index', [
                'date_from' => '2026-10-16',
                'date_to' => '2026-10-17',
            ]))
            ->assertOk()
            ->assertSee('Không tìm thấy khoản phải thu')
            ->assertDontSee('PT000012');
    }

    public function test_invalid_search_is_rejected_by_validation(): void
    {
        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index', ['q' => str_repeat('A', 81)]))
            ->assertSessionHasErrors('q');
    }

    public function test_invalid_status_and_reversed_dates_are_rejected(): void
    {
        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index', ['payment_status_id' => 999999]))
            ->assertSessionHasErrors('payment_status_id');

        $this->get(route('employee.payments.index', [
            'date_from' => '2026-10-17',
            'date_to' => '2026-10-15',
        ]))->assertSessionHasErrors('date_to');
    }

    public function test_read_only_requests_do_not_change_money_or_transactions(): void
    {
        $before = Payable::orderBy('id')
            ->get(['id', 'amount', 'payment_status_id'])->toArray();
        $transactionCount = PaymentTransaction::count();

        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index', ['q' => 'PT000012']))
            ->assertOk();

        $this->assertSame($before, Payable::orderBy('id')
            ->get(['id', 'amount', 'payment_status_id'])->toArray());
        $this->assertSame($transactionCount, PaymentTransaction::count());
    }

    public function test_cashier_loses_access_immediately_after_type_change(): void
    {
        $cashier = $this->account('NV000004');
        $this->actingAs($cashier)
            ->get(route('employee.payments.index'))->assertOk();

        $cashier->employee->detail()->update(['employee_type' => 'LAB_TECHNICIAN']);
        $this->get(route('employee.payments.index'))->assertForbidden();
    }
}
