<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Employee;
use App\Models\ExaminationRoom;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PaymentStatus;
use App\Models\Specialty;
use App\Models\User;
use Tests\Fixtures\Seeders\WorkflowFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class WalkInReceptionTest extends TestCase
{
    use RefreshDatabase;

    private DoctorSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(9, 0));
        $this->seed(WorkflowFixtureSeeder::class);
        $this->withoutVite();
        $this->withSession(['employee_workspace_mode' => 'reception']);
        $this->schedule = DoctorSchedule::create([
            'doctor_id' => Doctor::where('doctor_code', 'BS000001')->value('id'),
            'specialty_id' => Specialty::where('specialty_code', 'CKTMH')->value('id'),
            'examination_room_id' => ExaminationRoom::where('room_code', 'PKTMH01')->value('id'),
            'work_date' => today()->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'ACTIVE',
        ]);
    }

    private function receptionist(): User
    {
        return Employee::where('employee_code', 'NV000001')->firstOrFail()->user;
    }

    public function test_reception_dashboard_and_appointment_list_have_distinct_purposes(): void
    {
        $this->actingAs($this->receptionist())->get(route('employee.reception.index'))->assertOk()
            ->assertSee('Tiếp đón bệnh nhân')->assertSee('Tiếp nhận khám trực tiếp')
            ->assertSee('Xem lịch hẹn và tiếp nhận')->assertSee(route('employee.appointments.filter'))
            ->assertDontSee('<span>Lịch hẹn và tiếp nhận</span>', false);
        $this->post(route('employee.appointments.filter'), ['reset' => 1])->assertRedirect(route('employee.appointments.index'));
        $this->get(route('employee.appointments.index'))->assertOk()->assertSee('Lịch hẹn tiếp đón')
            ->assertSee('class="employee-nav__item is-active" href="'.route('employee.reception.index').'"', false)
            ->assertDontSee('Khám trực tiếp tại quầy');
        $this->get(route('employee.walk-in.create'))->assertOk()->assertSee('Tìm hồ sơ bệnh nhân')->assertSee('Tạo hồ sơ bệnh nhân mới');
    }

    public function test_admin_can_receive_an_existing_patient_and_cashier_can_collect_the_fee(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $patient->detail()->update(['citizen_id_number' => null]);
        $page = route('admin.visits.walk-in.create');
        $store = route('admin.visits.walk-in.store');
        $payload = [
            'patient_mode' => 'existing', 'patient_id' => $patient->id, 'schedule_id' => $this->schedule->id,
            'confirm_reception' => 1, 'request_token' => (string) Str::uuid(),
        ];

        $this->actingAs($admin)->get(route('admin.visits.index'))->assertOk()->assertSee($page);
        $this->get($page)->assertOk()->assertSee('Tiếp nhận khám trực tiếp')->assertSee('Quay lại lượt khám')
            ->assertSee($this->schedule->examinationRoom->name);
        $this->from($page)->post($store, $payload)->assertSessionHasErrors('patient_id');
        $this->post(route('admin.visits.walk-in.search'), ['identifier' => $patient->user->phone])->assertRedirect($page);
        $this->get($page)->assertOk()->assertSee('Tìm thấy 1 hồ sơ bệnh nhân')
            ->assertSee('value="'.$patient->id.'" selected', false)
            ->assertSee('data-patient-field="fullName" value="'.$patient->full_name.'"', false);

        $response = $this->post($store, $payload)->assertSessionHasNoErrors();
        $visit = $patient->medicalVisits()->where('reception_source', 'WALK_IN')->sole();
        $response->assertRedirect(route('admin.visits.show', $visit));
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee($visit->visit_code);
        $this->assertNull($visit->appointment_id);
        $this->assertSame(0, $visit->queues()->count());
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id, 'role_name_snapshot' => 'Administrator', 'action' => 'CHECK_IN',
            'entity_type' => 'MedicalVisit', 'entity_id' => $visit->id,
        ]);

        $cashier = Employee::where('employee_code', 'NV000004')->firstOrFail()->user;
        $this->actingAsEmployeeWorkspace($cashier, 'cashier')->post(route('employee.payments.collect.store', $fee), [
            'payment_method' => 'CASH', 'confirm_payment' => 1,
        ])->assertRedirect(route('employee.payments.show', $fee))->assertSessionHasNoErrors();
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame('WAITING', $visit->queues()->sole()->status->name);
    }

    public function test_admin_can_create_a_walk_in_patient_and_account(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $name = 'Bệnh Nhân Mới Tiếp Nhận Admin';
        $response = $this->actingAs($admin)->post(route('admin.visits.walk-in.store'), [
            'patient_mode' => 'new', 'full_name' => $name, 'date_of_birth' => '2000-01-02', 'gender' => 'Nữ',
            'citizen_id_number' => '123456789012', 'phone' => '0909888999', 'confirm_search' => 1, 'schedule_id' => $this->schedule->id,
            'confirm_reception' => 1, 'request_token' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();

        $patient = Patient::where('full_name', $name)->sole();
        $this->assertSame('0909888999', $patient->user->phone);
        $this->assertTrue($patient->user->must_change_password);
        $this->assertSame('123456789012', $patient->detail->citizen_id_number);
        $visit = $patient->medicalVisits()->where('reception_source', 'WALK_IN')->sole();
        $visitPage = route('admin.visits.show', $visit);
        $response->assertRedirect($visitPage)->assertSessionMissing('walk_in_credentials_'.$visit->id);
        $slip = route('admin.visits.reception-slip', $visit);
        $this->get($visitPage)->assertOk()->assertDontSee('Tài khoản bệnh nhân vừa tạo')->assertDontSee('data-walk-in-password', false)->assertSee($slip);
        $this->get($slip)->assertOk()->assertSee('PHIẾU KHÁM BỆNH')->assertSee($patient->patient_code)
            ->assertSeeInOrder(['Số thứ tự khám', 'Hà Nội, ngày 23 tháng 10 năm 2026 lúc 09:00', 'Người tiếp đón', $admin->administrator->full_name]);
        $this->get(route('admin.visits.walk-in.credentials', $visit))->assertRedirect($visitPage);
        $this->get(route('admin.users.index', ['search' => $patient->user->phone]))->assertOk()->assertSee($patient->patient_code);

        $issue = route('employee.walk-in.temporary-password', $patient);
        $this->post($issue, ['confirm_identity' => 1])->assertForbidden();
        $this->post(route('admin.logout'));

        $receptionPage = route('employee.walk-in.create');
        $this->actingAsEmployeeWorkspace($this->receptionist(), 'reception')->post($issue, ['confirm_identity' => 1])->assertNotFound();
        $this->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code])->assertRedirect($receptionPage);
        $this->get($receptionPage)->assertOk()->assertSee('Cấp mật khẩu tạm');
        $this->from($receptionPage)->post($issue)->assertSessionHasErrors('confirm_identity');
        $credentials = route('employee.walk-in.credentials', $visit);
        $this->post($issue, ['confirm_identity' => 1])->assertRedirect($credentials)->assertSessionHasNoErrors();
        $preview = $this->get($credentials)->assertOk()->assertSee('Mật khẩu tạm của bệnh nhân');
        $this->assertSame(1, preg_match('/<code data-walk-in-password>([a-zA-Z0-9]{16})<\/code>/', $preview->getContent(), $matches));
        $this->assertTrue(Hash::check($matches[1], $patient->user->fresh()->password));
        $this->get($credentials)->assertRedirect(route('employee.walk-in.show', $visit));
    }

    public function test_admin_walk_in_routes_reject_guests_and_receptionists(): void
    {
        $login = route('admin.login');
        $page = route('admin.visits.walk-in.create');
        $search = route('admin.visits.walk-in.search');
        $store = route('admin.visits.walk-in.store');

        $this->get($page)->assertRedirect($login);
        $this->post($search)->assertRedirect($login);
        $this->post($store)->assertRedirect($login);
        $receptionist = $this->receptionist();
        $this->actingAs($receptionist)->get($page)->assertRedirect($login);
        $this->actingAs($receptionist)->post($search, ['identifier' => 'BN000005'])->assertRedirect($login);
        $this->actingAs($receptionist)->post($store)->assertRedirect($login);
    }

    public function test_registration_with_a_counter_code_reuses_the_walk_in_profile_and_its_visit(): void
    {
        $citizenId = '012345678912';
        $originalName = 'Bệnh Nhân Tạo Tại Quầy';
        $page = route('employee.walk-in.create');
        $patient = Patient::create(['patient_code' => 'BN990002', 'full_name' => $originalName, 'status' => 'ACTIVE']);
        $patient->detail()->create(['citizen_id_number' => $citizenId]);
        $visit = $patient->medicalVisits()->create([
            'visit_code' => 'LK-LEGACY-TEST', 'doctor_id' => $this->schedule->doctor_id, 'specialty_id' => $this->schedule->specialty_id,
            'examination_room_id' => $this->schedule->examination_room_id, 'checked_in_at' => now()->subDay(), 'reception_source' => 'WALK_IN',
            'visit_status_id' => \App\Models\VisitStatus::where('name', 'COMPLETED')->value('id'),
        ]);
        $issue = route('employee.walk-in.activation', $patient);
        $this->actingAs($this->receptionist())->post($issue, ['confirm_identity' => 1])->assertNotFound();
        $this->post(route('employee.walk-in.search'), ['identifier' => $citizenId])->assertRedirect($page);
        $this->get($page)->assertSee('Cấp mã kích hoạt tài khoản')->assertSee($issue);
        $this->from($page)->post($issue)->assertSessionHasErrors('confirm_identity');
        $this->post($issue, ['confirm_identity' => 1])->assertRedirect($page)->assertSessionHasNoErrors();
        $code = session('employee.walk_in_patient_matches_activation.code');
        $this->assertMatchesRegularExpression('/^[A-F0-9]{20}$/', $code);
        $this->assertNotSame($code, DB::table('patient_registration_activations')->where('patient_id', $patient->id)->value('code_hash'));
        $preview = $this->get($page)->assertSee($code);
        $this->assertTrue($preview->baseResponse->headers->hasCacheControlDirective('no-store'));
        $this->get($page)->assertDontSee($code);
        $this->post(route('employee.logout'));

        $register = route('patient.register.store');
        $registration = [
            'full_name' => 'Tên khác khi đăng ký', 'phone' => '0909888999', 'citizen_id_number' => $citizenId,
            'password' => 'AnToan@123', 'password_confirmation' => 'AnToan@123',
        ];
        $this->get(route('patient.register'))->assertOk()->assertSee('name="citizen_id_number"', false);
        $this->post($register, $registration)->assertSessionHasErrors('activation_code');
        $this->post($register, [...$registration, 'citizen_id_number' => '012345678913', 'activation_code' => $code])
            ->assertSessionHasErrors('activation_code')->assertSessionMissing('_old_input.activation_code');
        $this->assertDatabaseMissing('users', ['phone' => $registration['phone']]);

        $patientCount = Patient::count();
        $this->post($register, [...$registration, 'activation_code' => strtolower($code)])->assertRedirect(route('patient.login'));
        $this->assertSame($patientCount, Patient::count());
        $user = User::where('phone', $registration['phone'])->sole();
        $this->assertSame($patient->id, $user->patient->id);
        $this->assertSame($patient->patient_code, $user->business_code);
        $this->assertSame($originalName, $user->patient->full_name);
        $this->assertDatabaseMissing('patient_registration_activations', ['patient_id' => $patient->id]);
        $this->post($register, [...$registration, 'phone' => '0909888998', 'activation_code' => $code])->assertSessionHasErrors('citizen_id_number');
        $this->post(route('patient.login.store'), ['phone' => $registration['phone'], 'password' => $registration['password']])
            ->assertRedirect(route('patient.home'));
        $this->get(route('patient.results.visit', $visit))->assertOk()->assertSee($visit->visit_code);
    }

    public function test_admin_activation_replaces_the_old_code_and_expires(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $citizenId = '012345678912';
        $patient = Patient::create(['patient_code' => 'BN990001', 'full_name' => 'Bệnh nhân chưa có tài khoản', 'status' => 'ACTIVE']);
        $patient->detail()->create(['citizen_id_number' => $citizenId]);
        $issue = route('admin.visits.walk-in.activation', $patient);
        $page = route('admin.visits.walk-in.create');
        $this->post($issue, ['confirm_identity' => 1])->assertRedirect(route('admin.login'));
        $this->actingAs($admin)->post(route('admin.visits.walk-in.search'), ['identifier' => $citizenId])->assertRedirect($page);
        $this->post($issue, ['confirm_identity' => 1])->assertRedirect($page);
        $oldCode = session('admin.walk_in_patient_matches_activation.code');
        $this->post($issue, ['confirm_identity' => 1])->assertRedirect($page);
        $newCode = session('admin.walk_in_patient_matches_activation.code');
        $this->assertNotSame($oldCode, $newCode);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'ISSUE_REGISTRATION_ACTIVATION', 'entity_id' => $patient->id]);
        $this->post(route('admin.logout'));

        $registration = [
            'full_name' => $patient->full_name, 'phone' => '0909888999', 'citizen_id_number' => $citizenId,
            'password' => 'AnToan@123', 'password_confirmation' => 'AnToan@123',
        ];
        $this->post(route('patient.register.store'), [...$registration, 'activation_code' => $oldCode])->assertSessionHasErrors('activation_code');
        $this->travel(24)->hours();
        $this->post(route('patient.register.store'), [...$registration, 'activation_code' => $newCode])->assertSessionHasErrors('activation_code');
        $this->assertNull($patient->fresh()->user_id);
        $this->assertDatabaseMissing('users', ['phone' => $registration['phone']]);
    }

    public function test_existing_patient_walk_in_requires_payment_then_receives_one_queue_number(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $citizenId = '012345678910';
        $patient->detail()->updateOrCreate([], ['citizen_id_number' => $citizenId]);
        $originalName = $patient->full_name;
        $identifier = strtolower($patient->patient_code);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.search'), ['identifier' => $identifier])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertOk()
            ->assertSee('value="'.$identifier.'"', false)->assertSee('Tìm thấy 1 hồ sơ bệnh nhân')
            ->assertSee('Chọn hồ sơ để tiếp nhận')->assertSee('value="'.$patient->id.'" selected', false)
            ->assertSee('data-citizen-id="'.$citizenId.'"', false)
            ->assertSeeInOrder(['data-patient-field="fullName" value="'.$originalName.'"',
                'data-patient-field="birthDate" value="'.$patient->date_of_birth->toDateString().'"',
                '<option value="'.$patient->gender.'" selected>',
                'data-patient-field="citizenId" value="'.$citizenId.'"'], false);
        $payload = ['patient_mode' => 'existing', 'patient_id' => $patient->id, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()];
        $this->post(route('employee.walk-in.store'), [...$payload, 'full_name' => 'Tên nhập nhầm',
            'date_of_birth' => '2099-01-01', 'gender' => 'không hợp lệ', 'citizen_id_number' => $citizenId])
            ->assertSessionHasNoErrors();
        $this->assertSame($originalName, $patient->fresh()->full_name);
        $visit = MedicalVisit::where('patient_id', $patient->id)->where('reception_source', 'WALK_IN')->sole();
        $this->assertNull($visit->appointment_id);
        $this->assertSame(0, $visit->queues()->count());
        $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
        $this->assertSame('UNPAID', $fee->paymentStatus->name);
        $this->get(route('employee.walk-in.show', $visit))->assertOk()->assertSee('cần thanh toán phí khám');
        $this->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code]);
        $this->post(route('employee.walk-in.temporary-password', $patient), ['confirm_identity' => 1])->assertSessionHasErrors('confirm_identity');
        $this->post(route('employee.walk-in.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code]);
        $this->post(route('employee.walk-in.store'), [...$payload, 'request_token' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasErrors('patient_id');
        $this->assertSame(1, MedicalVisit::where('patient_id', $patient->id)->where('reception_source', 'WALK_IN')->count());
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())->post(route('admin.payables.payment-transactions.store', $fee), [
            'payment_method' => 'CASH', 'confirm_payment' => 1, 'collected_by_employee_id' => Employee::where('employee_code', 'NV000004')->value('id'),
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('PAID', $fee->fresh()->paymentStatus->name);
        $this->assertSame(1, $visit->queues()->count());
        $this->assertSame('WAITING', $visit->queues()->sole()->status->name);
        $this->flushSession();
        $this->actingAsEmployeeWorkspace($this->receptionist(), 'reception')->get(route('employee.walk-in.show', $visit))->assertSee('Phí khám đã được thanh toán');
    }

    public function test_reception_creates_an_account_and_patient_must_change_temporary_password(): void
    {
        $phone = '0909888999';
        $response = $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), [
            'patient_mode' => 'new', 'patient_id' => 'không sử dụng', 'full_name' => 'Người Mới Kiểm Thử', 'date_of_birth' => '2000-01-02', 'gender' => 'Nữ',
            'citizen_id_number' => '123456789012', 'phone' => $phone, 'confirm_search' => 1, 'schedule_id' => $this->schedule->id,
            'current_address' => '12 Nguyễn Trãi', 'reason_for_visit' => 'Đau họng từ sáng',
            'confirm_reception' => 1, 'request_token' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();
        $patient = Patient::where('full_name', 'Người Mới Kiểm Thử')->sole();
        $user = $patient->user;
        $this->assertSame($phone, $user->phone);
        $this->assertSame($patient->patient_code, $user->business_code);
        $this->assertTrue($user->must_change_password);
        $this->assertSame('123456789012', $patient->detail->citizen_id_number);
        $visit = $patient->medicalVisits()->where('reception_source', 'WALK_IN')->sole();
        $credentials = route('employee.walk-in.credentials', $visit);
        $response->assertRedirect($credentials);
        $preview = $this->get($credentials)->assertOk()->assertSee($phone)
            ->assertSee('type="button" data-print-document', false)->assertSee('In thông tin đăng nhập')
            ->assertSee('Mediway · Thông tin đăng nhập')->assertSee('Bạn cần đặt mật khẩu mới trong lần đăng nhập đầu tiên.')
            ->assertSee(route('employee.reception.slip', $visit));
        $this->assertTrue($preview->baseResponse->headers->hasCacheControlDirective('no-store'));
        $this->assertSame(1, preg_match('/<code data-walk-in-password>([a-zA-Z0-9]{16})<\/code>/', $preview->getContent(), $matches));
        $temporaryPassword = $matches[1];
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->get(route('employee.reception.slip', $visit))->assertOk()->assertSee($phone)->assertSee('12 Nguyễn Trãi')
            ->assertSee('Đau họng từ sáng')->assertSee('02/01/2000')->assertSee('Nữ')->assertSee($patient->full_name)
            ->assertSee('class="reception-slip-number__value">'.$visit->reception_queue_number.'<', false)
            ->assertDontSee($temporaryPassword)->assertDontSee('123456789012');
        $this->get($credentials)->assertRedirect(route('employee.walk-in.show', $visit));
        $this->post(route('employee.logout'));

        $login = route('patient.login.store');
        $initial = route('patient.password.initial');
        $update = route('patient.password.initial.update');
        $results = route('patient.results.visit', $visit);
        $this->post($login, ['phone' => $phone, 'password' => $temporaryPassword])->assertRedirect($initial);
        $temporarySessionHash = session('patient_temporary_password_hash');
        $this->get($results)->assertRedirect($initial);
        $this->get($initial)->assertOk()->assertSee('Đổi mật khẩu lần đầu');
        $this->put($update, ['password' => $temporaryPassword, 'password_confirmation' => $temporaryPassword])->assertSessionHasErrors('password');
        $newPassword = 'MatKhauMoi@123';
        $this->put($update, ['password' => $newPassword, 'password_confirmation' => $newPassword])->assertRedirect(route('patient.home'));
        $this->assertFalse($user->fresh()->must_change_password);
        $this->get($results)->assertOk()->assertSee($visit->visit_code);
        $this->withSession(['patient_temporary_password_hash' => $temporarySessionHash])->get($results)->assertRedirect(route('patient.login'));
        $this->post($login, ['phone' => $phone, 'password' => $temporaryPassword])->assertSessionHasErrors('phone');
        $this->post($login, ['phone' => $phone, 'password' => $newPassword])->assertRedirect(route('patient.home'));
    }

    public function test_existing_unlinked_profile_is_reused_and_duplicate_phone_rolls_back(): void
    {
        $patient = Patient::create(['patient_code' => 'BN990003', 'full_name' => 'Hồ sơ cũ', 'status' => 'ACTIVE']);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code]);
        $store = route('employee.walk-in.store');
        $payload = [
            'patient_mode' => 'existing', 'patient_id' => $patient->id, 'citizen_id_number' => '123456789012',
            'date_of_birth' => '1985-03-12', 'gender' => 'Nữ',
            'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) Str::uuid(),
        ];
        $this->post($store, [...$payload, 'phone' => '0902000001'])->assertSessionHasErrors('phone');
        $this->assertNull($patient->fresh()->user_id);
        $this->assertFalse($patient->detail()->exists());
        $this->assertFalse($patient->medicalVisits()->exists());

        $response = $this->post($store, [...$payload, 'phone' => '0909888999'])->assertSessionHasNoErrors();
        $response->assertRedirect(route('employee.walk-in.credentials', $patient->medicalVisits()->sole()));
        $this->assertSame($patient->patient_code, $patient->fresh()->user->business_code);
        $this->assertSame($payload['citizen_id_number'], $patient->detail()->sole()->citizen_id_number);
    }

    public function test_room_numbers_follow_reception_order_even_when_paid_in_reverse_and_reset_next_day(): void
    {
        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        $cashier = Employee::where('employee_code', 'NV000004')->firstOrFail()->user;
        $receive = function (string $code, DoctorSchedule $schedule): MedicalVisit {
            $patient = Patient::where('patient_code', $code)->firstOrFail();
            $this->post(route('admin.visits.walk-in.search'), ['identifier' => $code])->assertRedirect();
            $this->post(route('admin.visits.walk-in.store'), [
                'patient_mode' => 'existing', 'patient_id' => $patient->id, 'schedule_id' => $schedule->id,
                'confirm_reception' => 1, 'request_token' => (string) Str::uuid(),
            ])->assertSessionHasNoErrors();

            return $patient->medicalVisits()->where('reception_source', 'WALK_IN')->latest('id')->firstOrFail();
        };

        $this->actingAs($admin);
        $first = $receive('BN000005', $this->schedule);
        $second = $receive('BN000001', $this->schedule);
        $this->assertSame($first->reception_queue_number + 1, $second->reception_queue_number);
        $this->assertSame(0, $first->queues()->count());

        $this->actingAsEmployeeWorkspace($cashier, 'cashier');
        foreach ([$second, $first] as $visit) {
            $fee = $visit->payables()->where('payment_type', 'EXAMINATION_FEE')->sole();
            $this->post(route('employee.payments.collect.store', $fee), ['payment_method' => 'CASH', 'confirm_payment' => 1])->assertSessionHasNoErrors();
            $this->assertSame($visit->reception_queue_number, (int) $visit->queues()->sole()->queue_number);
        }
        $this->actingAs($admin);
        $queuePage = route('admin.queues.index', ['examination_room_id' => $this->schedule->examination_room_id]);
        $this->get($queuePage)->assertOk()->assertSeeInOrder([$first->visit_code, $second->visit_code]);
        $queue = $first->queues()->sole();
        foreach (['call', 'start-service', 'complete'] as $action) {
            $this->post(route('admin.queues.'.$action, $queue))->assertSessionHasNoErrors()->assertSessionMissing('error');
        }
        $this->get($queuePage)->assertOk()->assertDontSee($first->visit_code)->assertSee($second->visit_code);
        $this->assertSame('COMPLETED', $queue->fresh()->status->name);

        $this->travel(1)->day();
        $nextSchedule = $this->schedule->replicate()->fill(['work_date' => today()->toDateString()]);
        $nextSchedule->save();
        $nextDay = $receive('BN000005', $nextSchedule);
        $this->assertSame(1, $nextDay->reception_queue_number);
        $this->get($queuePage)->assertOk()->assertDontSee($second->visit_code);
    }

    public function test_repeated_new_patient_submission_does_not_duplicate_a_record(): void
    {
        $token = (string) \Illuminate\Support\Str::uuid();
        $payload = ['patient_mode' => 'new', 'full_name' => 'Bệnh Nhân Gửi Lại', 'date_of_birth' => '2000-01-02', 'gender' => 'Nam',
            'citizen_id_number' => '123456789012', 'phone' => '0909888999', 'confirm_search' => 1, 'schedule_id' => $this->schedule->id,
            'confirm_reception' => 1, 'request_token' => $token];
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('employee.walk-in.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, Patient::where('full_name', 'Bệnh Nhân Gửi Lại')->count());
        $patient = Patient::where('full_name', 'Bệnh Nhân Gửi Lại')->sole();
        $this->assertSame(1, User::where('phone', $payload['phone'])->count());
        $visit = $patient->medicalVisits()->sole();
        $this->assertSame(1, $visit->payables()->count());
    }

    public function test_admin_and_receptionist_need_a_twelve_digit_citizen_id_to_create_a_walk_in_patient(): void
    {
        $name = 'Kiểm Thử CCCD Bắt Buộc';
        $payload = [
            'patient_mode' => 'new', 'full_name' => $name, 'date_of_birth' => '2000-01-02', 'gender' => 'Nam',
            'confirm_search' => 1, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1,
            'request_token' => (string) Str::uuid(), 'phone' => '0909888999',
        ];

        $admin = User::where('business_code', 'QT000001')->firstOrFail();
        foreach (['admin.visits.walk-in.store' => $admin, 'employee.walk-in.store' => $this->receptionist()] as $route => $actor) {
            $this->actingAs($actor)->post(route($route), $payload)->assertSessionHasErrors([
                'citizen_id_number' => 'Vui lòng nhập số CCCD khi tạo hồ sơ mới hoặc liên kết hồ sơ cũ chưa có CCCD.',
            ]);
            $this->post(route($route), [...$payload, 'citizen_id_number' => '01234567890'])->assertSessionHasErrors([
                'citizen_id_number' => 'CCCD phải gồm 12 chữ số.',
            ]);
            $this->post(route($route), [...$payload, 'citizen_id_number' => '123456789012', 'phone' => null])->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseMissing('patients', ['full_name' => $name]);
    }

    public function test_validation_and_invalid_schedule_do_not_create_partial_records(): void
    {
        $base = ['patient_mode' => 'new', 'full_name' => 'Kiểm Thử Không Lưu', 'date_of_birth' => '2000-01-02', 'gender' => 'Nam',
            'citizen_id_number' => '123456789012', 'phone' => '0909888999', 'schedule_id' => $this->schedule->id,
            'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()];
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), $base)->assertSessionHasErrors('confirm_search');
        $this->schedule->update(['status' => 'INACTIVE']);
        $this->post(route('employee.walk-in.store'), [...$base, 'confirm_search' => 1])->assertSessionHasErrors('schedule_id');
        $this->assertFalse(Patient::where('full_name', 'Kiểm Thử Không Lưu')->exists());
        $this->schedule->update(['status' => 'ACTIVE']);
        PaymentStatus::where('name', 'UNPAID')->update(['is_active' => false]);
        $this->post(route('employee.walk-in.store'), [...$base, 'confirm_search' => 1])->assertSessionHasErrors('appointment');
        $this->assertFalse(Patient::where('full_name', 'Kiểm Thử Không Lưu')->exists());
        $this->assertDatabaseMissing('users', ['phone' => $base['phone']]);
    }

    public function test_existing_identity_and_unsearched_patient_cannot_be_substituted(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $patient->detail()->update(['citizen_id_number' => '012345678901']);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.store'), [
            'patient_mode' => 'new', 'full_name' => 'Hồ Sơ Trùng', 'date_of_birth' => '2000-01-02', 'gender' => 'Nam',
            'citizen_id_number' => '012345678901', 'phone' => '0909888999', 'confirm_search' => 1, 'schedule_id' => $this->schedule->id,
            'confirm_reception' => 1, 'request_token' => (string) Str::uuid(),
        ])->assertSessionHasErrors('citizen_id_number');
        $this->assertFalse(Patient::where('full_name', 'Hồ Sơ Trùng')->exists());
        $this->post(route('employee.walk-in.store'), [
            'patient_mode' => 'existing', 'patient_id' => $patient->id, 'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('patient_id');
        $this->assertFalse(MedicalVisit::where('patient_id', $patient->id)->where('reception_source', 'WALK_IN')->exists());
    }

    public function test_search_accepts_login_phone_and_exact_identity_number(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $patient->detail()->update(['citizen_id_number' => '012345678901']);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.search'), ['identifier' => $patient->user->phone])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertSee($patient->patient_code);
        $this->post(route('employee.walk-in.search'), ['identifier' => '012345678901'])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertSee($patient->patient_code);
        $this->post(route('employee.walk-in.search'), ['identifier' => '01234567890'])->assertRedirect();
        $this->get(route('employee.walk-in.create'))->assertDontSee($patient->patient_code)->assertSee('Không tìm thấy hồ sơ');
        $this->get(route('employee.walk-in.create'))->assertSee('Không tìm thấy hồ sơ');
    }

    public function test_unfinished_walk_in_prevents_duplicate_appointment_reception_for_same_doctor(): void
    {
        $patient = Patient::where('patient_code', 'BN000005')->firstOrFail();
        $slot = $this->schedule->appointmentTimeSlots()->create(['start_time' => '09:00', 'end_time' => '09:30', 'max_patients' => 5, 'status' => 'ACTIVE']);
        $appointment = \App\Models\Appointment::create([
            'appointment_code' => 'LH-WALKIN-TEST', 'patient_id' => $patient->id, 'doctor_id' => $this->schedule->doctor_id,
            'specialty_id' => $this->schedule->specialty_id, 'examination_room_id' => $this->schedule->examination_room_id,
            'appointment_date' => today(), 'appointment_time_slot_id' => $slot->id,
            'appointment_status_id' => \App\Models\AppointmentStatus::where('name', 'BOOKED')->value('id'),
            'checkin_token' => (string) \Illuminate\Support\Str::uuid(), 'booked_at' => now(),
        ]);
        $this->actingAs($this->receptionist())->post(route('employee.walk-in.search'), ['identifier' => $patient->patient_code]);
        $this->post(route('employee.walk-in.store'), ['patient_mode' => 'existing', 'patient_id' => $patient->id,
            'schedule_id' => $this->schedule->id, 'confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasNoErrors();
        $this->post(route('employee.appointments.check-in', $appointment), ['confirm_reception' => 1, 'request_token' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasErrors('appointment');
        $this->assertFalse($appointment->medicalVisit()->exists());
    }

    public function test_wrong_roles_cannot_search_or_submit_walk_in(): void
    {
        foreach (['NV000002', 'NV000003', 'NV000004'] as $code) {
            $this->flushSession();
            $this->actingAsEmployeeWorkspace(User::where('business_code', $code)->firstOrFail(), 'reception')->get(route('employee.walk-in.create'))->assertOk();
            $this->post(route('employee.walk-in.search'), ['identifier' => 'BN000005'])->assertRedirect(route('employee.walk-in.create'));
            $this->post(route('employee.walk-in.store'), [])->assertSessionHasErrors('patient_mode');
        }
        foreach (['BN000001', 'QT000001'] as $code) {
            $this->flushSession();
            $this->actingAs(User::where('business_code', $code)->firstOrFail())->get(route('employee.walk-in.create'))->assertForbidden();
            $this->post(route('employee.walk-in.search'), ['identifier' => 'BN000005'])->assertForbidden();
            $this->post(route('employee.walk-in.store'), [])->assertForbidden();
        }
    }
}
