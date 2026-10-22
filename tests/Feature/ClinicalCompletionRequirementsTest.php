<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\MedicalVisit;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Models\User;
use App\Models\VisitStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClinicalCompletionRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function item(): ServiceOrderItem
    {
        $source = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
        $order = $source->replicate();
        $order->service_order_code = 'CDREQUIRE'.ServiceOrder::count();
        $order->save();

        return $order->items()->create([
            'medical_service_id' => $source->items()->firstOrFail()->medical_service_id,
            'service_status_id' => ServiceStatus::where('name', 'PROCESSING')->value('id'),
            'price_at_order' => 120000, 'started_at' => now(),
        ]);
    }

    public function test_visit_requires_both_diagnosis_and_conclusion_before_completion(): void
    {
        foreach (['IN_EXAMINATION', 'RETURNING'] as $index => $status) {
            $source = MedicalVisit::where('visit_code', 'LK000005')->firstOrFail();
            $visit = $source->replicate();
            $visit->fill([
                'visit_code' => 'LKREQUIRE'.$index, 'appointment_id' => null,
                'visit_status_id' => VisitStatus::where('name', $status)->value('id'),
                'examination_started_at' => now(), 'examination_ended_at' => null,
            ])->save();
            $completed = ['visit_status_id' => VisitStatus::where('name', 'COMPLETED')->value('id')];
            $this->patch(route('admin.visits.update-status', $visit), $completed)->assertSessionHasErrors('visit_status_id');
            $this->assertNull($visit->fresh()->examination_ended_at);

            $first = $index === 0 ? 'diagnosis' : 'conclusion';
            foreach ([$first, $first === 'diagnosis' ? 'conclusion' : 'diagnosis'] as $step => $document) {
                if ($document === 'diagnosis') {
                    $this->post(route('admin.visits.diagnoses.store', $visit), [
                        'disease_id' => Disease::where('disease_code', 'J01.9')->value('id'), 'diagnosis_type' => 'PRIMARY',
                    ])->assertSessionHasNoErrors()->assertSessionMissing('error');
                } else {
                    $this->post(route('admin.visits.conclusion.store', $visit), [
                        'professional_conclusion' => 'Đã khám và đánh giá', 'has_follow_up' => 0,
                    ])->assertSessionHasNoErrors()->assertSessionMissing('error');
                }
                $response = $this->patch(route('admin.visits.update-status', $visit), $completed);
                if ($step === 0) {
                    $response->assertSessionHasErrors('visit_status_id');
                    $this->assertSame($status, $visit->fresh()->status->name);
                    $this->assertNull($visit->fresh()->examination_ended_at);
                } else {
                    $response->assertSessionHasNoErrors()->assertSessionMissing('error');
                    $this->assertSame('COMPLETED', $visit->fresh()->status->name);
                    $this->assertNotNull($visit->fresh()->examination_ended_at);
                }
            }
        }
    }

    public function test_empty_or_notes_only_service_results_are_rejected(): void
    {
        $item = $this->item();
        foreach ([[], ['result_summary' => '  ', 'result_conclusion' => "\t\n", 'result_pdf_path' => ' ', 'result_image_path' => ' '], ['notes' => 'Chỉ ghi chú', 'result_parameters' => '{"value":1}']] as $values) {
            $this->post(route('admin.service-order-items.service-results.store', $item), [
                ...$values, 'confirm_create' => 1,
            ])->assertSessionHasErrors('result_summary');
            $this->assertFalse($item->result()->exists());
        }
    }

    public function test_each_supported_content_or_attachment_field_can_satisfy_result_requirement(): void
    {
        foreach (['result_summary' => 'Tóm tắt kết quả', 'result_conclusion' => 'Kết luận chuyên môn',
            'result_pdf' => UploadedFile::fake()->createWithContent('test.pdf', "%PDF-1.4\n%%EOF"),
            'result_image' => UploadedFile::fake()->createWithContent('test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3p8AAAAASUVORK5CYII=')),
        ] as $field => $value) {
            $item = $this->item();
            $this->post(route('admin.service-order-items.service-results.store', $item), [
                $field => $value, 'confirm_create' => 1,
            ])->assertSessionHasNoErrors()->assertSessionMissing('error');
            if ($value instanceof UploadedFile) {
                Storage::disk('local')->assertExists($item->fresh()->result->{$field.'_path'});
            } else {
                $this->assertSame($value, $item->fresh()->result->{$field});
            }
            $this->assertTrue($item->fresh()->result->hasContent());
        }
    }

    public function test_update_cannot_erase_all_result_content_but_can_replace_it_with_attachment(): void
    {
        $result = ServiceResult::create(['service_order_item_id' => $this->item()->id, 'result_summary' => 'Kết quả cần giữ']);
        $this->patch(route('admin.service-results.update', $result), [
            'result_summary' => '  ', 'notes' => 'Ghi chú', 'confirm_update' => 1,
        ])->assertSessionHasErrors('result_summary');
        $this->assertSame('Kết quả cần giữ', $result->fresh()->result_summary);
        $this->patch(route('admin.service-results.update', $result), [
            'result_image' => UploadedFile::fake()->createWithContent('updated.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3p8AAAAASUVORK5CYII=')), 'confirm_update' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertNull($result->fresh()->result_summary);
        $this->assertTrue($result->fresh()->hasDocument('image'));
    }

    public function test_legacy_empty_result_cannot_complete_service_until_content_is_added(): void
    {
        $item = $this->item();
        $result = ServiceResult::create(['service_order_item_id' => $item->id, 'result_summary' => '  ', 'notes' => 'Chưa nhập kết quả']);
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionHas('error');
        $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name);
        $this->assertNull($item->fresh()->completed_at);
        $this->patch(route('admin.service-results.update', $result), [
            'result_conclusion' => 'Đã nhập kết quả', 'confirm_update' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->post(route('admin.service-order-items.complete', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
        $this->assertNotNull($item->fresh()->completed_at);
    }
}
