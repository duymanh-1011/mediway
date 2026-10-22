<?php

namespace Tests\Feature;

use App\Models\MedicalVisit;
use App\Models\Prescription;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceResult;
use App\Models\ServiceStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ClinicalDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs(User::where('business_code', 'QT000001')->firstOrFail());
    }

    private function item(): ServiceOrderItem
    {
        $source = ServiceOrder::where('service_order_code', 'CD000004')->firstOrFail();
        $order = $source->replicate();
        $order->service_order_code = 'CDPRIVATE'.ServiceOrder::count();
        $order->save();

        return $order->items()->create([
            'medical_service_id' => $source->items()->firstOrFail()->medical_service_id,
            'service_status_id' => ServiceStatus::where('name', 'PROCESSING')->value('id'),
            'price_at_order' => 120000, 'started_at' => now(),
        ]);
    }

    private function pdf(string $name = 'ket-qua.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('ket-qua.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3p8AAAAASUVORK5CYII='));
    }

    private function upload(ServiceOrderItem $item, string $field = 'result_pdf'): ServiceResult
    {
        $this->post(route('admin.service-order-items.service-results.store', $item), [
            $field => $field === 'result_pdf' ? $this->pdf() : $this->image(), 'confirm_create' => 1,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        return $item->result()->sole();
    }

    private function prescription(): Prescription
    {
        $visit = MedicalVisit::where('visit_code', 'LK000004')->firstOrFail();

        return Prescription::create([
            'prescription_code' => 'DTPRIVATE', 'medical_visit_id' => $visit->id,
            'doctor_id' => $visit->doctor_id, 'prescribed_at' => now(), 'status' => 'ACTIVE',
        ]);
    }

    /** @return array<string, mixed> */
    private function prescriptionData(): array
    {
        return ['prescribed_at' => now()->format('Y-m-d H:i:s'), 'status' => 'ACTIVE', 'confirm_update' => 1];
    }

    public function test_arbitrary_paths_and_urls_cannot_satisfy_or_overwrite_a_result(): void
    {
        $item = $this->item();
        foreach (['results/missing.pdf', 'https://example.com/result.pdf', '../.env', 'patient-documents/1/report.pdf'] as $path) {
            $this->post(route('admin.service-order-items.service-results.store', $item), [
                'result_pdf_path' => $path, 'confirm_create' => 1,
            ])->assertSessionHasErrors(['result_pdf_path', 'result_summary']);
            $this->assertFalse($item->result()->exists());
        }
        $result = $this->upload($item);
        $path = $result->result_pdf_path;
        $this->patch(route('admin.service-results.update', $result), [
            'result_pdf_path' => 'https://example.com/result.pdf', 'confirm_update' => 1,
        ])->assertSessionHasErrors('result_pdf_path');
        $this->assertSame($path, $result->fresh()->result_pdf_path);
    }

    public function test_real_pdf_and_image_are_private_and_allow_service_completion(): void
    {
        foreach (['pdf', 'image'] as $type) {
            $item = $this->item();
            $result = $this->upload($item, 'result_'.$type);
            $path = $result->getAttribute('result_'.$type.'_path');
            $this->assertStringStartsWith('service-results/'.$item->id.'/', $path);
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
            $this->assertTrue($result->hasContent());
            $url = route('admin.service-results.document', [$result->id, $type]);
            $response = $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertStringContainsString($type === 'pdf' ? '%PDF-1.4' : 'PNG', $response->streamedContent());
            $this->get(route('admin.service-results.show', $result))->assertOk()
                ->assertSee('href="'.$url.'"', false)->assertDontSee('/storage/'.$path, false);
            $this->post(route('admin.service-order-items.complete', $item))->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertSame('COMPLETED', $item->fresh()->serviceStatus->name);
        }
    }

    public function test_deleted_empty_or_fake_attachments_do_not_count_as_result_content(): void
    {
        $item = $this->item();
        $result = $this->upload($item);
        $path = $result->result_pdf_path;
        foreach ([null, '', '<?php echo "not a PDF";'] as $content) {
            if ($content === null) {
                Storage::disk('local')->delete($path);
            } else {
                Storage::disk('local')->put($path, $content);
            }
            $this->assertFalse($result->fresh()->hasContent());
            $this->get(route('admin.service-results.document', [$result->id, 'pdf']))->assertNotFound();
            $this->post(route('admin.service-order-items.complete', $item))->assertSessionHas('error');
            $this->assertSame('PROCESSING', $item->fresh()->serviceStatus->name);
        }
    }

    public function test_result_download_rejects_cross_record_paths_traversal_and_external_urls(): void
    {
        $first = $this->upload($this->item());
        $other = $this->upload($this->item());
        $url = route('admin.service-results.document', [$first->id, 'pdf']);
        foreach ([$other->result_pdf_path, 'service-results/'.$first->service_order_item_id.'/../other.pdf',
            'service-results/'.$first->service_order_item_id.'\\other.pdf', 'https://example.com/result.pdf',
        ] as $path) {
            $first->update(['result_pdf_path' => $path]);
            $this->get($url)->assertNotFound();
            $this->assertFalse($first->hasContent());
        }
        $this->get(route('admin.service-results.document', [$first->id, 'invalid']))->assertNotFound();
        $this->get(route('admin.service-results.document', [$first->id, 'pdf']))->assertNotFound();
    }

    public function test_guest_non_admin_and_locked_accounts_cannot_download_clinical_documents(): void
    {
        $result = $this->upload($this->item());
        $prescription = $this->prescription();
        $this->patch(route('admin.prescriptions.update', $prescription), [
            ...$this->prescriptionData(), 'prescription_pdf' => $this->pdf(),
        ])->assertSessionHasNoErrors();
        $urls = [route('admin.service-results.document', [$result->id, 'pdf']), route('admin.prescriptions.document', $prescription)];
        foreach ($urls as $url) {
            Auth::logout();
            $this->get($url)->assertRedirect(route('admin.login'));
            $this->actingAs(User::where('business_code', 'BS000001')->firstOrFail());
            $this->get($url)->assertRedirect(route('admin.login'));
            $admin = User::where('business_code', 'QT000001')->firstOrFail();
            $admin->update(['status' => 'LOCKED']);
            $this->actingAs($admin);
            $this->get($url)->assertRedirect(route('admin.login'));
            $admin->update(['status' => 'ACTIVE']);
        }
    }

    public function test_invalid_extension_mime_and_oversized_uploads_are_rejected_without_files(): void
    {
        $item = $this->item();
        foreach ([
            ['result_pdf', $this->pdf('script.php')],
            ['result_pdf', UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo "invalid";')],
            ['result_pdf', UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')],
            ['result_image', UploadedFile::fake()->createWithContent('fake.png', '<?php echo "invalid";')],
            ['result_image', UploadedFile::fake()->createWithContent('unsafe.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
        ] as [$field, $file]) {
            $this->post(route('admin.service-order-items.service-results.store', $item), [
                $field => $file, 'confirm_create' => 1,
            ])->assertSessionHasErrors($field);
            $this->assertFalse($item->result()->exists());
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_result_update_keeps_existing_file_and_replacements_keep_audit_history(): void
    {
        $result = $this->upload($this->item());
        $original = $result->result_pdf_path;
        $this->patch(route('admin.service-results.update', $result), ['notes' => 'Bổ sung ghi chú', 'confirm_update' => 1])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame($original, $result->fresh()->result_pdf_path);
        $this->patch(route('admin.service-results.update', $result), ['result_pdf' => $this->pdf('moi.pdf'), 'confirm_update' => 1])
            ->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertNotSame($original, $result->fresh()->result_pdf_path);
        Storage::disk('local')->assertExists([$original, $result->fresh()->result_pdf_path]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'service_results', 'entity_id' => $result->id, 'action' => 'UPDATE']);
    }

    public function test_failed_result_update_rolls_back_database_and_only_discards_new_upload(): void
    {
        $result = $this->upload($this->item());
        $original = $result->result_pdf_path;
        ServiceResult::updated(function (): void {
            throw new RuntimeException('Simulated database operation failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->patch(route('admin.service-results.update', $result), ['result_pdf' => $this->pdf('moi.pdf'), 'confirm_update' => 1]);
            $this->fail('Expected the database operation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database operation failure', $exception->getMessage());
        }
        $this->assertSame($original, $result->fresh()->result_pdf_path);
        $this->assertSame([$original], Storage::disk('local')->allFiles());
    }

    public function test_prescription_upload_is_private_preserved_and_cannot_bypass_completed_visit_lock(): void
    {
        $prescription = $this->prescription();
        $this->patch(route('admin.prescriptions.update', $prescription), [
            ...$this->prescriptionData(), 'prescription_pdf' => $this->pdf('don-thuoc.pdf'),
        ])->assertSessionHasNoErrors();
        $path = $prescription->fresh()->prescription_pdf_path;
        $this->assertTrue($prescription->fresh()->hasPdf());
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $url = route('admin.prescriptions.document', $prescription);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('admin.prescriptions.show', $prescription))->assertOk()
            ->assertSee('href="'.$url.'"', false)->assertDontSee('/storage/'.$path, false);
        $this->patch(route('admin.prescriptions.update', $prescription), $this->prescriptionData())->assertSessionHasNoErrors();
        $this->assertSame($path, $prescription->fresh()->prescription_pdf_path);
        $closed = Prescription::whereHas('medicalVisit.status', fn ($query) => $query->where('name', 'COMPLETED'))->firstOrFail();
        $before = Storage::disk('local')->allFiles();
        $this->patch(route('admin.prescriptions.update', $closed), [...$this->prescriptionData(), 'prescription_pdf' => $this->pdf()])
            ->assertSessionHasErrors('prescription');
        $this->assertSame($before, Storage::disk('local')->allFiles());
    }

    public function test_prescription_rejects_manual_paths_and_cross_record_downloads(): void
    {
        $prescription = $this->prescription();
        $this->patch(route('admin.prescriptions.update', $prescription), [
            ...$this->prescriptionData(), 'prescription_pdf_path' => 'https://example.com/prescription.pdf',
        ])->assertSessionHasErrors('prescription_pdf_path');
        $result = $this->upload($this->item());
        $prescription->update(['prescription_pdf_path' => $result->result_pdf_path]);
        $this->get(route('admin.prescriptions.document', $prescription))->assertNotFound();
        $prescription->update(['prescription_pdf_path' => 'prescriptions/'.$prescription->id.'/../other.pdf']);
        $this->get(route('admin.prescriptions.document', $prescription))->assertNotFound();
        $this->assertFalse($prescription->hasPdf());
    }

    public function test_forms_use_file_inputs_instead_of_manual_storage_paths(): void
    {
        $item = $this->item();
        $result = $this->upload($item);
        foreach ([route('admin.service-order-items.service-results.create', $this->item()), route('admin.service-results.edit', $result)] as $url) {
            $this->get($url)->assertOk()->assertSee('enctype="multipart/form-data"', false)
                ->assertSee('name="result_pdf"', false)->assertSee('name="result_image"', false)
                ->assertDontSee('name="result_pdf_path"', false)->assertDontSee('name="result_image_path"', false);
        }
        $this->get(route('admin.prescriptions.edit', $this->prescription()))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)->assertSee('name="prescription_pdf"', false)
            ->assertDontSee('name="prescription_pdf_path"', false);
    }
}
