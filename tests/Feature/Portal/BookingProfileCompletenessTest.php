<?php

namespace Tests\Feature\Portal;

use App\Models\DoctorSchedule;
use App\Models\User;
use App\Support\Portal\BookingProfileGate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingProfileCompletenessTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private int $slotId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 27)->setTime(8, 0));
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->patient = User::where('business_code', 'BN000001')->firstOrFail();
        $this->patient->patient->detail->update([
            'current_address' => '12 Pho Bach Mai, Ha Noi',
            'emergency_contact_name' => 'Tran Van A',
            'emergency_contact_phone' => '0901234567',
            'email' => null,
        ]);
        $base = DoctorSchedule::firstOrFail();
        $schedule = DoctorSchedule::create([
            'doctor_id' => $base->doctor_id, 'specialty_id' => $base->specialty_id,
            'examination_room_id' => $base->examination_room_id,
            'work_date' => today()->addDay(), 'start_time' => '09:00:00',
            'end_time' => '11:00:00', 'status' => 'ACTIVE',
        ]);
        $this->slotId = $schedule->appointmentTimeSlots()->create([
            'start_time' => '09:00:00', 'end_time' => '09:30:00',
            'max_patients' => 2, 'status' => 'ACTIVE',
        ])->id;
        $this->actingAs($this->patient);
    }

    public function test_missing_emergency_fields_are_named_individually_and_together(): void
    {
        $detail = $this->patient->patient->detail;
        foreach ([
            [null, null, ['họ tên liên hệ khẩn cấp', 'số điện thoại liên hệ khẩn cấp']],
            [null, '0901234567', ['họ tên liên hệ khẩn cấp']],
            ['Tran Van A', null, ['số điện thoại liên hệ khẩn cấp']],
        ] as [$name, $phone, $expected]) {
            $detail->update(['emergency_contact_name' => $name, 'emergency_contact_phone' => $phone]);
            $missing = app(BookingProfileGate::class)->missingFields($this->patient->patient->fresh());
            $this->assertSame($expected, $missing);
            $response = $this->get(route('patient.appointments.create', $this->slotId));
            $response->assertOk()->assertSee('Hồ sơ bệnh nhân chưa đầy đủ')->assertSee('Bổ sung thông tin')
                ->assertSee(route('patient.profile.edit', ['booking_slot' => $this->slotId]));
            foreach ($expected as $field) $response->assertSeeText($field);
            $this->post(route('patient.appointments.store'), $this->bookingData())
                ->assertRedirect(route('patient.appointments.create', $this->slotId))->assertSessionHasErrors('profile');
            $this->assertDatabaseMissing('appointments', ['patient_id' => $this->patient->patient->id, 'appointment_time_slot_id' => $this->slotId]);
        }
    }

    public function test_missing_birth_gender_and_address_are_reported(): void
    {
        $patient = $this->patient->patient;
        foreach ([
            ['date_of_birth', 'ngày sinh'],
            ['gender', 'giới tính'],
        ] as [$field, $label]) {
            $original = DB::table('patients')->where('id', $patient->id)->value($field);
            DB::table('patients')->where('id', $patient->id)->update([$field => null]);
            $this->assertContains($label, app(BookingProfileGate::class)->missingFields($patient->fresh()));
            $this->post(route('patient.appointments.store'), $this->bookingData())
                ->assertRedirect(route('patient.appointments.create', $this->slotId))->assertSessionHasErrors('profile');
            DB::table('patients')->where('id', $patient->id)->update([$field => $original]);
        }
        $patient->detail->update(['current_address' => null, 'permanent_address' => null]);
        $this->assertSame(['địa chỉ liên hệ'], app(BookingProfileGate::class)->missingFields($patient->fresh()));
        $this->post(route('patient.appointments.store'), $this->bookingData())
            ->assertRedirect(route('patient.appointments.create', $this->slotId))->assertSessionHasErrors('profile');
        $patient->update(['date_of_birth' => null, 'gender' => null]);
        $this->get(route('patient.appointments.create', $this->slotId))->assertOk()
            ->assertSeeText('ngày sinh')->assertSeeText('giới tính')->assertSeeText('địa chỉ liên hệ')
            ->assertSee('Bổ sung thông tin');
    }

    public function test_complete_profile_with_no_email_can_book(): void
    {
        $this->get(route('patient.appointments.create', $this->slotId))->assertOk()
            ->assertSeeText('Tran Van A')->assertSeeText('090 ••• •567')
            ->assertDontSee('Bổ sung thông tin')->assertDontSee('Hồ sơ bệnh nhân chưa đầy đủ');
        $this->post(route('patient.appointments.store'), $this->bookingData())
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['patient_id' => $this->patient->patient->id, 'appointment_time_slot_id' => $this->slotId]);
    }

    public function test_confirmation_masks_only_own_citizen_id_and_requires_a_valid_one(): void
    {
        $detail = $this->patient->patient->detail;
        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $ownId = $detail->citizen_id_number;
        $otherId = $other->patient->detail->citizen_id_number;
        $this->assertNotEmpty($ownId);
        $response = $this->get(route('patient.appointments.create', $this->slotId));
        $response->assertOk()->assertSeeInOrder(['Địa chỉ liên hệ', 'Số CCCD', '••••••••'.substr($ownId, -4), 'Họ tên liên hệ khẩn cấp'])
            ->assertDontSee($ownId)->assertDontSee($otherId);

        $detail->update(['citizen_id_number' => null]);
        $this->get(route('patient.appointments.create', $this->slotId))->assertOk()
            ->assertSeeInOrder(['Địa chỉ liên hệ', 'Số CCCD', 'Chưa cập nhật', 'Họ tên liên hệ khẩn cấp'])
            ->assertSeeText('số CCCD')->assertSee('Bổ sung thông tin');
        $this->post(route('patient.appointments.store'), $this->bookingData())
            ->assertRedirect(route('patient.appointments.create', $this->slotId))->assertSessionHasErrors('profile');
        $this->assertDatabaseMissing('appointments', ['patient_id' => $this->patient->patient->id, 'appointment_time_slot_id' => $this->slotId]);
        $this->put(route('patient.profile.update'), ['booking_slot' => $this->slotId, 'citizen_id_number' => '123'])
            ->assertSessionHasErrors('citizen_id_number');
        $this->followingRedirects()->from(route('patient.profile.edit', ['booking_slot' => $this->slotId]))
            ->put(route('patient.profile.update'), ['booking_slot' => $this->slotId, 'citizen_id_number' => $otherId])
            ->assertOk()->assertDontSee($otherId);
        $this->followRedirects = false;
        $this->put(route('patient.profile.update'), ['booking_slot' => $this->slotId, 'citizen_id_number' => $ownId])
            ->assertRedirect(route('patient.appointments.create', $this->slotId));
        $this->get(route('patient.appointments.create', $this->slotId))->assertOk()->assertDontSee('Bổ sung thông tin')->assertDontSee($ownId);
        $this->put(route('patient.profile.update'), ['citizen_id_number' => '999999999999'])->assertSessionHasErrors('citizen_id_number');
        $this->post(route('patient.appointments.store'), $this->bookingData())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['patient_id' => $this->patient->patient->id, 'appointment_time_slot_id' => $this->slotId]);
    }

    public function test_malformed_legacy_citizen_id_cannot_be_used_to_book(): void
    {
        $this->patient->patient->detail->update(['citizen_id_number' => '123']);
        $this->postJson(route('patient.appointments.store'), $this->bookingData())
            ->assertUnprocessable()->assertJsonPath('errors.profile.0', 'số CCCD');
        $this->assertDatabaseMissing('appointments', ['patient_id' => $this->patient->patient->id, 'appointment_time_slot_id' => $this->slotId]);
    }

    public function test_missing_profile_blocks_booking_then_profile_save_returns_to_slot(): void
    {
        $this->patient->patient->detail->update(['emergency_contact_name' => null, 'emergency_contact_phone' => null]);
        $this->post(route('patient.appointments.store'), $this->bookingData())
            ->assertRedirect(route('patient.appointments.create', $this->slotId))->assertSessionHasErrors('profile');
        $this->assertDatabaseMissing('appointments', ['patient_id' => $this->patient->patient->id, 'appointment_time_slot_id' => $this->slotId]);

        $other = User::where('business_code', 'BN000002')->firstOrFail();
        $otherName = $other->patient->detail->emergency_contact_name;
        $this->get(route('patient.profile.edit', ['booking_slot' => $this->slotId, 'patient_id' => $other->patient->id]))
            ->assertOk()->assertDontSee($otherName);
        $this->put(route('patient.profile.update'), [
            'booking_slot' => $this->slotId, 'patient_id' => $other->patient->id,
            'emergency_contact_name' => 'Nguoi lien he moi', 'emergency_contact_phone' => '0912345678',
        ])->assertRedirect(route('patient.appointments.create', $this->slotId));
        $this->assertSame($otherName, $other->patient->fresh()->detail->emergency_contact_name);
        $this->assertDatabaseHas('patient_details', ['patient_id' => $this->patient->patient->id, 'emergency_contact_name' => 'Nguoi lien he moi', 'emergency_contact_phone' => '0912345678']);
        $this->get(route('patient.appointments.create', $this->slotId))->assertOk()
            ->assertSeeText('Nguoi lien he moi')->assertDontSee('Bổ sung thông tin')->assertDontSee('Hồ sơ bệnh nhân chưa đầy đủ');
        $this->post(route('patient.appointments.store'), $this->bookingData())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments', ['patient_id' => $this->patient->patient->id, 'appointment_time_slot_id' => $this->slotId]);
    }

    public function test_missing_profile_stays_on_confirmation_and_json_returns_structured_errors(): void
    {
        $this->patient->patient->detail->update(['emergency_contact_name' => null, 'emergency_contact_phone' => null]);
        $before = DB::table('appointments')->where('appointment_time_slot_id', $this->slotId)->count();
        $this->post(route('patient.appointments.store'), $this->bookingData())
            ->assertRedirect(route('patient.appointments.create', $this->slotId))
            ->assertSessionHasErrors('profile')->assertSessionHasInput('reason_for_visit', 'Kiem tra suc khoe');
        $this->followingRedirects()->post(route('patient.appointments.store'), $this->bookingData())->assertOk()
            ->assertSeeText('Chưa thể xác nhận lịch khám')->assertSee('data-profile-error', false)
            ->assertSeeText('họ tên liên hệ khẩn cấp')->assertSeeText('số điện thoại liên hệ khẩn cấp')
            ->assertSee('Kiem tra suc khoe');
        $this->postJson(route('patient.appointments.store'), $this->bookingData())
            ->assertUnprocessable()->assertJsonPath('errors.profile.0', 'họ tên liên hệ khẩn cấp')
            ->assertJsonPath('errors.profile.1', 'số điện thoại liên hệ khẩn cấp');
        $this->assertSame($before, DB::table('appointments')->where('appointment_time_slot_id', $this->slotId)->count());
    }

    private function bookingData(): array
    {
        return ['appointment_time_slot_id' => $this->slotId, 'reason_for_visit' => 'Kiem tra suc khoe', 'confirmed_information' => '1'];
    }
}
