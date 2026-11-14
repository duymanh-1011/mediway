<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\ExaminationRoomQueue;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeReceptionReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->withSession(['employee_workspace_mode' => 'reception']);
    }

    private function receptionist(): User
    {
        return Employee::where('employee_code', 'NV000001')->firstOrFail()->user;
    }

    public function test_receptionist_can_filter_and_view_appointments_without_exposing_clinical_information(): void
    {
        $appointment = Appointment::with('patient')->firstOrFail();
        $this->actingAs($this->receptionist())->post(route('employee.appointments.filter'), ['q' => $appointment->appointment_code])
            ->assertRedirect(route('employee.appointments.index'));
        $this->get(route('employee.appointments.index'))->assertOk()->assertSee($appointment->appointment_code)
            ->assertSee($appointment->patient->full_name)->assertDontSee('Nội dung khám');
        $this->get(route('employee.appointments.show', $appointment))->assertOk()->assertSee($appointment->appointment_code)
            ->assertDontSee('Lý do khám')->assertDontSee('Xác nhận tiếp nhận</button>', false);
        $this->post(route('employee.appointments.filter'), ['q' => str_repeat('a', 121)])->assertSessionHasErrors('q');
        $this->post(route('employee.appointments.filter'), ['q' => 'KHONGTIMTHAY123'])->assertRedirect();
        $this->get(route('employee.appointments.index'))->assertOk()->assertSee('Không tìm thấy lịch hẹn phù hợp.');
    }

    public function test_receptionist_cannot_access_the_clinical_queue(): void
    {
        $queue = ExaminationRoomQueue::firstOrFail();
        $this->actingAs($this->receptionist())->get(route('employee.reception.index'))
            ->assertOk()->assertDontSee('Hàng đợi hành chính')->assertDontSee('/nhan-vien/hang-doi', false);
        $this->get('/nhan-vien/hang-doi')->assertNotFound();
        $this->post('/nhan-vien/hang-doi/loc')->assertNotFound();
        $this->post(route('admin.queues.call', $queue))->assertRedirect(route('admin.login'));
    }

    public function test_role_and_current_employee_type_are_enforced_on_each_request(): void
    {
        $this->get(route('employee.appointments.index'))->assertRedirect(route('patient.login'));
        foreach (['NV000002', 'NV000003', 'NV000004'] as $code) {
            $this->flushSession();
            $user = Employee::where('employee_code', $code)->firstOrFail()->user;
            $this->actingAsEmployeeWorkspace($user, 'reception')->get(route('employee.appointments.index'))->assertOk();
        }
        foreach (['QT000001', 'BN000001', 'BS000001'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())
                ->get(route('employee.appointments.index'))->assertForbidden();
        }
        $user = $this->receptionist();
        $this->flushSession();
        $this->actingAsEmployeeWorkspace($user, 'reception')->get(route('employee.appointments.index'))->assertOk();
        $user->employee->detail->update(['employee_type' => 'CASHIER']);
        $this->get(route('employee.appointments.index'))->assertOk();
        $this->post(route('employee.appointments.filter'), ['q' => ''])->assertRedirect();
        $this->flushSession();
        $this->actingAsEmployeeWorkspace($user, 'cashier')->get(route('employee.appointments.index'))->assertForbidden();
    }
}
