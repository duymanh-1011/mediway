<?php

namespace Tests\Feature\Portal;

use App\Models\Payable;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            DatabaseSeeder::class
        );

        $this->withoutVite();
    }

    private function patientUser(
        string $code = 'BN000001'
    ): User {
        return User::query()
            ->where(
                'business_code',
                $code
            )
            ->firstOrFail();
    }

    public function test_patient_can_open_payment_page(): void
    {
        $this->actingAs(
            $this->patientUser()
        )
            ->get(
                route(
                    'patient.payments.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'Khoản cần thanh toán'
            );
    }

    public function test_unknown_payment_status_is_not_assumed_to_be_unpaid(): void
    {
        $user = $this->patientUser();
        $payable = Payable::whereHas('medicalVisit', fn ($q) => $q->where('patient_id', $user->patient->id))->firstOrFail();
        $statusId = \Illuminate\Support\Facades\DB::table('payment_statuses')->insertGetId([
            'name' => 'UNRECOGNIZED', 'description' => 'Chưa xác định', 'allows_service_execution' => false,
            'process_order' => 99, 'is_active' => true,
        ]);
        $payable->update(['payment_status_id' => $statusId, 'amount' => 123456]);
        $this->actingAs($user)->get(route('patient.payments.index'))->assertOk()
            ->assertSee('Có khoản thu chưa xác định được trạng thái')
            ->assertViewHas('summary', fn ($summary) => $summary['unknown_status_count'] === 1
                && $summary['outstanding_amount'] == 0);
    }

    public function test_patient_only_sees_own_payables(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $ownPayable =
            Payable::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    $user->patient->id
                )
            )
            ->firstOrFail();

        $otherPayable =
            Payable::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    '!=',
                    $user->patient->id
                )
            )
            ->firstOrFail();

        $response =
            $this->actingAs(
                $user
            )
            ->get(
                route(
                    'patient.payments.index'
                )
            );

        $response
            ->assertOk()
            ->assertSee(
                $ownPayable
                    ->payable_code
            )
            ->assertDontSee(
                $otherPayable
                    ->payable_code
            );
    }

    public function test_patient_only_sees_transactions_from_own_payables(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $ownPayable =
            Payable::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    $user->patient->id
                )
            )
            ->whereHas(
                'transactions'
            )
            ->with(
                'transactions'
            )
            ->firstOrFail();

        $otherPayable =
            Payable::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    '!=',
                    $user->patient->id
                )
            )
            ->whereHas(
                'transactions'
            )
            ->with(
                'transactions'
            )
            ->firstOrFail();

        $ownTransaction =
            $ownPayable
            ->transactions
            ->firstOrFail();

        $otherTransaction =
            $otherPayable
            ->transactions
            ->firstOrFail();

        $response =
            $this->actingAs(
                $user
            )
            ->get(
                route(
                    'patient.payments.index'
                )
            );

        $response
            ->assertOk()
            ->assertSee(
                $ownTransaction
                    ->transaction_code
            )
            ->assertDontSee(
                $otherTransaction
                    ->transaction_code
            );
    }

    public function test_collectors_belong_to_each_transaction_and_summary_uses_latest_success(): void
    {
        $user = $this->patientUser();
        $payable = Payable::whereHas('medicalVisit', fn ($query) => $query->where('patient_id', $user->patient->id))
            ->whereHas('transactions')->with('transactions')->firstOrFail();
        $collectors = Employee::orderBy('id')->take(2)->get();
        $this->assertCount(2, $collectors);

        $success = $payable->transactions->first();
        $success->update(['transaction_result' => 'SUCCESS', 'collected_by_employee_id' => $collectors[0]->id, 'confirmed_at' => now()]);
        $failed = $success->replicate();
        $failed->fill(['transaction_code' => 'GD-TEST-FAILED', 'transaction_result' => 'FAILED', 'collected_by_employee_id' => $collectors[1]->id, 'confirmed_at' => now()->addMinute()])->save();

        $response = $this->actingAs($user)->get(route('patient.payments.index'))->assertOk()
            ->assertDontSee('Hạn thanh toán')
            ->assertSee('Nhân viên thu tiền gần nhất')
            ->assertSee($collectors[0]->full_name)
            ->assertSee($collectors[1]->full_name);

        $response->assertSeeInOrder(['Nhân viên thu tiền gần nhất', $collectors[0]->full_name, 'GD-TEST-FAILED', $collectors[1]->full_name]);
    }

    public function test_missing_collector_is_displayed_without_guessing(): void
    {
        $user = $this->patientUser();
        $payable = Payable::whereHas('medicalVisit', fn ($query) => $query->where('patient_id', $user->patient->id))
            ->whereHas('transactions')->with('transactions')->firstOrFail();
        $payable->transactions()->update(['collected_by_employee_id' => null]);

        $this->actingAs($user)->get(route('patient.payments.index'))->assertOk()
            ->assertSee('Nhân viên thu tiền gần nhất')
            ->assertSee('Chưa có thông tin');
    }

    public function test_opening_payment_page_does_not_change_amount_or_status(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $payable =
            Payable::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    $user->patient->id
                )
            )
            ->withCount(
                'transactions'
            )
            ->firstOrFail();

        $amountBefore =
            (string) $payable->amount;

        $statusBefore =
            $payable->payment_status_id;

        $transactionsBefore =
            $payable->transactions_count;

        $this->actingAs(
            $user
        )
            ->get(
                route(
                    'patient.payments.index'
                )
            )
            ->assertOk();

        $payable =
            $payable->fresh();

        $this->assertSame(
            $amountBefore,
            (string) $payable->amount
        );

        $this->assertSame(
            $statusBefore,
            $payable->payment_status_id
        );

        $this->assertSame(
            $transactionsBefore,
            $payable
                ->transactions()
                ->count()
        );
    }

    public function test_patient_payment_module_has_no_mutation_routes(): void
    {
        $this->assertFalse(
            Route::has(
                'patient.payments.store'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.payments.update'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.payments.cancel'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.payments.update-status'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.payments.transactions.store'
            )
        );

        $this->assertFalse(
            Route::has(
                'patient.payments.checkout'
            )
        );
    }

    public function test_payment_page_does_not_expose_internal_payment_fields(): void
    {
        $user =
            $this->patientUser(
                'BN000001'
            );

        $payable =
            Payable::query()
            ->whereHas(
                'medicalVisit',
                fn($query) =>
                $query->where(
                    'patient_id',
                    $user->patient->id
                )
            )
            ->whereHas(
                'transactions'
            )
            ->with(
                'transactions'
            )
            ->firstOrFail();

        $transaction =
            $payable
            ->transactions
            ->firstOrFail();

        $response =
            $this->actingAs(
                $user
            )
            ->get(
                route(
                    'patient.payments.index'
                )
            )
            ->assertOk();

        if (
            filled(
                $payable->payment_qr_data
            )
        ) {
            $response->assertDontSee(
                $payable->payment_qr_data
            );
        }

        if (
            filled(
                $transaction
                    ->provider_transaction_code
            )
        ) {
            $response->assertDontSee(
                $transaction
                    ->provider_transaction_code
            );
        }

        if (
            filled(
                $transaction
                    ->failure_reason
            )
        ) {
            $response->assertDontSee(
                $transaction
                    ->failure_reason
            );
        }

        if (
            filled(
                $transaction
                    ->notes
            )
        ) {
            $response->assertDontSee(
                $transaction
                    ->notes
            );
        }

        $this->assertTrue(
            true
        );
    }

    public function test_non_patient_account_cannot_open_payment_page(): void
    {
        $admin =
            User::query()
            ->where(
                'business_code',
                'QT000001'
            )
            ->firstOrFail();

        $response =
            $this->actingAs(
                $admin
            )
            ->get(
                route(
                    'patient.payments.index'
                )
            );

        $response->assertStatus(
            302
        );

        $this->assertSame(
            route(
                'patient.login'
            ),
            $response
                ->baseResponse
                ->headers
                ->get(
                    'Location'
                )
        );
    }
}
