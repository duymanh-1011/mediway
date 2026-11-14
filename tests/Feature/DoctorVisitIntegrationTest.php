<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\MedicalService;
use App\Models\MedicalVisit;
use App\Models\Medicine;
use App\Models\ServiceResult;
use App\Models\User;
use App\Models\VisitStatus;
use App\Services\DoctorClinicalVersion;
use App\Services\DoctorVisitReadiness;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
    }

    public function test_one_visit_connects_doctor_admin_cashier_technician_and_patient(): void
    {
        // Tạo lượt kiểm thử độc lập từ hồ sơ mẫu trong SQLite, không dùng dữ liệu chính.
        $source = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();
        $visit = $source->replicate();
        $visit->visit_code = 'LKINTEGRATION01';
        $visit->appointment_id = null;
        $visit->visit_status_id = VisitStatus::where('name', 'WAITING')->value('id');
        $visit->checked_in_at = now()->subMinutes(15);
        $visit->examination_started_at = null;
        $visit->examination_ended_at = null;
        $visit->reception_queue_number = null;
        $visit->save();

        $doctor = User::where('business_code', 'BS000001')->firstOrFail();
        $patient = $visit->patient->user;
        $otherPatient = User::where('business_code', 'BN000001')->firstOrFail();
        // Admin bắt đầu khám trên lượt được phân công; bác sĩ phải thấy trạng thái mới.
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail())
            ->patch(route('admin.visits.update-status', $visit), ['visit_status_id' => VisitStatus::where('name', 'IN_EXAMINATION')->value('id')])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('IN_EXAMINATION', $visit->fresh()->status->name);
        $this->flushSession()->actingAs($doctor);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee($visit->visit_code);
        $this->put(route('doctor.visits.clinical.update', $visit), [
            'record_version' => DoctorClinicalVersion::of(null), 'reason_for_visit' => 'Lý do liên kết kiểm thử',
            'notes' => 'Thông tin nội bộ kiểm thử',
        ])->assertSessionHasNoErrors();
        $disease = Disease::where('status', 'ACTIVE')->firstOrFail();
        $this->post(route('doctor.visits.diagnoses.store', $visit), [
            'disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY', 'diagnosis_description' => 'Chẩn đoán liên kết kiểm thử',
        ])->assertSessionHasNoErrors();
        // Bác sĩ chỉ định, thu ngân thanh toán và kỹ thuật viên xử lý chính chỉ định đó.
        $this->flushSession()->actingAs($doctor);
        $service = MedicalService::where('service_code', 'DVXN001')->firstOrFail();
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id], 'reason' => 'Chỉ định liên kết kiểm thử'])->assertSessionHasNoErrors();
        $order = $visit->serviceOrders()->where('reason', 'Chỉ định liên kết kiểm thử')->sole();
        $item = $order->items()->sole();
        // Danh sách và chi tiết bác sĩ dùng cùng nhãn tiến độ như Admin.
        $this->get(route('doctor.visits.show', $visit))->assertOk()
            ->assertSee('data-visit-status="WAITING_FOR_SERVICE"', false)->assertSee('Chờ thực hiện');
        $this->assertSame('UNPAID', $item->payable->paymentStatus->name);
        $this->assertFalse(app(DoctorVisitReadiness::class)->check($visit->fresh())['ready']);
        $this->post(route('doctor.visits.orders.store', $visit), ['medical_service_ids' => [$service->id]])->assertSessionHasErrors('medical_service_ids');
        $this->assertSame(1, $visit->serviceOrders()->where('reason', 'Chỉ định liên kết kiểm thử')->count());
        $this->flushSession()->actingAs(User::where('business_code', 'NV000004')->firstOrFail());
        $this->post(route('employee.payments.collect.store', $item->payable), ['payment_method' => 'CASH', 'confirm_payment' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('PAID', $item->payable->fresh()->paymentStatus->name);
        $this->flushSession()->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->get(route('employee.services.show', $item))->assertOk()->assertSee($service->name);
        $this->post(route('employee.services.start', $item))->assertSessionHasNoErrors();
        $this->flushSession()->actingAs($doctor)->get(route('doctor.visits.show', $visit))
            ->assertOk()->assertSee('Đang thực hiện');
        $this->flushSession()->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->post(route('employee.services.save', $item), [
            'result_version' => ServiceResult::editVersion(null), 'confirm_result' => '1', 'result_summary' => 'Kết quả liên kết kiểm thử',
            'parameters_present' => '1', 'parameters' => [['name' => 'Chỉ số liên kết', 'value' => '5', 'unit' => 'mg/L']],
            'details_present' => '1', 'details' => [['name' => 'Kỹ thuật kiểm thử', 'value' => 'Thông tin kỹ thuật']],
        ])->assertSessionHasNoErrors();
        $result = $item->fresh()->result;
        $this->assertNotNull($result);
        $this->flushSession()->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $this->get(route('admin.service-results.show', $result))->assertOk()
            ->assertSee('Kết quả liên kết kiểm thử')->assertSee('Chỉ số liên kết')->assertSee('Thông tin kỹ thuật');
        $this->flushSession()->actingAs($doctor);
        $this->get(route('doctor.visits.show', $visit))->assertOk()->assertSee('Kết quả liên kết kiểm thử')->assertSee('Chỉ số liên kết')->assertSee('Thông tin kỹ thuật');
        $this->flushSession()->actingAs($patient);
        $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'dich-vu']))->assertOk()
            ->assertSee('Kết quả liên kết kiểm thử')->assertSee('Chỉ số liên kết')->assertSee('Thông tin kỹ thuật');
        $this->get(route('patient.results.service-print', $result))->assertOk()->assertSee('Kết quả liên kết kiểm thử');
        $this->flushSession()->actingAs($otherPatient)->get(route('patient.results.service-print', $result))->assertNotFound();
        $this->flushSession()->actingAs(User::where('business_code', 'BS000002')->firstOrFail())->get(route('doctor.visits.show', $visit))->assertOk();
        $this->flushSession()->actingAs(User::where('business_code', 'NV000001')->firstOrFail())->get(route('employee.services.show', $item))->assertForbidden();

        $this->flushSession()->actingAs(User::where('business_code', 'NV000002')->firstOrFail());
        $this->post(route('employee.services.complete', $item))->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
        $this->assertFalse(app(DoctorVisitReadiness::class)->check($visit->fresh())['ready']);
        $this->assertNotSame('COMPLETED', $visit->fresh()->status->name);

        // Giả lập lượt đã được gọi quay lại bác sĩ sau khi hoàn tất dịch vụ.
        $visit->update(['visit_status_id' => VisitStatus::where('name', 'RETURNING')->value('id')]);
        // Hoàn tất dịch vụ trước khi kê đơn; kết luận vẫn được ghi sau kê đơn.
        $this->flushSession()->actingAs($doctor);
        $medicine = Medicine::where('unit', 'viên')->where('status', 'ACTIVE')->firstOrFail();
        $medicine->update(['price_per_unit' => 2000]);
        $this->post(route('doctor.visits.prescriptions.store', $visit), [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => '10', 'usage_unit' => 'viên', 'dose_per_use' => '1 viên',
                'times_per_day' => '2', 'duration_days' => '5', 'usage_method' => 'Uống', 'usage_instructions' => 'Hướng dẫn riêng kiểm thử']],
            'general_instructions' => 'Lời dặn liên kết kiểm thử', 'doctor_notes' => 'Ghi chú đơn cho bệnh nhân', 'confirm_create' => '1',
        ])->assertSessionHasNoErrors();
        $this->put(route('doctor.visits.conclusion.save', $visit), [
            'record_version' => DoctorClinicalVersion::of(null), 'professional_conclusion' => 'Kết luận liên kết kiểm thử', 'has_follow_up' => '0',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(app(DoctorVisitReadiness::class)->check($visit->fresh())['ready']);

        // Cùng một lượt khám và cùng các bản ghi phải xuất hiện tại Admin và bệnh nhân.
        $this->flushSession()->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertSee('Lý do liên kết kiểm thử')
            ->assertSee('Chẩn đoán liên kết kiểm thử')->assertSee('Kết luận liên kết kiểm thử')->assertSee($visit->prescription->prescription_code);
        $this->flushSession()->actingAs($patient);
        $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'tong-quan']))->assertOk()
            ->assertSee('Chẩn đoán liên kết kiểm thử')->assertSee('Kết luận liên kết kiểm thử')->assertDontSee('Thông tin nội bộ kiểm thử');
        $this->get(route('patient.prescriptions.show', $visit->prescription))->assertOk()
            ->assertSee($medicine->name)->assertSee('Ghi chú đơn cho bệnh nhân')->assertSee('Hướng dẫn riêng kiểm thử');
        $this->get(route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'don-thuoc']))->assertOk()
            ->assertSee('2 lần/ngày')->assertSee('5 ngày')->assertSee('Lời dặn liên kết kiểm thử')->assertSee('Ghi chú đơn cho bệnh nhân');
        $this->flushSession()->actingAs($otherPatient);
        $this->get(route('patient.results.visit', ['visit' => $visit->id]))->assertNotFound();
        $this->get(route('patient.prescriptions.show', $visit->prescription))->assertNotFound();
    }
}
