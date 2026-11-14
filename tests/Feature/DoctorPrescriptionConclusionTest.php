<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\MedicalService;
use App\Models\Disease;
use App\Models\Medicine;
use App\Models\Payable;
use App\Models\PaymentStatus;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\DoctorClinicalVersion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorPrescriptionConclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
    }

    private function visit(): MedicalVisit
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit->update(['appointment_id' => null, 'visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        return $visit->fresh();
    }

    private function item(Medicine $medicine): array
    {
        return ['medicine_id' => $medicine->id, 'quantity' => '10', 'usage_unit' => 'viên', 'dose_per_use' => '1 viên',
            'times_per_day' => '2', 'duration_days' => '5', 'usage_method' => 'Uống'];
    }

    private function prescription(MedicalVisit $visit): void
    {
        $medicine = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->firstOrFail();
        $medicine->update(['price_per_unit' => 2000]);
        $this->post(route('doctor.visits.prescriptions.store', $visit), ['items' => [$this->item($medicine)], 'confirm_create' => '1'])
            ->assertSessionHasNoErrors();
    }

    public function test_doctor_creates_prescription_for_own_visit_and_patient_can_see_it(): void
    {
        $visit = $this->visit();
        $this->get(route('doctor.visits.prescriptions.create', $visit))->assertOk()->assertSee('Thêm thuốc');
        $this->prescription($visit);
        $prescription = $visit->fresh()->prescription;
        $this->assertNotNull($prescription);
        $this->assertSame($visit->doctor_id, $prescription->doctor_id);
        $this->assertCount(1, $prescription->items);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'prescriptions', 'entity_id' => $prescription->id, 'role_name_snapshot' => 'Doctor']);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee($prescription->prescription_code);
        $this->post(route('doctor.visits.prescriptions.store', $visit), ['items' => [$this->item($prescription->items->first()->medicine)], 'confirm_create' => '1'])->assertSessionHasErrors('prescription');
        $this->flushSession()->actingAs($visit->patient->user)->get(route('patient.prescriptions'))->assertOk()->assertSee($prescription->prescription_code);
    }

    public function test_prescription_rejects_invalid_input_and_allows_other_doctors_visit(): void
    {
        $visit = $this->visit();
        $foreign = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $medicine = Medicine::where('unit', 'viên')->firstOrFail();
        $this->post(route('doctor.visits.prescriptions.store', $visit), ['items' => [[...$this->item($medicine), 'quantity' => 0]], 'confirm_create' => '1'])->assertSessionHasErrors('items.0.quantity');
        $foreign->update(['appointment_id' => null, 'visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')]);
        $medicine->update(['price_per_unit' => 2000]);
        $this->post(route('doctor.visits.prescriptions.store', $foreign), ['items' => [$this->item($medicine)], 'confirm_create' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(User::where('business_code', 'BS000001')->firstOrFail()->doctor->id, $foreign->fresh()->prescription->doctor_id);
        $this->assertNull($visit->fresh()->prescription);
    }

    public function test_doctor_prints_only_prescription_for_assigned_visit(): void
    {
        $visit = $this->visit();
        $this->get(route('doctor.visits.prescriptions.print', $visit))->assertNotFound();
        $this->prescription($visit);
        $prescription = $visit->fresh()->prescription;
        $prescription->update(['doctor_notes' => 'Ghi chú bác sĩ xem và in']);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Ghi chú bác sĩ xem và in');
        $response = $this->get(route('doctor.visits.prescriptions.print', $visit))->assertOk()
            ->assertSee('ĐƠN THUỐC')->assertSee($prescription->prescription_code)
            ->assertSee($prescription->items->first()->medicine->name)
            ->assertSee('Ghi chú bác sĩ xem và in')
            ->assertSee('href="'.route('doctor.visits.show', $visit).'"', false)
            ->assertDontSee(route('patient.prescriptions.document', $prescription));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $other = MedicalVisit::where('visit_code', 'LK000002')->firstOrFail();
        $this->get(route('doctor.visits.prescriptions.print', $other))->assertOk();
        $this->flushSession()->actingAs(User::where('business_code', 'BN000001')->firstOrFail())
            ->get(route('doctor.visits.prescriptions.print', $visit))->assertForbidden();
    }

    public function test_prescription_accepts_multiple_distinct_medicines_and_rejects_duplicate_medicine(): void
    {
        $visit = $this->visit();
        $medicines = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->limit(2)->get();
        foreach ($medicines as $medicine) $medicine->update(['price_per_unit' => 2000]);
        $items = [$this->item($medicines[0]), $this->item($medicines[1])];
        $url = route('doctor.visits.prescriptions.store', $visit);
        $this->post($url, ['items' => [$items[0], $items[0]], 'confirm_create' => '1'])->assertSessionHasErrors('items.0.medicine_id');
        $this->assertNull($visit->fresh()->prescription);
        $this->post($url, ['items' => $items, 'confirm_create' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(2, $visit->fresh()->prescription->items()->count());
    }

    public function test_prescription_cannot_be_changed_or_cancelled_by_direct_request(): void
    {
        $visit = $this->visit();
        $this->prescription($visit);
        $prescription = $visit->fresh()->prescription;
        $url = route('doctor.visits.prescriptions.store', $visit);
        $before = $prescription->getAttributes();
        $payable = Payable::create(['payable_code' => 'PTTESTRX', 'medical_visit_id' => $visit->id, 'prescription_id' => $prescription->id,
            'payment_type' => 'MEDICINE_FEE', 'description' => 'Kiểm thử', 'amount' => 20000,
            'payment_status_id' => PaymentStatus::where('name', 'UNPAID')->value('id'), 'generated_at' => now()]);
        $this->get(route('doctor.visits.show', $visit))->assertOk()
            ->assertDontSee('Chỉnh sửa lời dặn')->assertDontSee('Hủy đơn thuốc')->assertSee('Xem và in đơn thuốc');
        foreach (['save', 'cancel'] as $action) {
            $this->put($url, ['record_version' => DoctorClinicalVersion::of($prescription), 'action' => $action,
                'general_instructions' => 'Không được sửa', 'doctor_notes' => 'Không được sửa'])->assertStatus(405);
        }
        $this->assertSame($before, $prescription->fresh()->getAttributes());
        $this->assertSame('UNPAID', $payable->fresh()->paymentStatus->name);
        $prescription->update(['status' => 'CANCELLED']);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Đã hủy')
            ->assertDontSee('Chỉnh sửa lời dặn')->assertDontSee('Hủy đơn thuốc');
    }

    public function test_creating_prescription_locks_clinical_edits_and_orders_but_not_conclusion(): void
    {
        $visit = $this->visit();
        $disease = Disease::where('status', 'ACTIVE')->whereNotIn('id', $visit->diagnoses()->pluck('disease_id'))->firstOrFail();
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        $this->put(route('doctor.visits.clinical.update', $visit), [
            'record_version' => DoctorClinicalVersion::of($visit->clinicalNote), 'reason_for_visit' => 'Nội dung trước kê đơn',
        ])->assertSessionHasNoErrors();
        $this->post(route('doctor.visits.diagnoses.store', $visit), [
            'disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY', 'diagnosis_description' => 'Chẩn đoán trước kê đơn',
        ])->assertSessionHasNoErrors();
        $this->prescription($visit);
        $diagnosis = $visit->diagnoses()->where('disease_id', $disease->id)->firstOrFail();
        $beforeDiagnoses = $visit->diagnoses()->count();
        $note = $visit->fresh()->clinicalNote;
        $beforeOrders = $visit->serviceOrders()->count();

        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Nội dung trước kê đơn')
            ->assertSee('Chẩn đoán trước kê đơn')->assertSee('Đã tạo đơn thuốc')
            ->assertDontSee('Lưu nội dung khám')->assertDontSee('Chỉnh sửa chẩn đoán')
            ->assertDontSee('+ Thêm chẩn đoán')->assertDontSee('>Chỉ định dịch vụ</a>', false)
            ->assertSee('Nhập kết luận khám');
        $this->put(route('doctor.visits.clinical.update', $visit), [
            'record_version' => DoctorClinicalVersion::of($note), 'reason_for_visit' => 'Không được lưu',
        ])->assertSessionHasErrors('prescription_lock');
        $this->post(route('doctor.visits.diagnoses.store', $visit), [
            'disease_id' => $disease->id, 'diagnosis_type' => 'SECONDARY',
        ])->assertSessionHasErrors('prescription_lock');
        $this->put(route('doctor.visits.diagnoses.update', [$visit, $diagnosis]), [
            'record_version' => DoctorClinicalVersion::of($diagnosis), 'disease_id' => $disease->id,
            'diagnosis_type' => 'PRIMARY', 'diagnosis_description' => 'Không được lưu',
        ])->assertSessionHasErrors('prescription_lock');
        $this->get(route('doctor.visits.orders.create', $visit))->assertRedirect(route('doctor.visits.show', $visit));
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id]])
            ->assertSessionHasErrors('prescription_lock');
        $this->assertSame('Nội dung trước kê đơn', $note->fresh()->reason_for_visit);
        $this->assertSame('Chẩn đoán trước kê đơn', $diagnosis->fresh()->diagnosis_description);
        $this->assertSame($beforeDiagnoses, $visit->diagnoses()->count());
        $this->assertSame($beforeOrders, $visit->serviceOrders()->count());
        $this->put(route('doctor.visits.conclusion.save', $visit), [
            'record_version' => DoctorClinicalVersion::of($visit->fresh()->conclusion), 'professional_conclusion' => 'Kết luận sau kê đơn', 'has_follow_up' => '0',
        ])->assertSessionHasNoErrors();

        // Đơn cũ bị hủy không tự mở khóa các phần đã chốt.
        $visit->fresh()->prescription->update(['status' => 'CANCELLED']);
        $this->put(route('doctor.visits.clinical.update', $visit), [
            'record_version' => DoctorClinicalVersion::of($note->fresh()), 'reason_for_visit' => 'Không được lưu',
        ])->assertSessionHasErrors('conclusion_lock');
    }

    public function test_saved_conclusion_locks_earlier_steps_even_without_prescription(): void
    {
        $visit = $this->visit();
        $disease = Disease::where('status', 'ACTIVE')->whereNotIn('id', $visit->diagnoses()->pluck('disease_id'))->firstOrFail();
        $medicine = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->firstOrFail();
        $medicine->update(['price_per_unit' => 2000]);
        $service = MedicalService::where('service_code', 'DVXN007')->firstOrFail();
        $this->post(route('doctor.visits.diagnoses.store', $visit), ['disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY'])
            ->assertSessionHasNoErrors();
        $diagnosis = $visit->diagnoses()->where('disease_id', $disease->id)->firstOrFail();
        $note = $visit->fresh()->clinicalNote;
        $orderCount = $visit->serviceOrders()->count();
        $diagnosisCount = $visit->diagnoses()->count();
        $this->put(route('doctor.visits.conclusion.save', $visit), [
            'record_version' => DoctorClinicalVersion::of(null), 'professional_conclusion' => 'Kết luận chốt', 'has_follow_up' => '0',
        ])->assertSessionHasNoErrors();

        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Kết luận chốt')
            ->assertSee('Đã lưu kết luận khám')->assertDontSee('Lưu nội dung khám')
            ->assertDontSee('Chỉnh sửa chẩn đoán')->assertDontSee('+ Thêm chẩn đoán')
            ->assertDontSee('>Chỉ định dịch vụ</a>', false)->assertDontSee('>Kê đơn thuốc</a>', false)
            ->assertDontSee('Chỉnh sửa kết luận khám')->assertDontSee('Lưu kết luận');
        $this->put(route('doctor.visits.clinical.update', $visit), [
            'record_version' => DoctorClinicalVersion::of($note), 'reason_for_visit' => 'Không lưu',
        ])->assertSessionHasErrors('conclusion_lock');
        $this->post(route('doctor.visits.diagnoses.store', $visit), [
            'disease_id' => $disease->id, 'diagnosis_type' => 'SECONDARY',
        ])->assertSessionHasErrors('conclusion_lock');
        $this->put(route('doctor.visits.diagnoses.update', [$visit, $diagnosis]), [
            'record_version' => DoctorClinicalVersion::of($diagnosis), 'disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY',
        ])->assertSessionHasErrors('conclusion_lock');
        $this->get(route('doctor.visits.orders.create', $visit))->assertRedirect(route('doctor.visits.show', $visit));
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasErrors('conclusion_lock');
        $this->get(route('doctor.visits.prescriptions.create', $visit))->assertRedirect(route('doctor.visits.show', $visit));
        $this->post(route('doctor.visits.prescriptions.store', $visit), ['items' => [$this->item($medicine)], 'confirm_create' => '1'])
            ->assertSessionHasErrors('conclusion_lock');
        $this->assertSame($diagnosisCount, $visit->diagnoses()->count());
        $this->assertSame($orderCount, $visit->serviceOrders()->count());
        $this->assertNull($visit->fresh()->prescription);
        $this->assertSame($note?->reason_for_visit, $visit->fresh()->clinicalNote?->reason_for_visit);
    }

    public function test_confirmation_is_required_and_input_is_retained_after_validation(): void
    {
        $visit = $this->visit();
        $medicine = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->firstOrFail();
        $medicine->update(['price_per_unit' => 2000]);
        $create = route('doctor.visits.prescriptions.create', $visit);
        $store = route('doctor.visits.prescriptions.store', $visit);
        $payload = ['items' => [$this->item($medicine)], 'general_instructions' => 'Lời dặn cần giữ', 'doctor_notes' => 'Ghi chú cần giữ'];

        // Xác nhận không hợp lệ không được tạo đơn, kể cả khi gọi trực tiếp.
        foreach ([null, '0', 'khong'] as $confirmation) {
            $this->flushSession()->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
            $this->from($create)->post($store, [...$payload, 'confirm_create' => $confirmation])->assertSessionHasErrors('confirm_create');
            $this->assertNull($visit->fresh()->prescription);
        }
        $this->flushSession()->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
        $this->from($create)->post($store, $payload)->assertSessionHasErrors('confirm_create');
        $this->get($create)->assertOk()
            ->assertSee('Lời dặn cần giữ')->assertSee('Ghi chú cần giữ')
            ->assertSee('Tôi đã kiểm tra thông tin thuốc và xác nhận tạo đơn.');

        $this->flushSession()->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
        $this->from($create)->post($store, [...$payload, 'items' => [[...$payload['items'][0], 'quantity' => '0']], 'confirm_create' => '1'])
            ->assertSessionHasErrors('items.0.quantity');
        $form = $this->get($create)->assertOk()->assertSee('Lời dặn cần giữ')->assertSee('Ghi chú cần giữ');
        $this->assertMatchesRegularExpression('/name="confirm_create" value="1"\s+checked/', $form->getContent());
        $this->assertMatchesRegularExpression('/name="items\[0\]\[medicine_id\]"[\s\S]*?value="'.$medicine->id.'"[^>]*selected/', $form->getContent());
        $this->assertNull($visit->fresh()->prescription);
    }

    public function test_confirmation_error_appears_next_to_checkbox(): void
    {
        $visit = $this->visit();
        $errors = (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag([
            'confirm_create' => 'Vui lòng xác nhận thông tin trước khi lưu đơn thuốc.',
        ]));
        $this->view('doctor.visits.prescription-create', [
            'doctor' => $visit->doctor, 'record' => $visit, 'medicines' => Medicine::where('status', 'ACTIVE')->get(), 'errors' => $errors,
        ])->assertSee('id="prescription-confirm-error"', false)
            ->assertSee('Vui lòng xác nhận thông tin trước khi lưu đơn thuốc.');
    }

    public function test_conclusion_requires_valid_content_and_rejects_stale_or_unauthorized_writes(): void
    {
        $visit = $this->visit();
        $url = route('doctor.visits.conclusion.save', $visit);
        $emptyVersion = DoctorClinicalVersion::of(null);
        $this->put($url, ['record_version' => $emptyVersion, 'has_follow_up' => '0'])->assertSessionHasErrors('professional_conclusion');
        $payload = ['record_version' => $emptyVersion, 'professional_conclusion' => 'Kết luận kiểm thử', 'has_follow_up' => '0'];
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $conclusion = $visit->fresh()->conclusion;
        $this->assertSame('Kết luận kiểm thử', $conclusion->professional_conclusion);
        $this->assertSame($visit->doctor_id, $conclusion->doctor_id);
        $this->put($url, $payload)->assertSessionHasErrors('conclusion_lock');
        $this->assertSame('Kết luận kiểm thử', $conclusion->fresh()->professional_conclusion);
        // Kể cả phiên bản mới nhất cũng không cho phép ghi đè kết luận đã lưu.
        $this->put($url, [...$payload, 'record_version' => DoctorClinicalVersion::of($conclusion->fresh()), 'professional_conclusion' => 'Nội dung bị từ chối'])
            ->assertSessionHasErrors('conclusion_lock');
        $this->assertSame('Kết luận kiểm thử', $conclusion->fresh()->professional_conclusion);
        $foreign = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $this->put(route('doctor.visits.conclusion.save', $foreign), $payload)->assertSessionHasNoErrors();
        $this->assertSame(User::where('business_code', 'BS000001')->firstOrFail()->doctor->id, $foreign->fresh()->conclusion->doctor_id);
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')]);
        $this->put($url, [...$payload, 'record_version' => DoctorClinicalVersion::of($conclusion->fresh())])->assertSessionHasErrors('visit');
    }

    public function test_conclusion_requires_nonempty_clinical_note_and_diagnosis(): void
    {
        $visit = $this->visit();
        $visit->diagnoses()->delete();
        $visit->clinicalNote()->delete();
        $url = route('doctor.visits.conclusion.save', $visit);
        $payload = ['record_version' => DoctorClinicalVersion::of(null), 'professional_conclusion' => 'Kết luận kiểm thử', 'has_follow_up' => '0'];

        $this->get(route('doctor.visits.show', $visit))->assertOk()
            ->assertSee('Vui lòng lưu ít nhất một nội dung khám trước khi lưu kết luận.')
            ->assertSee('Vui lòng thêm ít nhất một chẩn đoán trước khi lưu kết luận.');
        $this->put($url, $payload)->assertSessionHasErrors(['clinical_note', 'diagnosis']);
        $this->assertNull($visit->fresh()->conclusion);

        // Bản ghi rỗng hoặc chỉ gồm khoảng trắng chưa phải nội dung khám.
        $visit->clinicalNote()->create(['reason_for_visit' => '   ', 'recorded_at' => now()]);
        $this->put($url, $payload)->assertSessionHasErrors(['clinical_note', 'diagnosis']);
        $visit->clinicalNote->update(['reason_for_visit' => 'Đau đầu']);
        $this->put($url, $payload)->assertSessionHasErrors('diagnosis');
        $this->assertNull($visit->fresh()->conclusion);

        $disease = Disease::where('status', 'ACTIVE')->firstOrFail();
        $this->post(route('doctor.visits.diagnoses.store', $visit), ['disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY'])
            ->assertSessionHasNoErrors();
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertSame('Kết luận kiểm thử', $visit->fresh()->conclusion->professional_conclusion);
    }

    public function test_follow_up_requires_date_and_reason_and_waiting_service_blocks_changes(): void
    {
        $visit = $this->visit();
        $url = route('doctor.visits.conclusion.save', $visit);
        $payload = ['record_version' => DoctorClinicalVersion::of(null), 'professional_conclusion' => 'Kết luận', 'has_follow_up' => '1'];
        $this->put($url, $payload)->assertSessionHasErrors(['follow_up_date', 'follow_up_reason']);
        $this->assertNull($visit->fresh()->conclusion);
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'WAITING_FOR_SERVICE')->value('id')]);
        $this->put($url, [...$payload, 'has_follow_up' => '0'])->assertSessionHasErrors('visit');
        $this->assertNull($visit->fresh()->conclusion);
    }

    public function test_conclusion_saves_all_fields_and_follow_up_cannot_be_changed(): void
    {
        $visit = $this->visit();
        $url = route('doctor.visits.conclusion.save', $visit);
        $date = now()->addDays(10)->toDateString();
        $payload = [
            'record_version' => DoctorClinicalVersion::of(null), 'professional_conclusion' => 'Kết luận kiểm thử đầy đủ',
            'disposition' => 'Theo dõi ngoại trú', 'treatment_plan' => 'Kế hoạch kiểm thử',
            'instructions' => 'Dặn dò kiểm thử', 'has_follow_up' => '1', 'follow_up_date' => $date,
            'follow_up_reason' => 'Theo dõi sau khám', 'notes' => 'Ghi chú kiểm thử',
        ];
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $conclusion = $visit->fresh()->conclusion;
        $this->assertSame('Theo dõi ngoại trú', $conclusion->disposition);
        $this->assertSame($date, $conclusion->follow_up_date->toDateString());
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Kết luận kiểm thử đầy đủ')
            ->assertSee('Kế hoạch kiểm thử')->assertSee('Dặn dò kiểm thử')->assertSee('Theo dõi sau khám')
            ->assertSee('Ghi chú kiểm thử')->assertSee('Hẹn tái khám')->assertDontSee('Chỉnh sửa kết luận khám');

        // Sau khi lưu, không được bỏ hẹn hoặc ghi đè các trường kết luận.
        $this->put($url, [...$payload, 'record_version' => DoctorClinicalVersion::of($conclusion), 'has_follow_up' => '0'])
            ->assertSessionHasErrors('conclusion_lock');
        $this->assertSame($date, $conclusion->fresh()->follow_up_date->toDateString());
        $this->assertSame('Theo dõi sau khám', $conclusion->fresh()->follow_up_reason);
    }

    public function test_conclusion_form_restores_follow_up_and_limits_suggestions_to_assigned_doctor(): void
    {
        $visit = $this->visit();
        $other = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
        $other->conclusion()->create(['doctor_id' => $other->doctor_id, 'professional_conclusion' => 'Khác',
            'disposition' => 'Gợi ý riêng bác sĩ khác', 'has_follow_up' => false, 'concluded_at' => now()]);
        $this->withSession(['_old_input' => [
            'form_key' => 'conclusion', 'record_version' => DoctorClinicalVersion::of(null),
            'professional_conclusion' => 'Nội dung đang nhập', 'has_follow_up' => '1',
            'follow_up_date' => now()->addDays(2)->toDateString(), 'follow_up_reason' => 'Lý do đang nhập',
        ]])->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Nội dung đang nhập')
            ->assertSee('Lý do đang nhập')->assertSee('data-follow-up-fields', false)
            ->assertDontSee('Gợi ý riêng bác sĩ khác');
    }
}
