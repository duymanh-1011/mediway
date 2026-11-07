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

    public function test_detail_combines_payable_and_transaction_without_conflating_status_or_amount(): void
    {
        $payable = Payable::whereHas('transactions')->with('transactions')->firstOrFail();
        $transaction = $payable->transactions->first();
        \Illuminate\Support\Facades\DB::table('payment_transactions')->where('id', $transaction->id)->update([
            'transaction_result' => 'FAILED', 'amount' => 12345, 'collected_by_employee_id' => null,
        ]);
        \Illuminate\Support\Facades\DB::table('payables')->where('id', $payable->id)->update(['amount' => 67890, 'notes' => 'Ghi chú đối chiếu riêng']);
        $this->actingAs($this->account('NV000004'));
        foreach (['UNPAID' => 'Chưa thanh toán', 'PENDING' => 'Chờ xác nhận', 'PAID' => 'Đã thanh toán', 'CANCELLED' => 'Đã hủy'] as $state => $label) {
            $status = PaymentStatus::where('name', $state)->firstOrFail();
            \Illuminate\Support\Facades\DB::table('payables')->where('id', $payable->id)->update(['payment_status_id' => $status->id]);
            $this->get(route('employee.payments.show', ['payable' => $payable, 'q' => 'kiemthu', 'page' => 2]))
                ->assertOk()->assertSee('Thông tin khoản thu và thanh toán')->assertSee($label)
                ->assertSee('Thất bại')->assertSee('67.890')->assertSee('12.345')->assertSee('Không ghi nhận / tự động')
                ->assertSee('Ghi chú đối chiếu riêng')->assertSee('q=kiemthu')->assertSee('page=2')
                ->assertDontSee('Thông tin giao dịch gần nhất');
        }
        $cashier = $this->account('NV000002');
        $cashier->employee->detail()->update(['employee_type' => 'SERVICE_TECHNICIAN']);
        $this->flushSession();
        $this->actingAs($cashier)->get(route('employee.payments.show', $payable))->assertForbidden();
    }

    public function test_detail_retains_expandable_history_and_restricts_receipt_links(): void
    {
        $transactions = PaymentTransaction::where('payment_method', 'CASH')->whereHas('payable')->take(2)->get();
        $payable = $transactions[0]->payable;
        \Illuminate\Support\Facades\DB::table('payment_transactions')->where('id', $transactions[0]->id)->update(['payable_id' => $payable->id, 'transaction_result' => 'SUCCESS']);
        \Illuminate\Support\Facades\DB::table('payment_transactions')->where('id', $transactions[1]->id)->update(['payable_id' => $payable->id, 'transaction_result' => 'FAILED']);
        $response = $this->actingAs($this->account('NV000004'))->get(route('employee.payments.show', $payable))->assertOk()
            ->assertSee('<details>', false)->assertSee($transactions[0]->transaction_code)->assertSee($transactions[1]->transaction_code)
            ->assertSee('Thất bại')->assertSee('Thành công')->assertSee(route('employee.payments.receipt', $transactions[0]->id), false)
            ->assertDontSee(route('employee.payments.receipt', $transactions[1]->id), false);
    }

    public function test_detail_without_transactions_or_notes_has_no_receipt_or_history(): void
    {
        $payable = Payable::whereDoesntHave('transactions')->firstOrFail();
        \Illuminate\Support\Facades\DB::table('payables')->where('id', $payable->id)->update(['notes' => null]);
        $this->actingAs($this->account('NV000004'))->get(route('employee.payments.show', $payable))->assertOk()
            ->assertSee('Thông tin khoản thu và thanh toán')->assertDontSee('Xem hóa đơn')
            ->assertDontSee('Lịch sử giao dịch (')->assertDontSee('<h3>Ghi chú</h3>', false);
    }

    public function test_cashier_can_open_real_payables_list(): void
    {
        $this->actingAs($this->account('NV000004'))
            ->get(route('employee.payments.index'))
            ->assertOk()
            ->assertSee('Danh sách khoản phải thu')
            ->assertSee('PT000012');
    }

    public function test_list_and_detail_show_compact_real_payment_content(): void
    {
        $cashier = $this->account('NV000004');
        $item = \App\Models\ServiceOrderItem::with(['medicalService', 'payable'])->whereHas('medicalService')->whereHas('payable')->firstOrFail();
        $payable = $item->payable;
        $this->actingAs($cashier)->get(route('employee.payments.index', ['q' => $payable->payable_code]))
            ->assertOk()->assertSee('Phí dịch vụ '.$item->medicalService->name)->assertDontSee('Mã khoản thu / lượt khám');
        $this->get(route('employee.payments.show', $payable))
            ->assertOk()->assertSee('Phí dịch vụ '.$item->medicalService->name)->assertDontSee('Mã lượt khám');
        $this->assertSame('Phí khám', (new Payable(['payment_type' => 'EXAMINATION_FEE']))->displayContent());
        $this->assertSame('Tiền thuốc', (new Payable(['payment_type' => 'MEDICINE_FEE']))->displayContent());
    }

    public function test_other_employee_types_cannot_open_cashier_list(): void
    {
        foreach (['SERVICE_TECHNICIAN', 'RECEPTIONIST'] as $type) {
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

        $cashier->employee->detail()->update(['employee_type' => 'SERVICE_TECHNICIAN']);
        $this->get(route('employee.payments.index'))->assertForbidden();
    }

    public function test_search_escapes_html_and_does_not_treat_sql_as_a_query(): void
    {
        $this->actingAs($this->account('NV000004'));
        $script = '<script>alert(1)</script>';
        $this->get(route('employee.payments.index', ['q' => $script]))
            ->assertOk()->assertSee(e($script), false)->assertDontSee($script, false)
            ->assertSee('Không tìm thấy khoản phải thu');
        $this->get(route('employee.payments.index', ['q' => "' OR 1=1 --"]))
            ->assertOk()->assertSee('Không tìm thấy khoản phải thu')->assertDontSee('PT000012');
    }

    public function test_paginated_payables_do_not_issue_a_query_per_row(): void
    {
        $this->actingAs($this->account('NV000004'));
        \Illuminate\Support\Facades\DB::enableQueryLog();
        \Illuminate\Support\Facades\DB::flushQueryLog();
        $this->get(route('employee.payments.index', ['q' => 'PT000012']))->assertOk()->assertSee('PT000012');
        $single = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::flushQueryLog();
        $response = $this->get(route('employee.payments.index'))->assertOk();
        $many = count(\Illuminate\Support\Facades\DB::getQueryLog());
        $this->assertGreaterThan(1, $response->viewData('payables')->count());
        $this->assertLessThanOrEqual($single + 2, $many, 'Query count grew with the number of payables.');
    }

    public function test_combined_filters_return_only_the_matching_unpaid_service_fee(): void
    {
        $status = PaymentStatus::where('name', 'UNPAID')->firstOrFail();
        $response = $this->actingAs($this->account('NV000004'))->get(route('employee.payments.index', [
            'q' => 'PT000012', 'payment_type' => 'SERVICE_FEE', 'payment_status_id' => $status->id,
            'date_from' => '2026-10-15', 'date_to' => '2026-10-15',
        ]))->assertOk()->assertSee('PT000012')->assertDontSee('PT000011');
        $this->assertSame(1, $response->viewData('payables')->total());
    }

    public function test_pagination_limits_to_ten_and_retains_filter_query(): void
    {
        $response = $this->actingAs($this->account('NV000004'))->get(route('employee.payments.index', ['date_from' => '2026-10-01']))->assertOk();
        $paginator = $response->viewData('payables');
        $this->assertSame(10, $paginator->perPage());
        $this->assertCount(10, $paginator->items());
        $this->assertGreaterThan(10, $paginator->total());
        $this->assertStringContainsString('date_from=2026-10-01', $paginator->nextPageUrl());
        $second = $this->get($paginator->nextPageUrl())->assertOk()->viewData('payables');
        $this->assertLessThanOrEqual(10, $second->count());
        $this->assertNotEquals($paginator->items()[0]->id, $second->items()[0]->id);
    }
}
